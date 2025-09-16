<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use App\Facades\CurrentCompany;

class CompanyScopedMiddleware
{
    /**
     * Handle an incoming request.
     * Automatically scope all queries to the current user's company.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Initialize CurrentCompany using Facade
            CurrentCompany::initialize();
            
            $companyId = CurrentCompany::id();
            
            if ($companyId) {
                // Apply global scope to models that belong to companies
                $this->applyCompanyScopes($companyId);
                
                // Store company ID in request for easy access
                $request->merge(['current_company_id' => $companyId]);
            }
        }

        return $next($request);
    }    /**
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
