<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use App\Models\Company;

class CompanyScopedMiddleware
{
    /**
     * Handle an incoming request.
     * Automatically scope all queries to the current user's company.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Get company ID from user (assuming user belongs to a company)
            $companyId = $this->getUserCompanyId($user);

            if ($companyId) {
                // Apply global scope to models that belong to companies
                $this->applyCompanyScopes($companyId);

                // Store company ID in request for easy access
                $request->merge(['current_company_id' => $companyId]);
            }
        }

        return $next($request);
    }

    /**
     * Get the company ID for the current user
     */
    private function getUserCompanyId($user)
    {
        // Option 1: User directly belongs to a company
        if (isset($user->company_id)) {
            return $user->company_id;
        }

        // Option 2: User has a relationship with company through CompanyUser
        if ($user->companies()->exists()) {
            return $user->companies()->first()->id;
        }

        // Option 3: Check for current_company_id in session or user preferences
        if (session()->has('current_company_id')) {
            return session('current_company_id');
        }

        return null;
    }

    /**
     * Apply company scopes to relevant models
     */
    private function applyCompanyScopes($companyId)
    {
        // Scope MenuItems to current company (through category relationship)
        MenuItem::addGlobalScope('company', function (Builder $builder) use ($companyId) {
            $builder->whereHas('category', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            });
        });

        // Scope MenuCategories to current company
        MenuCategory::addGlobalScope('company', function (Builder $builder) use ($companyId) {
            $builder->where('company_id', $companyId);
        });

        // Note: Company model itself should not be scoped
        // as users might need to see other companies for partnerships, etc.
    }
}
