<?php

namespace App\Http\Controllers;

use App\Repositories\CompanyRepository;
use App\Http\Resources\CompanyResource;
use App\Facades\CurrentCompany;
use App\Facades\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CompanyController extends CRUDController
{
    protected $resourceClass = CompanyResource::class;

    public function __construct(CompanyRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Define las relaciones a cargar para Company
     */
    protected function getEagerLoadRelations(): array
    {
        return ['users']; // Ejemplo: cargar usuarios de la compañía
    }

    /**
     * Relaciones específicas para el método index
     */
    protected function getIndexEagerLoadRelations(): array
    {
        return ['users']; // Solo usuarios en el listado
    }

    /**
     * Relaciones específicas para el método show
     */
    protected function getShowEagerLoadRelations(): array
    {
        return ['users', 'menuCategories', 'menuCategories.menuItems']; // Más detalle en show
    }

    // =================== Company Context Methods ===================

    /**
     * Debug endpoint to check company context
     */
    public function debug(): JsonResponse
    {
        $user = Auth::user();

        // Debug user companies relationship
        $userCompanies = $user ? $user->companies : collect();
        $companiesData = $userCompanies->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
            ];
        });

        // Force CurrentCompany initialization multiple times
        CurrentCompany::initialize();

        // If still no company, try to force set first company
        if (!CurrentCompany::exists() && $userCompanies->count() > 0) {
            $firstCompany = $userCompanies->first();
            $cacheKey = "user_current_company.{$user->id}";

            // Manually set cache and session
            \Illuminate\Support\Facades\Cache::put($cacheKey, $firstCompany->id, now()->addDays(30));
            session(['current_company_id' => $firstCompany->id]);

            // Re-initialize
            CurrentCompany::initialize();
        }

        return response()->json([
            'authenticated' => Auth::check(),
            'user_id' => $user ? $user->id : null,
            'user_company_id' => $user ? $user->company_id : null,
            'user_companies_from_relationship' => $companiesData,
            'user_companies_count' => $userCompanies->count(),
            'session_company_id' => session('current_company_id'),
            'cache_company_id' => $user ? \Illuminate\Support\Facades\Cache::get("user_current_company.{$user->id}") : null,
            'current_company_exists' => CurrentCompany::exists(),
            'current_company_id' => CurrentCompany::id(),
            'current_company' => CurrentCompany::exists() ? CurrentCompany::get()->only(['id', 'name']) : null,
            'middleware_applied' => request()->has('current_company_id'),
            'request_company_id' => request('current_company_id'),
            'forced_setup' => !CurrentCompany::exists() && $userCompanies->count() > 0,
        ]);
    }

    /**
     * Force set company for testing - enhanced version
     */
    public function forceSetCompany(Request $request): JsonResponse
    {
        $user = Auth::user();
        $companyId = $request->input('company_id', 8); // Default to company 8

        // Verify user has access to this company
        $userCompanies = $user->companies;
        $hasAccess = $userCompanies->contains('id', $companyId);

        if (!$hasAccess) {
            return response()->json([
                'error' => 'User does not have access to company ' . $companyId,
                'user_companies' => $userCompanies->pluck('id')->toArray(),
            ], 403);
        }

        // Set in cache manually
        $cacheKey = "user_current_company.{$user->id}";
        \Illuminate\Support\Facades\Cache::put($cacheKey, $companyId, now()->addDays(30));

        // Set in session manually
        session(['current_company_id' => $companyId]);
        session()->save();

        // Try to force CurrentCompany to reinitialize
        // Clear any internal state first
        CurrentCompany::initialize();

        return response()->json([
            'message' => 'Company set successfully',
            'company_id' => $companyId,
            'user_has_access' => $hasAccess,
            'cache_key' => $cacheKey,
            'cache_value' => \Illuminate\Support\Facades\Cache::get($cacheKey),
            'session_company_id' => session('current_company_id'),
            'current_company_exists_after' => CurrentCompany::exists(),
            'current_company_id_after' => CurrentCompany::id(),
        ]);
    }

    /**
     * Debug CurrentCompany service step by step
     */
    public function debugCurrentCompany(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Debug step by step
        $debug = [];

        if ($user) {
            $debug['user_authenticated'] = true;
            $debug['user_id'] = $user->id;
            $debug['user_direct_company_id'] = $user->company_id ?? null;

            // Check session
            $debug['session_has_current_company_id'] = session()->has('current_company_id');
            $debug['session_current_company_id'] = session('current_company_id');

            // Debug companies relationship
            $userCompanies = $user->companies;
            $debug['user_companies_via_relationship'] = $userCompanies->pluck('id')->toArray();
            $debug['companies_count'] = $userCompanies->count();

            if (session()->has('current_company_id')) {
                $sessionCompanyId = session('current_company_id');
                $debug['session_company_id'] = $sessionCompanyId;
                $debug['user_direct_access'] = isset($user->company_id) && $user->company_id == $sessionCompanyId;

                // Check if session company is in user's companies
                $debug['session_company_in_user_companies'] = $userCompanies->contains('id', $sessionCompanyId);
            }
        }

        return response()->json($debug);
    }

    /**
     * Get current company info
     */
    public function current(): JsonResponse
    {
        $company = CurrentCompany::get();

        if (!$company) {
            return response()->json(['error' => 'No company context found'], 404);
        }

        return response()->json([
            'company' => new CompanyResource($company),
            'settings' => CurrentCompany::settings()
        ]);
    }

    /**
     * Get all companies the user has access to
     */
    public function userCompanies(): JsonResponse
    {
        $companies = CurrentCompany::getUserCompanies();
        $currentCompanyId = CurrentCompany::id();

        return response()->json([
            'companies' => CompanyResource::collection($companies),
            'current_company_id' => $currentCompanyId
        ]);
    }

    /**
     * Switch to a different company
     */
    public function switchCompany(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id'
        ]);

        $success = CurrentCompany::switchTo($request->company_id);

        if (!$success) {
            return response()->json([
                'error' => 'You do not have access to this company'
            ], 403);
        }

        $newCompany = CurrentCompany::get();

        return response()->json([
            'message' => 'Company switched successfully',
            'company' => new CompanyResource($newCompany)
        ]);
    }

    /**
     * Switch to a different company by slug (for development)
     */
    public function switchCompanyBySlug(Request $request): JsonResponse
    {
        $request->validate([
            'slug' => 'required|string|exists:companies,slug'
        ]);

        $success = CurrentCompany::switchToBySlug($request->slug);

        if (!$success) {
            return response()->json([
                'error' => 'You do not have access to this company or company not found'
            ], 403);
        }

        $newCompany = CurrentCompany::get();

        return response()->json([
            'message' => 'Company switched successfully',
            'company' => new CompanyResource($newCompany)
        ]);
    }

    /**
     * Get list of companies with slugs for development purposes
     */
    public function getCompaniesForDevelopment(): JsonResponse
    {
        $companies = $this->repository->getModel()
            ->select(['id', 'name', 'slug', 'is_active'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'is_active' => $company->is_active
                ];
            })
        ]);
    }

    /**
     * Update company settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array'
        ]);

        $success = CurrentCompany::updateSettings($request->settings);

        if (!$success) {
            return response()->json(['error' => 'Failed to update settings'], 500);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => CurrentCompany::settings()
        ]);
    }

    /**
     * Get company analytics/dashboard data
     */
    public function analytics(Request $request): JsonResponse
    {
        $company = CurrentCompany::get();

        if (!$company) {
            return response()->json(['error' => 'No company context found'], 404);
        }

        $period = $request->get('period', '30days');

        // Basic analytics - this can be expanded significantly
        $analytics = [
            'company_id' => $company->id,
            'period' => $period,
            'user_info' => [
                'user_id' => CurrentUser::id(),
                'user_name' => CurrentUser::name(),
                'user_timezone' => CurrentUser::timezone(),
                'is_admin' => CurrentUser::isAdmin(),
            ],
            'metrics' => [
                'total_menu_items' => $company->menuCategories()->withCount('menuItems')->get()->sum('menu_items_count'),
                'total_categories' => $company->menuCategories()->count(),
                'active_items' => $company->menuCategories()
                    ->whereHas('menuItems', function ($q) {
                        $q->where('available', true);
                    })->count(),
                'last_updated' => now()
            ]
        ];

        return response()->json($analytics);
    }

    /**
     * Get current user profile within company context
     */
    public function userProfile(): JsonResponse
    {
        if (!CurrentUser::check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $profile = [
            'user' => [
                'id' => CurrentUser::id(),
                'name' => CurrentUser::name(),
                'email' => CurrentUser::email(),
                'timezone' => CurrentUser::timezone(),
                'language' => CurrentUser::language(),
                'currency' => CurrentUser::currency(),
                'is_admin' => CurrentUser::isAdmin(),
                'is_active' => CurrentUser::isActive(),
                'preferences' => CurrentUser::preferences(),
            ],
            'company' => [
                'id' => CurrentCompany::id(),
                'name' => CurrentCompany::get()?->name,
                'settings' => CurrentCompany::settings(),
            ],
            'companies_access' => CurrentUser::companies()->count(),
        ];

        return response()->json($profile);
    }

    /**
     * Update user preferences
     */
    public function updateUserPreferences(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array'
        ]);

        if (!CurrentUser::check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $success = CurrentUser::updatePreferences($request->preferences);

        if (!$success) {
            return response()->json(['error' => 'Failed to update preferences'], 500);
        }

        return response()->json([
            'message' => 'User preferences updated successfully',
            'preferences' => CurrentUser::preferences()
        ]);
    }
}
