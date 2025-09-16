<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MenuItemResource extends BaseResource
{
    /**
     * Formatter principal que maneja toda la lógica de formateo
     */
    protected function formatter($item, Request $request, array $options = []): array
    {
        $formatted = [
            // Campos base del MenuItem
            'name' => $item->name,
            'description' => $item->description,
            'price' => $item->price,
            'category_id' => $item->category_id,
        ];

        // Formatear categoría si está cargada
        if ($item->relationLoaded('category') && $item->category) {
            $category = $item->category;

            $formatted['category'] = [
                'id' => $category->id,
                'name' => $category->name,
                'is_active' => $category->is_active ?? true,
                'can_order' => $category->is_active ?? true,
            ];

            // Si la categoría no está activa, agregar mensaje
            if (!($category->is_active ?? true)) {
                $formatted['category']['status_message'] = 'Categoría temporalmente deshabilitada';
            }

            // Formatear empresa si está cargada dentro de la categoría
            if ($category->relationLoaded('company') && $category->company) {
                $company = $category->company;
                $isOpen = $this->isCompanyOpen($company, $options);
                $canDeliver = $this->canCompanyDeliver($company, $options);

                $formatted['company'] = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'is_open' => $isOpen,
                    'can_deliver' => $canDeliver,
                    'status' => $this->getCompanyStatus($company, $isOpen, $canDeliver),
                ];

                // Agregar tiempo de entrega solo si puede entregar
                if ($canDeliver) {
                    $formatted['company']['delivery_time'] = $company->estimated_delivery_time ?? 30;
                }
            }
        }

        // Formatear información de precios si se solicita
        if ($options['include_pricing'] ?? true) {
            $formatted['pricing_info'] = [
                'base_price' => $item->price,
                'final_price' => $item->price,
                'currency' => $options['currency'] ?? 'USD',
                'has_discount' => false,
            ];

            // Aplicar descuentos si hay empresa cargada
            if (
                isset($formatted['company']) &&
                $item->category->company->has_happy_hour ?? false
            ) {

                $formatted['pricing_info']['final_price'] = $item->price * 0.9;
                $formatted['pricing_info']['has_discount'] = true;
                $formatted['pricing_info']['discount_reason'] = 'Happy Hour';
                $formatted['pricing_info']['discount_percentage'] = 10;
            }
        }

        // Formatear permisos si se solicita
        if ($options['include_permissions'] ?? false) {
            $user = $request->user();

            $formatted['permissions'] = [
                'can_edit' => false,
                'can_delete' => false,
                'can_order' => true,
            ];

            if ($user && isset($formatted['company'])) {
                $companyId = $item->category->company_id;

                // Verificar si el usuario pertenece a la empresa
                $belongsToCompany = $user->companies()
                    ->where('company_id', $companyId)
                    ->exists();

                $formatted['permissions']['can_edit'] = $belongsToCompany;
                $formatted['permissions']['can_delete'] = $belongsToCompany && $user->hasRole('admin');
            }
        }

        // Calcular disponibilidad general
        $formatted['is_available'] = $this->calculateAvailability($item, $formatted, $options);

        // Agregar información detallada si se solicita
        if ($options['detailed'] ?? false) {
            $formatted['additional_info'] = $this->getAdditionalInfo($item, $options);
        }

        return $formatted;
    }

    /**
     * Calcular disponibilidad del item basado en todas las condiciones
     */
    protected function calculateAvailability($item, array $formatted, array $options): bool
    {
        $isAvailable = $item->is_available ?? true;

        // Verificar categoría
        if (isset($formatted['category'])) {
            $isAvailable = $isAvailable && $formatted['category']['can_order'];
        }

        // Verificar empresa
        if (isset($formatted['company'])) {
            $isAvailable = $isAvailable && $formatted['company']['is_open'];
        }

        return $isAvailable;
    }

    /**
     * Obtener información adicional cuando se solicite modo detallado
     */
    protected function getAdditionalInfo($item, array $options): array
    {
        return [
            'last_updated' => $item->updated_at,
            'popularity_score' => $this->calculatePopularity($item),
            'nutritional_info' => $item->nutritional_info ?? null,
        ];
    }

    /**
     * Calcular puntuación de popularidad (ejemplo)
     */
    protected function calculatePopularity($item): int
    {
        // Lógica ejemplo para calcular popularidad
        return rand(1, 100); // En realidad usarías datos reales de orders, etc.
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
     * Verificar si la empresa puede entregar
     */
    protected function canCompanyDeliver($company, array $options = []): bool
    {
        $baseCanDeliver = ($company->delivery_enabled ?? true) &&
            $this->isCompanyOpen($company, $options);

        // Validación adicional basada en ubicación del usuario
        if (isset($options['user_location']) && $company->delivery_radius) {
            // Aquí podrías agregar lógica de distancia
            // return $baseCanDeliver && $this->isWithinDeliveryRadius($options['user_location'], $company);
        }

        return $baseCanDeliver;
    }

    /**
     * Obtener estado de la empresa
     */
    protected function getCompanyStatus($company, bool $isOpen, bool $canDeliver): string
    {
        if (!$isOpen) {
            return 'closed';
        }

        if (!$canDeliver) {
            return 'no_delivery';
        }

        return 'available';
    }
}
