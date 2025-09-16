<?php

namespace App\Http\Controllers;

use App\Repositories\MenuItemRepository;
use App\Http\Resources\MenuItemResource;

class MenuItemController extends CRUDController
{
    protected $resourceClass = MenuItemResource::class;

    public function __construct(MenuItemRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Define las relaciones a cargar para MenuItem
     */
    protected function getEagerLoadRelations(): array
    {
        return ['category']; // Cargar categoría del item
    }

    /**
     * Relaciones específicas para el método index (lista)
     */
    protected function getIndexEagerLoadRelations(): array
    {
        return ['category.company']; // Cargar categoría y empresa
    }

    /**
     * Relaciones específicas para el método show (detalle)
     */
    protected function getShowEagerLoadRelations(): array
    {
        return ['category.company']; // Cargar categoría y empresa para detalle
    }
}
