<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $relations = [])
    {
        if (empty($relations)) {
            return $this->model->all();
        }
        return $this->model->with($relations)->get();
    }

    public function find($id, array $relations = [])
    {
        if (empty($relations)) {
            return $this->model->find($id);
        }
        return $this->model->with($relations)->find($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($data);
        }
        return $record;
    }

    public function delete($id)
    {
        $record = $this->find($id);
        if ($record) {
            $record->delete();
        }
        return $record;
    }
}
