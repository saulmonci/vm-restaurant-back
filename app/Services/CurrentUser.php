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
     * Check if user has a specific role or permission
     */
    public function hasRole(string $role): bool
    {
        $user = $this->get();
        
        if (!$user) {
            return false;
        }
        
        // This can be expanded based on your role system
        // For now, checking a simple 'role' attribute
        return $user->role === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
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
     * Clear all cached data
     */
    public function clearCache(): void
    {
        if ($this->user) {
            Cache::forget("user.{$this->user->id}");
        }
        
        $this->user = null;
        $this->userId = null;
        $this->preferences = null;
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
        }
    }
}