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

    public function index()
    {
        $relations = $this->getIndexEagerLoadRelations();
        $items = $this->repository->all($relations);
        return $this->resourceClass::collection($items);
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
