<?php

namespace App\Services;

use App\Repositories\UsuarioRepository;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UsuarioService
{
    public function __construct(
        protected UsuarioRepository $repository
    ) {}

    public function getAllUsuarios(): Collection
    {
        return $this->repository->getAll();
    }

    public function getPaginatedUsuarios(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getUsuarioById(int $id): ?Usuario
    {
        return $this->repository->findById($id);
    }

    public function searchUsuarios(string $termo): Collection
    {
        return $this->repository->searchByName($termo);
    }

    public function getUsuariosByTipo(string $tipo): Collection
    {
        if (!array_key_exists($tipo, Usuario::TIPOS)) {
            throw new \InvalidArgumentException('Tipo de usuário inválido');
        }

        return $this->repository->getByTipo($tipo);
    }

    public function createUsuario(array $data): Usuario
    {
        $this->validateUsuarioData($data);
        $this->checkEmailDuplicate($data['email']);
        
        return $this->repository->create($data);
    }

    public function updateUsuario(int $id, array $data): bool
    {
        $this->validateUsuarioData($data, $id);
        
        if (isset($data['email'])) {
            $this->checkEmailDuplicate($data['email'], $id);
        }
        
        return $this->repository->update($id, $data);
    }

    public function deleteUsuario(int $id): bool
    {
        $usuario = $this->repository->findById($id);
        
        if (!$usuario) {
            return false;
        }

        // Não permitir deletar o último admin
        if ($usuario->isAdmin()) {
            $adminCount = $this->repository->getByTipo('admin')->count();
            if ($adminCount <= 1) {
                throw new \Exception('Não é possível excluir o último administrador do sistema');
            }
        }

        return $this->repository->delete($id);
    }

    public function changePassword(int $id, string $currentPassword, string $newPassword): bool
    {
        $usuario = $this->repository->findById($id);
        
        if (!$usuario) {
            throw new \Exception('Usuário não encontrado');
        }

        if (!$usuario->checkPassword($currentPassword)) {
            throw new \Exception('Senha atual incorreta');
        }

        $this->validatePassword($newPassword);
        
        return $this->repository->updatePassword($id, $newPassword);
    }

    public function resetPassword(int $id, string $newPassword): bool
    {
        $this->validatePassword($newPassword);
        
        return $this->repository->updatePassword($id, $newPassword);
    }

    public function getStatistics(): array
    {
        $countByTipo = $this->repository->countByTipo();
        $total = array_sum($countByTipo);

        return [
            'total' => $total,
            'por_tipo' => $countByTipo,
            'tipos_disponiveis' => Usuario::TIPOS
        ];
    }

    public function authenticateUser(string $email, string $password): ?Usuario
    {
        $usuario = $this->repository->findByEmail($email);
        
        if (!$usuario || !$usuario->checkPassword($password)) {
            return null;
        }

        return $usuario;
    }

    private function validateUsuarioData(array $data, ?int $excludeId = null): void
    {
        // Validar tipo de usuário
        if (isset($data['tipo']) && !array_key_exists($data['tipo'], Usuario::TIPOS)) {
            throw new \InvalidArgumentException('Tipo de usuário inválido');
        }

        // Validar email
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email inválido');
        }

        // Validar senha (apenas na criação ou se fornecida)
        if (isset($data['password']) && !empty($data['password'])) {
            $this->validatePassword($data['password']);
        }
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Senha deve ter pelo menos 8 caracteres');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Senha deve conter pelo menos uma letra maiúscula');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Senha deve conter pelo menos uma letra minúscula');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException('Senha deve conter pelo menos um número');
        }
    }

    private function checkEmailDuplicate(string $email, ?int $excludeId = null): void
    {
        $existingUsuario = $this->repository->findByEmail($email);
        
        if ($existingUsuario && (!$excludeId || $existingUsuario->id !== $excludeId)) {
            throw new \Exception('Email já cadastrado para outro usuário');
        }
    }
}