<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\User;

class CompanyContextService
{
    /**
     * Get the current company for the authenticated user
     */
    public static function getCurrentCompany(): ?Company
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();
        $companyId = self::getCurrentCompanyId();

        return $companyId ? Company::find($companyId) : null;
    }

    /**
     * Get the current company ID
     */
    public static function getCurrentCompanyId(): ?int
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Priority 1: User's direct company_id
        if (isset($user->company_id)) {
            return $user->company_id;
        }

        // Priority 2: Session-stored company (for users with multiple companies)
        if (session()->has('current_company_id')) {
            $sessionCompanyId = session('current_company_id');

            // Verify user has access to this company
            if (self::userHasAccessToCompany($user, $sessionCompanyId)) {
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

    /**
     * Switch to a different company (for multi-company users)
     */
    public static function switchCompany(int $companyId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();

        if (self::userHasAccessToCompany($user, $companyId)) {
            session(['current_company_id' => $companyId]);
            return true;
        }

        return false;
    }

    /**
     * Get all companies the user has access to
     */
    public static function getUserCompanies(): \Illuminate\Database\Eloquent\Collection
    {
        if (!Auth::check()) {
            return collect();
        }

        $user = Auth::user();

        // If user has a direct company_id, return that company
        if (isset($user->company_id)) {
            return Company::where('id', $user->company_id)->get();
        }

        // Return all companies the user belongs to through CompanyUser
        return $user->companies;
    }

    /**
     * Check if user has access to a specific company
     */
    public static function userHasAccessToCompany(User $user, int $companyId): bool
    {
        // Direct company ownership
        if (isset($user->company_id) && $user->company_id == $companyId) {
            return true;
        }

        // Access through CompanyUser relationship
        return $user->companies()->where('company_id', $companyId)->exists();
    }

    /**
     * Create a new company and associate with current user
     */
    public static function createCompanyForUser(array $companyData, ?User $user = null): Company
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            throw new \Exception('No authenticated user found');
        }

        $company = Company::create($companyData);

        // Associate user with the company
        $user->companies()->attach($company->id, [
            'role' => 'owner', // or whatever role system you're using
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Set as current company
        session(['current_company_id' => $company->id]);

        return $company;
    }

    /**
     * Get company-specific settings
     */
    public static function getCompanySettings(string $key = null, $default = null)
    {
        $company = self::getCurrentCompany();

        if (!$company) {
            return $default;
        }

        // Assuming you have a settings JSON column or separate settings table
        if (isset($company->settings)) {
            $settings = is_string($company->settings)
                ? json_decode($company->settings, true)
                : $company->settings;

            if ($key) {
                return data_get($settings, $key, $default);
            }

            return $settings;
        }

        return $default;
    }

    /**
     * Update company settings
     */
    public static function updateCompanySettings(array $settings): bool
    {
        $company = self::getCurrentCompany();

        if (!$company) {
            return false;
        }

        $currentSettings = self::getCompanySettings() ?? [];
        $newSettings = array_merge($currentSettings, $settings);

        return $company->update(['settings' => $newSettings]);
    }
}
