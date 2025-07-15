<?php

namespace App\Services;

use App\Repositories\ProfissionalRepository;
use App\Models\Profissional;
use Illuminate\Database\Eloquent\Collection;

class ProfissionalService
{
    public function __construct(
        protected ProfissionalRepository $repository
    ) {}

    public function getAllProfissionais(): Collection
    {
        return $this->repository->getAll();
    }

    public function getProfissionalById(int $id): ?Profissional
    {
        return $this->repository->findById($id);
    }

    public function createProfissional(array $data): Profissional
    {
        $this->validateProfissionalData($data);
        
        return $this->repository->create($data);
    }

    public function updateProfissional(int $id, array $data): bool
    {
        $this->validateProfissionalData($data);
        
        return $this->repository->update($id, $data);
    }

    public function deleteProfissional(int $id): bool
    {
        return $this->repository->delete($id);
    }

    private function validateProfissionalData(array $data): void
    {
        // Adicionar validações específicas de negócio
        if (isset($data['percentual_repassado']) && $data['percentual_repassado'] > 100) {
            throw new \InvalidArgumentException('Percentual não pode ser maior que 100%');
        }
    }
}