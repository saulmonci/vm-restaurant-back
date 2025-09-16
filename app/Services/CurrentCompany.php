<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Models\CompanyUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing current company context
 * Registered as singleton in Laravel Service Container
 * Caches company data to avoid repeated DB queries
 */
class CurrentCompany
{
    private ?Company $company = null;
    private ?int $companyId = null;
    private ?array $settings = null;
    private bool $loaded = false;

    /**
     * Initialize current company context (called by service provider)
     */
    public function initialize(): void
    {
        if ($this->loaded) {
            return; // Already loaded in this request
        }

        $this->companyId = $this->resolveCompanyId();

        if ($this->companyId) {
            $this->loadCompanyData();
        }

        $this->loaded = true;
    }

    /**
     * Get current company ID
     */
    public function id(): ?int
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        return $this->companyId;
    }

    /**
     * Get current company instance
     */
    public function get(): ?Company
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        return $this->company;
    }

    /**
     * Get company settings
     */
    public function settings(string $key = null, $default = null)
    {
        if (!$this->loaded) {
            $this->initialize();
        }

        if ($key) {
            return data_get($this->settings, $key, $default);
        }

        return $this->settings ?? $default;
    }

    /**
     * Check if user has company context
     */
    public function exists(): bool
    {
        return $this->id() !== null;
    }

    /**
     * Switch to different company (clears cache)
     */
    public function switchTo(int $companyId): bool
    {
        if (!$this->userHasAccessToCompany(Auth::user(), $companyId)) {
            return false;
        }

        // Clear current cache
        $this->clearCache();

        // Set new company
        session(['current_company_id' => $companyId]);
        $this->companyId = $companyId;
        $this->loadCompanyData();

        return true;
    }

    /**
     * Update company settings (updates cache)
     */
    public function updateSettings(array $newSettings): bool
    {
        $company = $this->get();

        if (!$company) {
            return false;
        }

        $currentSettings = $company->settings ?? [];
        $mergedSettings = array_merge($currentSettings, $newSettings);

        // Update in database
        $updated = $company->update(['settings' => $mergedSettings]);

        if ($updated) {
            // Update cache
            $this->settings = $mergedSettings;
            $this->company->settings = $mergedSettings;

            // Update cache storage
            $cacheKey = "company.{$company->id}";
            Cache::put($cacheKey, $company, 3600); // 1 hour
        }

        return $updated;
    }

    /**
     * Get all companies the current user has access to
     */
    public function getUserCompanies()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return collect([]);
        }

        // Get companies where the user is associated
        return $user->companies()->get();
    }

    /**
     * Clear all cached data
     */
    public function clearCache(): void
    {
        if ($this->company) {
            Cache::forget("company.{$this->company->id}");
        }

        $this->company = null;
        $this->companyId = null;
        $this->settings = null;
        $this->loaded = false;
    }

    // =================== Private Methods ===================

    private function resolveCompanyId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Priority 1: User's direct company_id
        if (isset($user->company_id)) {
            return $user->company_id;
        }

        // Priority 2: Session-stored company (for multi-company users)
        if (session()->has('current_company_id')) {
            $sessionCompanyId = session('current_company_id');

            if ($this->userHasAccessToCompany($user, $sessionCompanyId)) {
                return $sessionCompanyId;
            }
        }

        // Priority 3: First company the user belongs to
        if ($user->companies()->exists()) {
            $firstCompany = $user->companies()->first();

            // Store in session for future requests
            session(['current_company_id' => $firstCompany->id]);

            return $firstCompany->id;
        }

        return null;
    }

    private function loadCompanyData(): void
    {
        if (!$this->companyId) {
            return;
        }

        $cacheKey = "company.{$this->companyId}";

        // Try to get from cache first
        $this->company = Cache::remember($cacheKey, 3600, function () {
            return Company::find($this->companyId);
        });

        if ($this->company) {
            $this->settings = $this->company->settings ?? [];
        }
    }

    private function userHasAccessToCompany(User $user, int $companyId): bool
    {
        // Direct company ownership
        if (isset($user->company_id) && $user->company_id == $companyId) {
            return true;
        }

        // Access through CompanyUser relationship
        /** @var \App\Models\User $user */
        return $user->companies()->where('companies.id', $companyId)->exists();
    }
}
