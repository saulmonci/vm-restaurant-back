<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    /**
     * Formatter principal que maneja toda la lógica de formateo
     */
    protected function formatter($item, Request $request, array $options = []): array
    {
        $formatted = [
            // Campos base del User
            'name' => $item->name,
            'email' => $item->email,
            'email_verified_at' => $item->email_verified_at,
        ];

        // Información de perfil público
        $formatted['profile'] = $this->getPublicProfile($item, $options);

        // Formatear empresas si están cargadas
        if ($item->relationLoaded('companies')) {
            $companies = $item->companies;

            if ($options['include_companies'] ?? false) {
                $formatted['companies'] = CompanyResource::collection($companies);
            }

            $formatted['companies_count'] = $companies->count();
            $formatted['company_roles'] = $this->getCompanyRoles($item, $companies);

            // Empresa principal si existe
            $mainCompany = $companies->where('pivot.is_main', true)->first();
            if ($mainCompany) {
                $formatted['main_company'] = [
                    'id' => $mainCompany->id,
                    'name' => $mainCompany->name,
                    'role' => $mainCompany->pivot->role ?? 'member',
                ];
            }
        }

        // Información privada solo para el usuario actual o admins
        if ($this->shouldIncludePrivateInfo($item, $request, $options)) {
            $formatted['private_info'] = $this->getPrivateInfo($item, $options);
        }

        // Configuraciones del usuario
        if ($options['include_settings'] ?? false) {
            $formatted['settings'] = $this->getUserSettings($item, $options);
        }

        // Estadísticas del usuario
        if ($options['include_stats'] ?? false) {
            $formatted['stats'] = $this->getUserStats($item, $options);
        }

        // Permisos globales
        if ($options['include_permissions'] ?? false) {
            $formatted['permissions'] = $this->getUserPermissions($item, $request, $options);
        }

        // Estado de actividad
        $formatted['activity_status'] = $this->getActivityStatus($item, $options);

        return $formatted;
    }

    /**
     * Obtener perfil público del usuario
     */
    protected function getPublicProfile($user, array $options = []): array
    {
        return [
            'display_name' => $user->display_name ?? $user->name,
            'avatar' => $user->avatar_url ?? null,
            'bio' => $user->bio ?? null,
            'is_active' => $user->is_active ?? true,
            'joined_at' => $user->created_at->toDateString(),
        ];
    }

    /**
     * Obtener roles en empresas
     */
    protected function getCompanyRoles($user, $companies): array
    {
        $roles = [];

        foreach ($companies as $company) {
            $roles[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'role' => $company->pivot->role ?? 'member',
                'is_main' => $company->pivot->is_main ?? false,
                'joined_at' => $company->pivot->created_at ?? null,
            ];
        }

        return $roles;
    }

    /**
     * Verificar si se debe incluir información privada
     */
    protected function shouldIncludePrivateInfo($user, Request $request, array $options = []): bool
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            return false;
        }

        // Si es el mismo usuario
        if ($currentUser->id === $user->id) {
            return true;
        }

        // Si es admin global
        if ($currentUser->hasRole('super_admin')) {
            return true;
        }

        // Si trabajan en la misma empresa y el usuario actual es admin/manager
        if ($options['check_company_access'] ?? false) {
            $sharedCompanies = $currentUser->companies()
                ->whereIn('company_id', $user->companies->pluck('id'))
                ->get();

            foreach ($sharedCompanies as $company) {
                $role = $company->pivot->role ?? 'member';
                if (in_array($role, ['admin', 'manager'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtener información privada del usuario
     */
    protected function getPrivateInfo($user, array $options = []): array
    {
        return [
            'phone' => $user->phone ?? null,
            'address' => $user->address ?? null,
            'birth_date' => $user->birth_date ?? null,
            'preferences' => $user->preferences ?? [],
            'last_login_at' => $user->last_login_at ?? null,
            'login_count' => $user->login_count ?? 0,
        ];
    }

    /**
     * Obtener configuraciones del usuario
     */
    protected function getUserSettings($user, array $options = []): array
    {
        return [
            'notifications' => [
                'email_notifications' => $user->email_notifications ?? true,
                'push_notifications' => $user->push_notifications ?? true,
                'sms_notifications' => $user->sms_notifications ?? false,
            ],
            'privacy' => [
                'profile_public' => $user->profile_public ?? true,
                'show_activity' => $user->show_activity ?? true,
            ],
            'preferences' => [
                'language' => $user->preferred_language ?? 'es',
                'timezone' => $user->timezone ?? 'UTC',
                'currency' => $user->preferred_currency ?? 'USD',
            ],
        ];
    }

    /**
     * Obtener estadísticas del usuario
     */
    protected function getUserStats($user, array $options = []): array
    {
        // Aquí podrías calcular estadísticas reales basadas en órdenes, actividad, etc.
        return [
            'total_orders' => $this->getUserOrderCount($user),
            'favorite_categories' => $this->getFavoriteCategories($user),
            'total_spent' => $this->getTotalSpent($user),
            'avg_order_value' => $this->getAverageOrderValue($user),
            'last_order_date' => $this->getLastOrderDate($user),
        ];
    }

    /**
     * Obtener permisos globales del usuario
     */
    protected function getUserPermissions($user, Request $request, array $options = []): array
    {
        $currentUser = $request->user();
        $isSameUser = $currentUser && $currentUser->id === $user->id;
        $isAdmin = $currentUser && $currentUser->hasRole('super_admin');

        return [
            'can_edit_profile' => $isSameUser || $isAdmin,
            'can_delete_account' => $isSameUser || $isAdmin,
            'can_manage_companies' => $this->canManageCompanies($user, $currentUser),
            'can_view_private_info' => $this->shouldIncludePrivateInfo($user, $request, $options),
            'can_impersonate' => $isAdmin && !$isSameUser,
        ];
    }

    /**
     * Obtener estado de actividad
     */
    protected function getActivityStatus($user, array $options = []): array
    {
        $lastActivity = $user->last_activity_at ?? $user->updated_at;
        $isOnline = $lastActivity && $lastActivity->diffInMinutes(now()) < 5;

        return [
            'is_online' => $isOnline,
            'last_activity' => $lastActivity?->toISOString(),
            'status' => $this->getUserStatus($user, $isOnline),
        ];
    }

    /**
     * Métodos helper para estadísticas (implementar según tu lógica de negocio)
     */
    protected function getUserOrderCount($user): int
    {
        // return $user->orders()->count();
        return 0; // Placeholder
    }

    protected function getFavoriteCategories($user): array
    {
        // Lógica para obtener categorías favoritas
        return []; // Placeholder
    }

    protected function getTotalSpent($user): float
    {
        // return $user->orders()->sum('total_amount');
        return 0.0; // Placeholder
    }

    protected function getAverageOrderValue($user): float
    {
        // return $user->orders()->avg('total_amount') ?? 0;
        return 0.0; // Placeholder
    }

    protected function getLastOrderDate($user): ?string
    {
        // return $user->orders()->latest()->first()?->created_at?->toISOString();
        return null; // Placeholder
    }

    protected function canManageCompanies($user, $currentUser): bool
    {
        if (!$currentUser) return false;

        return $currentUser->hasRole('super_admin') ||
            $currentUser->id === $user->id;
    }

    protected function getUserStatus($user, bool $isOnline): string
    {
        if (!($user->is_active ?? true)) {
            return 'inactive';
        }

        if ($isOnline) {
            return 'online';
        }

        return 'offline';
    }
}
