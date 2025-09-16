<?php

namespace App\Repositories;

use Illuminate\Http\Request;

interface BaseRepositoryInterface
{
    public function all(array $relations = []);
    public function paginate(int $perPage = 15, array $relations = []);
    public function allWithFilters(Request $request, array $relations = []);
    public function paginateWithFilters(Request $request, int $perPage = 15, array $relations = []);
    public function find($id, array $relations = []);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getModel();
}
