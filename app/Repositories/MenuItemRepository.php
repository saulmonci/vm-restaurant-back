<?php

namespace App\Repositories;

use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemRepository extends BaseRepository
{
    public function __construct(MenuItem $model)
    {
        parent::__construct($model);
    }

    /**
     * Apply specific filters for MenuItem
     */
    protected function applySpecificFilters($query, Request $request)
    {
        // Filtros de estado
        if ($request->has('available')) {
            $query->where('available', $request->boolean('available'));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Filtros de precios
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->numeric('min_price'));
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->numeric('max_price'));
        }

        if ($request->has('price_range')) {
            $priceRange = $request->string('price_range');
            match ($priceRange) {
                'budget' => $query->where('price', '<=', 15),
                'medium' => $query->whereBetween('price', [15, 30]),
                'premium' => $query->where('price', '>=', 30),
                default => null
            };
        }

        // Filtros dietéticos
        if ($request->has('vegetarian')) {
            $query->where('vegetarian', $request->boolean('vegetarian'));
        }

        if ($request->has('vegan')) {
            $query->where('vegan', $request->boolean('vegan'));
        }

        if ($request->has('gluten_free')) {
            $query->where('gluten_free', $request->boolean('gluten_free'));
        }

        if ($request->has('spicy')) {
            $query->where('spicy', $request->boolean('spicy'));
        }

        // Filtros de relaciones
        if ($request->has('category_id')) {
            $categoryIds = collect(explode(',', $request->string('category_id')))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->toArray();

            if (!empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if ($request->has('company_id')) {
            $companyIds = collect(explode(',', $request->string('company_id')))
                ->map(fn($id) => (int) trim($id))
                ->filter()
                ->toArray();

            if (!empty($companyIds)) {
                $query->whereHas('category.company', function ($q) use ($companyIds) {
                    $q->whereIn('id', $companyIds);
                });
            }
        }

        // Filtro por categorías activas
        if ($request->has('active_categories_only')) {
            $query->whereHas('category', function ($q) {
                $q->where('active', true);
            });
        }

        // Filtro por empresas activas
        if ($request->has('active_companies_only')) {
            $query->whereHas('category.company', function ($q) {
                $q->where('active', true);
            });
        }

        // Filtro por calificación (si existe el campo)
        if ($request->has('min_rating') && $this->model->hasColumn('rating')) {
            $query->where('rating', '>=', $request->numeric('min_rating'));
        }

        // Filtros de tiempo de preparación (si existe el campo)
        if ($request->has('max_prep_time') && $this->model->hasColumn('preparation_time')) {
            $query->where('preparation_time', '<=', $request->integer('max_prep_time'));
        }

        // Filtro por ingredientes (si existe relación)
        if ($request->has('has_ingredients') && method_exists($this->model, 'ingredients')) {
            $ingredients = collect(explode(',', $request->string('has_ingredients')))
                ->map(fn($ingredient) => trim($ingredient))
                ->filter()
                ->toArray();

            if (!empty($ingredients)) {
                $query->whereHas('ingredients', function ($q) use ($ingredients) {
                    $q->whereIn('name', $ingredients);
                });
            }
        }

        // Filtro por alergenos (si existe campo)
        if ($request->has('without_allergens') && $this->model->hasColumn('allergens')) {
            $allergens = collect(explode(',', $request->string('without_allergens')))
                ->map(fn($allergen) => trim($allergen))
                ->filter()
                ->toArray();

            if (!empty($allergens)) {
                foreach ($allergens as $allergen) {
                    $query->where('allergens', 'not like', '%' . $allergen . '%');
                }
            }
        }
    }
}
