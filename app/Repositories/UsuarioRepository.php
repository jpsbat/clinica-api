<?php

namespace App\Repositories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UsuarioRepository
{
    public function __construct(
        protected Usuario $model
    ) {}

    public function getAll(): Collection
    {
        return $this->model->orderBy('nome')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('nome')->paginate($perPage);
    }

    public function findById(int $id): ?Usuario
    {
        return $this->model->find($id);
    }

    public function findByEmail(string $email): ?Usuario
    {
        return $this->model->where('email', $email)->first();
    }

    public function searchByName(string $nome): Collection
    {
        return $this->model
            ->where('nome', 'LIKE', "%{$nome}%")
            ->orderBy('nome')
            ->get();
    }

    public function getByTipo(string $tipo): Collection
    {
        return $this->model
            ->where('tipo', $tipo)
            ->orderBy('nome')
            ->get();
    }

    public function create(array $data): Usuario
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $usuario = $this->findById($id);
        
        if (!$usuario) {
            return false;
        }

        // Remove a senha se estiver vazia
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        }

        return $usuario->update($data);
    }

    public function delete(int $id): bool
    {
        $usuario = $this->findById($id);
        
        if (!$usuario) {
            return false;
        }

        return $usuario->delete();
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $usuario = $this->findById($id);
        
        if (!$usuario) {
            return false;
        }

        return $usuario->update(['password' => $newPassword]);
    }

    public function countByTipo(): array
    {
        return $this->model
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->toArray();
    }

    public function getActiveUsers(): Collection
    {
        return $this->model
            ->whereNull('deleted_at')
            ->orderBy('nome')
            ->get();
    }
}