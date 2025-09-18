<?php

namespace App\Http\Controllers;

use App\Repositories\MenuCategoryRepository;
use App\Http\Resources\MenuCategoryResource;
use App\Http\Controllers\CRUDController;
use App\Facades\CurrentCompany;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuCategoryController extends CRUDController
{
    protected $resourceClass = MenuCategoryResource::class;

    public function __construct(MenuCategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Define las relaciones a cargar para MenuCategory
     */
    protected function getEagerLoadRelations(): array
    {
        return ['company']; // Cargar información de la empresa
    }

    /**
     * Relaciones específicas para el método index (lista)
     */
    protected function getIndexEagerLoadRelations(): array
    {
        return ['company']; // Solo empresa en el listado
    }

    /**
     * Relaciones específicas para el método show (detalle)
     */
    protected function getShowEagerLoadRelations(): array
    {
        return ['company', 'menuItems']; // Incluir items del menú en detalle
    }

    /**
     * Debug method to check what categories are being returned
     */
    public function debug(Request $request): JsonResponse
    {
        // Force CurrentCompany initialization
        CurrentCompany::initialize();

        // Get all categories without any scoping
        $allCategories = \App\Models\MenuCategory::with('company')->get()->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'company_id' => $cat->company_id,
                'company_name' => $cat->company ? $cat->company->name : null,
            ];
        });

        // Get categories through repository (should be scoped)
        $scopedCategories = $this->repository->all(['company'])->map(function ($cat) {
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'company_id' => $cat->company_id,
                'company_name' => $cat->company ? $cat->company->name : null,
            ];
        });

        return response()->json([
            'session_company_id' => session('current_company_id'),
            'current_company_id' => CurrentCompany::id(),
            'current_company_name' => CurrentCompany::exists() ? CurrentCompany::get()->name : null,
            'current_company_exists' => CurrentCompany::exists(),
            'middleware_applied' => request()->has('current_company_id'),
            'request_company_id' => request('current_company_id'),
            'all_categories_in_db' => $allCategories,
            'scoped_categories_from_repository' => $scopedCategories,
            'scoped_count' => $scopedCategories->count(),
        ]);
    }
}
