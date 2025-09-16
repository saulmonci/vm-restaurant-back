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
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
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
                $q->where('available', true);
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
                            'vegetarian' => $q->where('vegetarian', true),
                            'vegan' => $q->where('vegan', true),
                            'gluten_free' => $q->where('gluten_free', true),
                            'spicy' => $q->where('spicy', true),
                            default => null
                        };
                    }
                });
            }
        }
    }
}
