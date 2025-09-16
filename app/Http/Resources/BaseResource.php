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
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            ...$this->getAttributes($request)
        ];
    }

    /**
     * Get the specific attributes for each resource.
     * This method should be implemented by child classes.
     */
    abstract protected function getAttributes(Request $request): array;

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
