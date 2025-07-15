<?php

namespace App\Repositories;

use App\Models\Paciente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PacienteRepository
{
    public function __construct(
        protected Paciente $model
    ) {}

    public function getAll(): Collection
    {
        return $this->model->orderBy('nome')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('nome')->paginate($perPage);
    }

    public function findById(int $id): ?Paciente
    {
        return $this->model->find($id);
    }

    public function findByCpf(string $cpf): ?Paciente
    {
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        return $this->model->where('cpf_responsavel', $cpfLimpo)->first();
    }

    public function searchByName(string $nome): Collection
    {
        return $this->model
            ->where('nome', 'LIKE', "%{$nome}%")
            ->orderBy('nome')
            ->get();
    }

    public function create(array $data): Paciente
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $paciente = $this->findById($id);
        
        if (!$paciente) {
            return false;
        }

        return $paciente->update($data);
    }

    public function delete(int $id): bool
    {
        $paciente = $this->findById($id);
        
        if (!$paciente) {
            return false;
        }

        return $paciente->delete();
    }

    public function getByTipoPagamento(string $tipo): Collection
    {
        return $this->model
            ->where('tipo_pagamento', $tipo)
            ->orderBy('nome')
            ->get();
    }
}