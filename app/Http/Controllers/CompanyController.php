<?php

namespace App\Http\Controllers;

use App\Repositories\CompanyRepository;
use App\Http\Resources\CompanyResource;
use App\Services\CompanyContextService;
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
        $company = CompanyContextService::getCurrentCompany();

        if (!$company) {
            return response()->json(['error' => 'No company context found'], 404);
        }

        return response()->json([
            'company' => new CompanyResource($company),
            'settings' => CompanyContextService::getCompanySettings()
        ]);
    }

    /**
     * Get all companies the user has access to
     */
    public function userCompanies(): JsonResponse
    {
        $companies = CompanyContextService::getUserCompanies();
        $currentCompanyId = CompanyContextService::getCurrentCompanyId();

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

        $success = CompanyContextService::switchCompany($request->company_id);

        if (!$success) {
            return response()->json([
                'error' => 'You do not have access to this company'
            ], 403);
        }

        $newCompany = CompanyContextService::getCurrentCompany();

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

        $success = CompanyContextService::updateCompanySettings($request->settings);

        if (!$success) {
            return response()->json(['error' => 'Failed to update settings'], 500);
        }

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => CompanyContextService::getCompanySettings()
        ]);
    }

    /**
     * Get company analytics/dashboard data
     */
    public function analytics(Request $request): JsonResponse
    {
        $company = CompanyContextService::getCurrentCompany();

        if (!$company) {
            return response()->json(['error' => 'No company context found'], 404);
        }

        $period = $request->get('period', '30days');

        // Basic analytics - this can be expanded significantly
        $analytics = [
            'company_id' => $company->id,
            'period' => $period,
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
}
