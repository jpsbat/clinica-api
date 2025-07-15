<?php

namespace App\Repositories;

use App\Models\Profissional;
use Illuminate\Database\Eloquent\Collection;

class ProfissionalRepository
{
    public function __construct(
        protected Profissional $model
    ) {}

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function findById(int $id): ?Profissional
    {
        return $this->model->find($id);
    }

    public function create(array $data): Profissional
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $profissional = $this->findById($id);
        
        if (!$profissional) {
            return false;
        }

        return $profissional->update($data);
    }

    public function delete(int $id): bool
    {
        $profissional = $this->findById($id);
        
        if (!$profissional) {
            return false;
        }

        return $profissional->delete();
    }
}