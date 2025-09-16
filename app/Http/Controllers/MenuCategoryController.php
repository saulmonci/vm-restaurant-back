<?php

namespace App\Http\Controllers;

use App\Repositories\MenuCategoryRepository;
use App\Http\Resources\MenuCategoryResource;

class MenuCategoryController extends CRUDController
{
    protected $resourceClass = MenuCategoryResource::class;

    public function __construct(MenuCategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Define las relaciones a cargar para MenuCategory
     */
    protected function getEagerLoadRelations(): array
    {
        return ['company']; // Cargar información de la empresa
    }

    /**
     * Relaciones específicas para el método index (lista)
     */
    protected function getIndexEagerLoadRelations(): array
    {
        return ['company']; // Solo empresa en el listado
    }

    /**
     * Relaciones específicas para el método show (detalle)
     */
    protected function getShowEagerLoadRelations(): array
    {
        return ['company', 'menuItems']; // Incluir items del menú en detalle
    }
}
