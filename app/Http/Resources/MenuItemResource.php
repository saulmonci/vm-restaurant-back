<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MenuItemResource extends BaseResource
{
    /**
     * Get the specific attributes for MenuItem resource.
     */
    protected function getAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category_id' => $this->category_id,
            // Solo incluir relaciones si están cargadas para evitar N+1
            'category' => $this->includeRelation('category'),
            'company' => $this->includeRelation('category.company'), // Relación anidada
        ];
    }
}
