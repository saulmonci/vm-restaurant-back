<?php

namespace App\Repositories;

use App\Models\MenuItem;

class MenuItemRepository extends BaseRepository
{
    public function __construct(MenuItem $model)
    {
        parent::__construct($model);
    }
    // Métodos específicos para MenuItem aquí
}
