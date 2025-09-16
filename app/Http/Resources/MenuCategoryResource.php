<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MenuCategoryResource extends BaseResource
{
    /**
     * Formatter principal que maneja toda la lógica de formateo
     */
    protected function formatter($item, Request $request, array $options = []): array
    {
        $formatted = [
            // Campos base de la MenuCategory
            'name' => $item->name,
            'description' => $item->description,
            'is_active' => $item->is_active ?? true,
            'sort_order' => $item->sort_order ?? 0,
        ];

        // Formatear empresa si está cargada
        if ($item->relationLoaded('company') && $item->company) {
            $company = $item->company;

            $formatted['company'] = [
                'id' => $company->id,
                'name' => $company->name,
                'is_active' => $company->is_active ?? true,
            ];
        }

        // Formatear items del menú si están cargados
        if ($item->relationLoaded('menuItems')) {
            $menuItems = $item->menuItems;

            $formatted['menu_items'] = $options['include_menu_items'] ?? false
                ? MenuItemResource::collection($menuItems)
                : null;

            $formatted['menu_items_count'] = $menuItems->count();
            $formatted['available_items_count'] = $menuItems->where('is_available', true)->count();

            // Estadísticas de precios si se solicita
            if ($options['include_price_stats'] ?? false) {
                $formatted['price_stats'] = $this->getPriceStats($menuItems);
            }
        } else {
            $formatted['menu_items'] = null;
            $formatted['menu_items_count'] = null;
        }

        // Estado de disponibilidad de la categoría
        $formatted['availability_info'] = $this->getAvailabilityInfo($item, $options);

        // Permisos del usuario
        if ($options['include_permissions'] ?? false) {
            $formatted['permissions'] = $this->getCategoryPermissions($item, $request, $options);
        }

        // Información adicional si se solicita detalle
        if ($options['detailed'] ?? false) {
            $formatted['additional_info'] = $this->getAdditionalInfo($item, $options);
        }

        return $formatted;
    }

    /**
     * Obtener estadísticas de precios de los items
     */
    protected function getPriceStats($menuItems): array
    {
        if ($menuItems->isEmpty()) {
            return [
                'min_price' => 0,
                'max_price' => 0,
                'avg_price' => 0,
                'items_count' => 0,
            ];
        }

        $prices = $menuItems->pluck('price')->filter();

        return [
            'min_price' => $prices->min(),
            'max_price' => $prices->max(),
            'avg_price' => round($prices->avg(), 2),
            'items_count' => $prices->count(),
        ];
    }

    /**
     * Obtener información de disponibilidad
     */
    protected function getAvailabilityInfo($category, array $options = []): array
    {
        $isActive = $category->is_active ?? true;
        $hasAvailableItems = false;
        $companyIsOpen = true;

        // Verificar si tiene items disponibles
        if ($category->relationLoaded('menuItems')) {
            $hasAvailableItems = $category->menuItems->where('is_available', true)->count() > 0;
        }

        // Verificar si la empresa está abierta
        if ($category->relationLoaded('company') && $category->company) {
            $companyIsOpen = $this->isCompanyOpen($category->company, $options);
        }

        $canOrder = $isActive && $hasAvailableItems && $companyIsOpen;

        return [
            'is_active' => $isActive,
            'has_available_items' => $hasAvailableItems,
            'company_is_open' => $companyIsOpen,
            'can_order' => $canOrder,
            'status_message' => $this->getStatusMessage($isActive, $hasAvailableItems, $companyIsOpen),
        ];
    }

    /**
     * Obtener permisos del usuario para esta categoría
     */
    protected function getCategoryPermissions($category, Request $request, array $options = []): array
    {
        $user = $request->user();

        if (!$user) {
            return [
                'can_edit' => false,
                'can_delete' => false,
                'can_add_items' => false,
                'can_reorder' => false,
            ];
        }

        $canManage = false;

        // Verificar si el usuario puede manejar esta categoría
        if ($category->relationLoaded('company') && $category->company) {
            $canManage = $user->companies()
                ->where('company_id', $category->company_id)
                ->exists();
        }

        $isAdmin = $canManage && $user->hasRole('admin');

        return [
            'can_edit' => $canManage,
            'can_delete' => $isAdmin,
            'can_add_items' => $canManage,
            'can_reorder' => $canManage,
        ];
    }

    /**
     * Obtener información adicional
     */
    protected function getAdditionalInfo($category, array $options = []): array
    {
        return [
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
            'last_item_added' => $this->getLastItemAdded($category),
            'popularity_score' => $this->calculatePopularity($category),
        ];
    }

    /**
     * Verificar si la empresa está abierta
     */
    protected function isCompanyOpen($company, array $options = []): bool
    {
        $timezone = $options['timezone'] ?? 'UTC';
        $currentHour = now($timezone)->hour;
        $openHour = $company->open_hour ?? 9;
        $closeHour = $company->close_hour ?? 22;

        return $currentHour >= $openHour && $currentHour <= $closeHour;
    }

    /**
     * Obtener mensaje de estado
     */
    protected function getStatusMessage(bool $isActive, bool $hasItems, bool $companyOpen): ?string
    {
        if (!$isActive) {
            return 'Categoría deshabilitada';
        }

        if (!$hasItems) {
            return 'Sin items disponibles';
        }

        if (!$companyOpen) {
            return 'Restaurante cerrado';
        }

        return null;
    }

    /**
     * Obtener último item agregado
     */
    protected function getLastItemAdded($category): ?string
    {
        if (!$category->relationLoaded('menuItems')) {
            return null;
        }

        $lastItem = $category->menuItems->sortByDesc('created_at')->first();
        return $lastItem ? $lastItem->created_at->toISOString() : null;
    }

    /**
     * Calcular puntuación de popularidad
     */
    protected function calculatePopularity($category): int
    {
        // Aquí podrías implementar lógica real basada en órdenes, views, etc.
        return rand(1, 100);
    }
}
