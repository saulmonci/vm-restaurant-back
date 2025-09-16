<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CompanyResource extends BaseResource
{
    /**
     * Get the specific attributes for Company resource.
     */
    protected function getAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            // Solo incluir relaciones si están cargadas para evitar N+1
            'users' => $this->includeRelation('users'), // Sin resource class por ahora
            'menu_categories' => $this->includeRelation('menuCategories'),
            'users_count' => $this->isRelationLoaded('users') ? $this->users->count() : null,
        ];
    }
}
