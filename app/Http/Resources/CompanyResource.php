<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CompanyResource extends BaseResource
{
    /**
     * Formatter principal que maneja toda la lógica de formateo
     */
    protected function formatter($item, Request $request, array $options = []): array
    {
        $formatted = [
            // Campos base de la Company
            'name' => $item->name,
            'description' => $item->description,
        ];

        // Formatear usuarios si están cargados
        if ($item->relationLoaded('users')) {
            $formatted['users'] = $item->users;
            $formatted['users_count'] = $item->users->count();

            // Información adicional de usuarios si se solicita
            if ($options['include_user_details'] ?? false) {
                $formatted['active_users_count'] = $item->users->where('is_active', true)->count();
                $formatted['admin_users_count'] = $item->users->where('role', 'admin')->count();
            }
        } else {
            $formatted['users'] = null;
            $formatted['users_count'] = null;
        }

        // Formatear categorías de menú si están cargadas
        if ($item->relationLoaded('menuCategories')) {
            $categories = $item->menuCategories;

            $formatted['menu_categories'] = $categories;
            $formatted['menu_categories_count'] = $categories->count();
            $formatted['active_categories_count'] = $categories->where('is_active', true)->count();

            // Si se solicita información detallada
            if ($options['include_menu_stats'] ?? false) {
                $formatted['menu_stats'] = $this->getMenuStats($categories, $options);
            }
        } else {
            $formatted['menu_categories'] = null;
            $formatted['menu_categories_count'] = null;
        }

        // Información de estado de la empresa
        $formatted['status_info'] = $this->getCompanyStatus($item, $options);

        // Información de configuración si se solicita
        if ($options['include_settings'] ?? false) {
            $formatted['settings'] = $this->getCompanySettings($item, $options);
        }

        // Información de horarios y delivery
        $formatted['business_info'] = $this->getBusinessInfo($item, $options);

        // Permisos del usuario actual
        if ($options['include_permissions'] ?? false) {
            $formatted['permissions'] = $this->getCompanyPermissions($item, $request, $options);
        }

        return $formatted;
    }

    /**
     * Obtener estadísticas del menú
     */
    protected function getMenuStats($categories, array $options = []): array
    {
        $totalItems = 0;
        $activeItems = 0;

        foreach ($categories as $category) {
            if ($category->relationLoaded('menuItems')) {
                $totalItems += $category->menuItems->count();
                $activeItems += $category->menuItems->where('is_available', true)->count();
            }
        }

        return [
            'total_menu_items' => $totalItems,
            'active_menu_items' => $activeItems,
            'categories_with_items' => $categories->filter(function ($cat) {
                return $cat->relationLoaded('menuItems') && $cat->menuItems->count() > 0;
            })->count(),
        ];
    }

    /**
     * Obtener estado general de la empresa
     */
    protected function getCompanyStatus($company, array $options = []): array
    {
        $isActive = $company->is_active ?? true;
        $isOpen = $this->isCompanyOpen($company, $options);

        return [
            'is_active' => $isActive,
            'is_open' => $isOpen,
            'can_take_orders' => $isActive && $isOpen,
            'status_message' => $this->getStatusMessage($company, $isActive, $isOpen),
        ];
    }

    /**
     * Obtener configuraciones de la empresa
     */
    protected function getCompanySettings($company, array $options = []): array
    {
        return [
            'delivery_enabled' => $company->delivery_enabled ?? true,
            'pickup_enabled' => $company->pickup_enabled ?? true,
            'delivery_radius' => $company->delivery_radius ?? null,
            'min_order_amount' => $company->min_order_amount ?? 0,
            'delivery_fee' => $company->delivery_fee ?? 0,
            'estimated_delivery_time' => $company->estimated_delivery_time ?? 30,
            'payment_methods' => $company->payment_methods ?? ['cash', 'card'],
        ];
    }

    /**
     * Obtener información de horarios y negocio
     */
    protected function getBusinessInfo($company, array $options = []): array
    {
        $timezone = $options['timezone'] ?? 'UTC';

        return [
            'open_hour' => $company->open_hour ?? 9,
            'close_hour' => $company->close_hour ?? 22,
            'timezone' => $timezone,
            'current_time' => now($timezone)->format('H:i'),
            'is_open_now' => $this->isCompanyOpen($company, $options),
            'next_opening' => $this->getNextOpening($company, $options),
        ];
    }

    /**
     * Obtener permisos del usuario para esta empresa
     */
    protected function getCompanyPermissions($company, Request $request, array $options = []): array
    {
        $user = $request->user();

        if (!$user) {
            return [
                'can_edit' => false,
                'can_delete' => false,
                'can_manage_users' => false,
                'can_manage_menu' => false,
                'can_view_orders' => false,
            ];
        }

        // Verificar si el usuario pertenece a esta empresa
        $belongsToCompany = $user->companies()->where('company_id', $company->id)->exists();
        $isAdmin = $belongsToCompany && $user->hasRole('admin');
        $isManager = $belongsToCompany && $user->hasRole('manager');

        return [
            'can_edit' => $belongsToCompany,
            'can_delete' => $isAdmin,
            'can_manage_users' => $isAdmin || $isManager,
            'can_manage_menu' => $belongsToCompany,
            'can_view_orders' => $belongsToCompany,
            'role_in_company' => $this->getUserRoleInCompany($user, $company),
        ];
    }

    /**
     * Verificar si la empresa está abierta
     */
    protected function isCompanyOpen($company, array $options = []): bool
    {
        $timezone = $options['timezone'] ?? 'UTC';
        $currentHour = now($timezone)->hour;
        $openHour = $company->open_hour ?? 9;
        $closeHour = $company->close_hour ?? 22;

        return $currentHour >= $openHour && $currentHour <= $closeHour;
    }

    /**
     * Obtener mensaje de estado
     */
    protected function getStatusMessage($company, bool $isActive, bool $isOpen): ?string
    {
        if (!$isActive) {
            return 'Empresa temporalmente cerrada';
        }

        if (!$isOpen) {
            $openHour = $company->open_hour ?? 9;
            return "Cerrado. Abre a las {$openHour}:00";
        }

        return null;
    }

    /**
     * Obtener próxima apertura
     */
    protected function getNextOpening($company, array $options = []): ?string
    {
        if ($this->isCompanyOpen($company, $options)) {
            return null;
        }

        $timezone = $options['timezone'] ?? 'UTC';
        $openHour = $company->open_hour ?? 9;
        $tomorrow = now($timezone)->addDay()->setHour($openHour)->setMinute(0);

        return $tomorrow->format('Y-m-d H:i:s');
    }

    /**
     * Obtener rol del usuario en la empresa
     */
    protected function getUserRoleInCompany($user, $company): ?string
    {
        $companyUser = $user->companies()->where('company_id', $company->id)->first();
        return $companyUser ? $companyUser->pivot->role ?? 'member' : null;
    }
}
