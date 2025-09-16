<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\BaseRepositoryInterface;
use App\Http\Resources\BaseResource;

abstract class CRUDController extends Controller
{
    protected $repository;
    protected $resourceClass;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get the relationships to eager load.
     * Can be overridden by child classes.
     */
    protected function getEagerLoadRelations(): array
    {
        return [];
    }

    /**
     * Get the relationships to eager load for index method.
     * Can be overridden by child classes.
     */
    protected function getIndexEagerLoadRelations(): array
    {
        return $this->getEagerLoadRelations();
    }

    /**
     * Get the relationships to eager load for show method.
     * Can be overridden by child classes.
     */
    protected function getShowEagerLoadRelations(): array
    {
        return $this->getEagerLoadRelations();
    }

    public function index(Request $request)
    {
        $relations = $this->getIndexEagerLoadRelations();

        // Verificar si se solicita paginación
        if ($request->boolean('paginate', false)) {
            $perPage = $request->integer('perPage', 15); // Default 15 items por página
            $page = $request->integer('page', 1);

            // Validar perPage (mínimo 1, máximo 100)
            $perPage = max(1, min(100, $perPage));

            $items = $this->repository->paginateWithFilters($request, $perPage, $relations);

            return $this->resourceClass::collection($items)->additional([
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'from' => $items->firstItem(),
                    'to' => $items->lastItem(),
                    'has_more_pages' => $items->hasMorePages(),
                    'prev_page_url' => $items->previousPageUrl(),
                    'next_page_url' => $items->nextPageUrl(),
                    'path' => $items->path(),
                ],
                'meta' => [
                    'paginated' => true,
                    'per_page_requested' => $request->integer('perPage', 15),
                    'page_requested' => $page,
                    'total_pages' => $items->lastPage(),
                ],
                'request_info' => [
                    'url' => $request->url(),
                    'query_params' => $request->query(),
                ]
            ]);
        }

        // Sin paginación, retornar todos los elementos
        $items = $this->repository->allWithFilters($request, $relations);
        return $this->resourceClass::collection($items)->additional([
            'meta' => [
                'paginated' => false,
                'total_items' => $items->count(),
            ],
            'request_info' => [
                'url' => $request->url(),
                'query_params' => $request->query(),
            ]
        ]);
    }

    public function show($id)
    {
        $relations = $this->getShowEagerLoadRelations();
        $item = $this->repository->find($id, $relations);
        if (!$item) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return new $this->resourceClass($item);
    }

    public function store(Request $request)
    {
        $item = $this->repository->create($request->all());
        return new $this->resourceClass($item);
    }

    public function update(Request $request, $id)
    {
        $item = $this->repository->update($id, $request->all());
        if (!$item) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return new $this->resourceClass($item);
    }

    public function destroy($id)
    {
        $item = $this->repository->delete($id);
        if (!$item) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json(['message' => 'Deleted']);
    }
}
