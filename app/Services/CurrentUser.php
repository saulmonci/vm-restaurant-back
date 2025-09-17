<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing current authenticated user context
 * Registered as singleton in Laravel Service Container
 * Caches user data to avoid repeated queries and Auth::user() calls
 */
class CurrentUser
{
    private ?User $user = null;
    private ?int $userId = null;
    private ?array $preferences = null;
    private ?array $roles = null;
    private ?array $permissions = null;
    private bool $loaded = false;

    /**
     * Initialize current user context
     */
    public function initialize(): void
    {
        if ($this->loaded) {
            return; // Already loaded in this request
        }

        if (Auth::check()) {
            $this->userId = Auth::id();
            $this->loadUserData();
        }

        $this->loaded = true;
    }

    /**
     * Get current user ID
     */
    public function id(): ?int
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        return $this->userId;
    }

    /**
     * Get current user instance
     */
    public function get(): ?User
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        return $this->user;
    }

    /**
     * Check if user is authenticated
     */
    public function exists(): bool
    {
        return $this->id() !== null;
    }

    /**
     * Check if user is authenticated (alias for exists())
     */
    public function check(): bool
    {
        return $this->exists();
    }

    /**
     * Get user preferences
     */
    public function preferences(string $key = null, $default = null)
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        if ($key) {
            return data_get($this->preferences, $key, $default);
        }

        return $this->preferences ?? $default;
    }

    /**
     * Get user's name
     */
    public function name(): ?string
    {
        $user = $this->get();
        return $user ? ($user->display_name ?: $user->name) : null;
    }

    /**
     * Get user's email
     */
    public function email(): ?string
    {
        $user = $this->get();
        return $user?->email;
    }

    /**
     * Get user's timezone
     */
    public function timezone(): string
    {
        $user = $this->get();
        return $user?->timezone ?? config('app.timezone', 'UTC');
    }

    /**
     * Get user's preferred language
     */
    public function language(): string
    {
        $user = $this->get();
        return $user?->preferred_language ?? config('app.locale', 'en');
    }

    /**
     * Get user's preferred currency
     */
    public function currency(): string
    {
        $user = $this->get();
        return $user?->preferred_currency ?? 'USD';
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        $user = $this->get();
        return $user?->is_active ?? false;
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(array $newPreferences): bool
    {
        $user = $this->get();

        if (!$user) {
            return false;
        }

        $currentPreferences = $user->preferences ?? [];
        $mergedPreferences = array_merge($currentPreferences, $newPreferences);

        // Update in database
        $updated = $user->update(['preferences' => $mergedPreferences]);

        if ($updated) {
            // Update cache
            $this->preferences = $mergedPreferences;
            $this->user->preferences = $mergedPreferences;

            // Update cache storage
            $cacheKey = "user.{$user->id}";
            Cache::put($cacheKey, $user, 3600); // 1 hour
        }

        return $updated;
    }

    /**
     * Update user's last activity
     */
    public function updateLastActivity(): bool
    {
        $user = $this->get();

        if (!$user) {
            return false;
        }

        return $user->update([
            'last_activity_at' => now()
        ]);
    }

    /**
     * Get user's companies (if using multi-tenancy)
     */
    public function companies()
    {
        $user = $this->get();

        if (!$user) {
            return collect([]);
        }

        return $user->companies()->get();
    }

    /**
     * Get user roles in the current company
     */
    public function roles(): array
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        return $this->roles ?? [];
    }

    /**
     * Get user permissions in the current company
     */
    public function permissions(): array
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        return $this->permissions ?? [];
    }

    /**
     * Check if user has a specific role in the current company
     */
    public function hasRole(string $roleName): bool
    {
        return in_array($roleName, $this->roles());
    }

    /**
     * Check if user has any of the specified roles in the current company
     */
    public function hasAnyRole(array $roleNames): bool
    {
        $userRoles = $this->roles();
        return !empty(array_intersect($userRoles, $roleNames));
    }

    /**
     * Check if user has all of the specified roles in the current company
     */
    public function hasAllRoles(array $roleNames): bool
    {
        $userRoles = $this->roles();
        return empty(array_diff($roleNames, $userRoles));
    }

    /**
     * Check if user has a specific permission in the current company
     */
    public function hasPermission(string $permissionName): bool
    {
        return in_array($permissionName, $this->permissions());
    }

    /**
     * Check if user has any of the specified permissions in the current company
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        $userPermissions = $this->permissions();
        return !empty(array_intersect($userPermissions, $permissionNames));
    }

    /**
     * Check if user has all of the specified permissions in the current company
     */
    public function hasAllPermissions(array $permissionNames): bool
    {
        $userPermissions = $this->permissions();
        return empty(array_diff($permissionNames, $userPermissions));
    }

    /**
     * Check if user is admin in the current company
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is manager in the current company
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user can manage users (has permission or is admin)
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermission('manage_users') || $this->isAdmin();
    }

    /**
     * Check if user can manage menu (has permission or is admin/manager)
     */
    public function canManageMenu(): bool
    {
        return $this->hasAnyPermission(['create_menu', 'edit_menu', 'delete_menu'])
            || $this->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Clear all cached data
     */
    public function clearCache(): void
    {
        if ($this->user) {
            Cache::forget("user.{$this->user->id}");

            // Also clear roles and permissions cache
            $currentCompany = app(\App\Services\CurrentCompany::class);
            $company = $currentCompany->get();
            if ($company) {
                Cache::forget("user_roles_permissions.{$this->user->id}.{$company->id}");
            }
        }

        $this->user = null;
        $this->userId = null;
        $this->preferences = null;
        $this->roles = null;
        $this->permissions = null;
        $this->loaded = false;
    }

    /**
     * Refresh user data (useful after updates)
     */
    public function refresh(): void
    {
        $this->clearCache();
        $this->initialize();
    }

    // =================== Private Methods ===================

    /**
     * Load user data from database with caching
     */
    private function loadUserData(): void
    {
        if (!$this->userId) {
            return;
        }

        $cacheKey = "user.{$this->userId}";

        // Try to get from cache first
        $this->user = Cache::remember($cacheKey, 3600, function () {
            return User::find($this->userId);
        });

        if ($this->user) {
            $this->preferences = $this->user->preferences ?? [];
            $this->loadRolesAndPermissions();
        }
    }

    /**
     * Load user roles and permissions for the current company
     */
    private function loadRolesAndPermissions(): void
    {
        if (!$this->user) {
            return;
        }

        $currentCompany = app(\App\Services\CurrentCompany::class);
        $company = $currentCompany->get();

        if (!$company) {
            $this->roles = [];
            $this->permissions = [];
            return;
        }

        $cacheKey = "user_roles_permissions.{$this->userId}.{$company->id}";

        $rolesAndPermissions = Cache::remember($cacheKey, 1800, function () use ($company) {
            // Get user roles for the current company
            $roles = $this->user->roles()
                ->wherePivot('company_id', $company->id)
                ->with('permissions')
                ->get();

            $roleNames = $roles->pluck('name')->toArray();
            $permissions = $roles->flatMap(function ($role) {
                return $role->permissions->pluck('name');
            })->unique()->values()->toArray();

            return [
                'roles' => $roleNames,
                'permissions' => $permissions
            ];
        });

        $this->roles = $rolesAndPermissions['roles'];
        $this->permissions = $rolesAndPermissions['permissions'];
    }
}
