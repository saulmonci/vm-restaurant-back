<?php

namespace App\Repositories;

interface BaseRepositoryInterface
{
    public function all(array $relations = []);
    public function find($id, array $relations = []);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
