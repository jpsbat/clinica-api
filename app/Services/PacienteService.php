<?php

namespace App\Services;

use App\Repositories\PacienteRepository;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PacienteService
{
    public function __construct(
        protected PacienteRepository $repository
    ) {}

    public function getAllPacientes(): Collection
    {
        return $this->repository->getAll();
    }

    public function getPaginatedPacientes(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getPacienteById(int $id): ?Paciente
    {
        return $this->repository->findById($id);
    }

    public function searchPacientes(string $termo): Collection
    {
        // Se o termo contém apenas números, busca por CPF
        if (preg_match('/^\d+$/', preg_replace('/\D/', '', $termo))) {
            $paciente = $this->repository->findByCpf($termo);
            return $paciente ? collect([$paciente]) : collect();
        }

        // Caso contrário, busca por nome
        return $this->repository->searchByName($termo);
    }

    public function createPaciente(array $data): Paciente
    {
        $this->validatePacienteData($data);
        $this->checkCpfDuplicate($data['cpf_responsavel']);
        
        return $this->repository->create($data);
    }

    public function updatePaciente(int $id, array $data): bool
    {
        $this->validatePacienteData($data, $id);
        
        if (isset($data['cpf_responsavel'])) {
            $this->checkCpfDuplicate($data['cpf_responsavel'], $id);
        }
        
        return $this->repository->update($id, $data);
    }

    public function deletePaciente(int $id): bool
    {
        // Verificar se o paciente tem agendamentos
        $paciente = $this->repository->findById($id);
        
        if (!$paciente) {
            return false;
        }

        if ($paciente->agendamentos()->exists()) {
            throw new \Exception('Não é possível excluir paciente com agendamentos cadastrados');
        }

        return $this->repository->delete($id);
    }

    public function getPacientesByTipoPagamento(string $tipo): Collection
    {
        if (!in_array($tipo, ['particular', 'convenio'])) {
            throw new \InvalidArgumentException('Tipo de pagamento inválido');
        }

        return $this->repository->getByTipoPagamento($tipo);
    }

    private function validatePacienteData(array $data, ?int $excludeId = null): void
    {
        // Validar CPF
        if (isset($data['cpf_responsavel'])) {
            $cpfLimpo = preg_replace('/\D/', '', $data['cpf_responsavel']);
            
            if (strlen($cpfLimpo) !== 11) {
                throw new \InvalidArgumentException('CPF deve conter 11 dígitos');
            }
            
            if (!$this->isValidCpf($cpfLimpo)) {
                throw new \InvalidArgumentException('CPF inválido. Use apenas números e verifique se está correto.');
            }
        }

        // Validar valor para particulares
        if (isset($data['tipo_pagamento']) && $data['tipo_pagamento'] === 'particular') {
            if (!isset($data['valor']) || $data['valor'] <= 0) {
                throw new \InvalidArgumentException('Valor é obrigatório para pacientes particulares');
            }
        }

        // Validar data de nascimento
        if (isset($data['data_nascimento'])) {
            $dataNascimento = \Carbon\Carbon::parse($data['data_nascimento']);
            if ($dataNascimento->isFuture()) {
                throw new \InvalidArgumentException('Data de nascimento não pode ser futura');
            }
        }
    }

    private function checkCpfDuplicate(string $cpf, ?int $excludeId = null): void
    {
        $existingPaciente = $this->repository->findByCpf($cpf);
        
        if ($existingPaciente && (!$excludeId || $existingPaciente->id !== $excludeId)) {
            throw new \Exception('CPF já cadastrado para outro paciente');
        }
    }

    private function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Validação do CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }
}