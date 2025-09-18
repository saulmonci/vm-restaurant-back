<?php

namespace App\Repositories;

use App\Models\MenuCategory;
use Illuminate\Http\Request;

class MenuCategoryRepository extends BaseRepository
{
    public function __construct(MenuCategory $model)
    {
        parent::__construct($model);
    }

    /**
     * Apply specific filters for MenuCategory
     */
    protected function applySpecificFilters($query, Request $request)
    {
        // Filtro por estado activo
        if ($request->has('active') || $request->has('is_active')) {
            $isActive = $request->boolean('active') ?: $request->boolean('is_active');
            $query->where('is_active', $isActive);
        }

        // Filtros jerárquicos
        if ($request->has('main_only')) {
            // Solo categorías principales (sin padre)
            $query->whereNull('parent_id');
        }

        if ($request->has('subcategories_only')) {
            // Solo subcategorías (con padre)
            $query->whereNotNull('parent_id');
        }

        if ($request->has('parent_id')) {
            if ($request->string('parent_id') === 'null') {
                // Categorías principales
                $query->whereNull('parent_id');
            } else {
                // Subcategorías de un padre específico
                $parentIds = collect(explode(',', $request->string('parent_id')))
                    ->map(fn($id) => (int) trim($id))
                    ->filter()
                    ->toArray();

                if (!empty($parentIds)) {
                    $query->whereIn('parent_id', $parentIds);
                }
            }
        }

        if ($request->has('with_children')) {
            // Solo categorías que tienen subcategorías
            $query->whereHas('children');
        }

        if ($request->has('level')) {
            $level = (int) $request->get('level');
            if ($level === 0) {
                $query->whereNull('parent_id');
            } else {
                // Para niveles específicos, necesitamos una consulta más compleja
                $query->whereHas('parent', function ($q) use ($level) {
                    $this->applyLevelFilter($q, $level - 1);
                });
            }
        }

        // Filtro por empresa específica
        if ($request->has('company_id')) {
            $companyIds = collect(explode(',', $request->string('company_id')))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->toArray();

            if (!empty($companyIds)) {
                $query->whereIn('company_id', $companyIds);
            }
        }

        // Filtro por nombre de categoría
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->string('name') . '%');
        }

        // Filtro por categorías que tienen items disponibles
        if ($request->has('has_available_items')) {
            $query->whereHas('menuItems', function ($q) {
                $q->where('is_available', true);
            });
        }

        // Filtro por categorías con items en rango de precio
        if ($request->has('price_range')) {
            $priceRange = $request->string('price_range');

            $query->whereHas('menuItems', function ($q) use ($priceRange) {
                match ($priceRange) {
                    'budget' => $q->where('price', '<=', 15),
                    'medium' => $q->whereBetween('price', [15, 30]),
                    'premium' => $q->where('price', '>=', 30),
                    default => null
                };
            });
        }

        // Filtro por categorías con opciones dietéticas específicas
        if ($request->has('dietary_options')) {
            $options = collect(explode(',', $request->string('dietary_options')))
                ->map(fn($option) => trim($option))
                ->filter()
                ->toArray();

            if (!empty($options)) {
                $query->whereHas('menuItems', function ($q) use ($options) {
                    foreach ($options as $option) {
                        match ($option) {
                            'vegetarian' => $q->where('is_vegetarian', true),
                            'vegan' => $q->where('is_vegan', true),
                            'gluten_free' => $q->where('is_gluten_free', true),
                            'spicy' => $q->where('spice_level', '>', 0),
                            default => null
                        };
                    }
                });
            }
        }

        // Ordenamiento por defecto considerando jerarquía
        if (!$request->has('sort')) {
            $query->orderBy('parent_id', 'asc')
                ->orderBy('sort_order', 'asc')
                ->orderBy('name', 'asc');
        }
    }

    /**
     * Método helper para aplicar filtros de nivel
     */
    private function applyLevelFilter($query, int $targetLevel)
    {
        if ($targetLevel === 0) {
            $query->whereNull('parent_id');
        } else {
            $query->whereHas('parent', function ($q) use ($targetLevel) {
                $this->applyLevelFilter($q, $targetLevel - 1);
            });
        }
    }

    /**
     * Obtener árbol jerárquico de categorías
     */
    public function getHierarchicalTree($companyId = null)
    {
        $query = $this->model->with(['children.children.children'])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * Obtener categorías principales con conteo de subcategorías
     */
    public function getMainCategoriesWithCount($companyId = null)
    {
        $query = $this->model->withCount(['children' => function ($q) {
            $q->where('is_active', true);
        }])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }

    /**
     * Obtener subcategorías de una categoría padre
     */
    public function getSubcategories($parentId, $companyId = null)
    {
        $query = $this->model->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
