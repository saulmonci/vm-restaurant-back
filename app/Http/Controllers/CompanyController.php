<?php

namespace App\Http\Controllers;

use App\Repositories\CompanyRepository;
use App\Http\Resources\CompanyResource;
use App\Facades\CurrentCompany;
use App\Facades\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
