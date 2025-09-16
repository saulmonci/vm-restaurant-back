<?php

namespace App\Traits;

trait Searchable
{
    /**
     * Get the searchable fields for this model.
     * Override this method in your model to define searchable fields.
     */
    public function getSearchableFields(): array
    {
        return $this->searchable ?? [];
    }
}
