<?php

namespace App\Http\Controllers;

use App\Repositories\CompanyRepository;
use App\Http\Resources\CompanyResource;

class CompanyController extends CRUDController
{
    protected $resourceClass = CompanyResource::class;

    public function __construct(CompanyRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Define las relaciones a cargar para Company
     */
    protected function getEagerLoadRelations(): array
    {
        return ['users']; // Ejemplo: cargar usuarios de la compañía
    }

    /**
     * Relaciones específicas para el método index
     */
    protected function getIndexEagerLoadRelations(): array
    {
        return ['users']; // Solo usuarios en el listado
    }

    /**
     * Relaciones específicas para el método show
     */
    protected function getShowEagerLoadRelations(): array
    {
        return ['users', 'menuCategories', 'menuCategories.menuItems']; // Más detalle en show
    }

    // Puedes sobreescribir métodos aquí si necesitas lógica extra
}
