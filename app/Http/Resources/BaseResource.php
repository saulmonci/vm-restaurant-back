<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $options = $this->buildOptionsFromRequest($request, [
            'timezone' => $request->get('timezone', 'UTC'),
        ]);

        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            ...$this->formatter($this->resource, $request, $options),
        ];
    }

    /**
     * Formatter method that should be implemented by child classes.
     * This method handles all the custom formatting logic.
     */
    protected function formatter($item, Request $request, array $options = []): array
    {
        return [];
    }

    /**
     * Check if a relationship is loaded to avoid N+1 queries.
     */
    protected function isRelationLoaded(string $relation): bool
    {
        return $this->resource->relationLoaded($relation);
    }

    /**
     * Get relationship data only if it's loaded.
     */
    protected function getLoadedRelation(string $relation): mixed
    {
        if ($this->isRelationLoaded($relation)) {
            return $this->resource->getRelation($relation);
        }
        return null;
    }

    /**
     * Include relationship data only if it's loaded with optional resource transformation.
     */
    protected function includeRelation(string $relation, string $resourceClass = null): mixed
    {
        if (!$this->isRelationLoaded($relation)) {
            return null;
        }

        $relationData = $this->resource->getRelation($relation);

        if ($resourceClass && $relationData) {
            if ($relationData instanceof \Illuminate\Database\Eloquent\Collection) {
                return $resourceClass::collection($relationData);
            }
            return new $resourceClass($relationData);
        }

        return $relationData;
    }

    /**
     * Validar múltiples relaciones en cadena
     */
    protected function areRelationsLoaded(array $relations): bool
    {
        foreach ($relations as $relation) {
            if (!$this->isRelationNestedLoaded($relation)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verificar si una relación anidada está cargada (ej: 'category.company')
     */
    protected function isRelationNestedLoaded(string $relation): bool
    {
        $parts = explode('.', $relation);
        $current = $this->resource;

        foreach ($parts as $part) {
            if (!$current->relationLoaded($part)) {
                return false;
            }
            $current = $current->getRelation($part);

            if (!$current) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener relación anidada si está cargada
     */
    protected function getNestedRelation(string $relation): mixed
    {
        if (!$this->isRelationNestedLoaded($relation)) {
            return null;
        }

        $parts = explode('.', $relation);
        $current = $this->resource;

        foreach ($parts as $part) {
            $current = $current->getRelation($part);
        }

        return $current;
    }

    /**
     * Incluir relación con validación personalizada
     */
    protected function includeRelationWhen(string $relation, callable $condition, callable $transform = null): mixed
    {
        if (!$this->isRelationLoaded($relation)) {
            return null;
        }

        $relationData = $this->resource->getRelation($relation);

        if (!$condition($relationData)) {
            return null;
        }

        if ($transform) {
            return $transform($relationData);
        }

        return $relationData;
    }

    /**
     * Incluir datos condicionales basados en relaciones
     */
    protected function whenRelationLoaded(string $relation, callable $callback, $default = null): mixed
    {
        if ($this->isRelationLoaded($relation)) {
            return $callback($this->resource->getRelation($relation));
        }

        return $default;
    }

    /**
     * Validar y transformar relación con fallback
     */
    protected function includeRelationWithFallback(string $relation, array $fallbackData = []): mixed
    {
        if ($this->isRelationLoaded($relation)) {
            $relationData = $this->resource->getRelation($relation);

            if ($relationData) {
                return $relationData;
            }
        }

        return $fallbackData;
    }

    /**
     * Crear opciones para formatters desde el request
     */
    protected function buildOptionsFromRequest(Request $request, array $defaultOptions = []): array
    {
        return array_merge($defaultOptions, [
            'user' => $request->user(),
            'include_permissions' => $request->has('include_permissions'),
            'include_pricing' => $request->boolean('include_pricing', true),
            'include_availability' => $request->boolean('include_availability', true),
            'currency' => $request->get('currency', 'USD'),
            'user_location' => $request->get('user_location'),
            'detailed' => $request->boolean('detailed', false),
        ]);
    }

    /**
     * Formatter genérico para relaciones
     */
    protected function formatRelation(string $relation, callable $formatter, array $options = []): mixed
    {
        if (!$this->isRelationLoaded($relation)) {
            return null;
        }

        $relationData = $this->resource->getRelation($relation);

        if (!$relationData) {
            return null;
        }

        return $formatter($relationData, $options);
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'status' => 'success',
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Customize the response for a single resource.
     */
    public function withResponse(Request $request, $response): void
    {
        $response->header('X-Resource-Type', class_basename(static::class));
    }
}
