<?php

namespace App\Services;

use App\Repositories\FinanceiroRepository;
use App\Repositories\ProfissionalRepository;
use App\Models\Financeiro;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class FinanceiroService
{
    public function __construct(
        protected FinanceiroRepository $repository,
        protected ProfissionalRepository $profissionalRepository
    ) {}

    public function getAllFinanceiros(): Collection
    {
        return $this->repository->getAll();
    }

    public function getPaginatedFinanceiros(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getFinanceiroById(int $id): ?Financeiro
    {
        return $this->repository->findById($id);
    }

    public function getFinanceirosByAno(int $ano): Collection
    {
        return $this->repository->getByAno($ano);
    }

    public function getFinanceirosByMes(string $mesReferencia): Collection
    {
        $this->validateMesReferencia($mesReferencia);
        return $this->repository->getByMes($mesReferencia);
    }

    public function getFinanceirosByProfissional(int $profissionalId): Collection
    {
        $profissional = $this->profissionalRepository->findById($profissionalId);
        
        if (!$profissional) {
            throw new \Exception('Profissional não encontrado');
        }

        return $this->repository->getByProfissional($profissionalId);
    }

    public function createFinanceiro(array $data): Financeiro
    {
        $this->validateFinanceiroData($data);
        $this->checkDuplicateRegistro($data['profissional_id'], $data['mes_referencia']);
        
        // Calcular valor repassado baseado no percentual do profissional
        $profissional = $this->profissionalRepository->findById($data['profissional_id']);
        $data['valor_repassado'] = $this->calculateValorRepassado(
            $data['valor_bruto'], 
            (float) $profissional->percentual_repassado
        );
        
        return $this->repository->create($data);
    }

    public function updateFinanceiro(int $id, array $data): bool
    {
        $financeiro = $this->repository->findById($id);
        
        if (!$financeiro) {
            return false;
        }

        $this->validateFinanceiroData($data);
        
        // Verificar duplicata apenas se mudou profissional ou mês
        if (isset($data['profissional_id']) || isset($data['mes_referencia'])) {
            $profissionalId = $data['profissional_id'] ?? $financeiro->profissional_id;
            $mesReferencia = $data['mes_referencia'] ?? $financeiro->mes_referencia;
            
            $this->checkDuplicateRegistro($profissionalId, $mesReferencia, $id);
        }
        
        // Recalcular valor repassado se necessário
        if (isset($data['valor_bruto']) || isset($data['profissional_id'])) {
            $profissionalId = $data['profissional_id'] ?? $financeiro->profissional_id;
            $valorBruto = $data['valor_bruto'] ?? $financeiro->valor_bruto;
            
            $profissional = $this->profissionalRepository->findById($profissionalId);
            $data['valor_repassado'] = $this->calculateValorRepassado(
                $valorBruto, 
                (float) $profissional->percentual_repassado
            );
        }
        
        return $this->repository->update($id, $data);
    }

    public function deleteFinanceiro(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function getResumoMensal(string $mesReferencia): array
    {
        $this->validateMesReferencia($mesReferencia);
        
        $resumo = $this->repository->getResumoByMes($mesReferencia);
        $topProfissionais = $this->repository->getTopProfissionaisByMes($mesReferencia);
        
        return [
            'mes_referencia' => $mesReferencia,
            'resumo' => $resumo,
            'top_profissionais' => $topProfissionais,
            'valor_retido_total' => $resumo['valor_bruto_total'] - $resumo['valor_repassado_total']
        ];
    }

    public function getResumoAnual(int $ano): array
    {
        $resumoPorMes = $this->repository->getResumoByAno($ano);
        
        $totais = [
            'total_profissionais' => 0,
            'total_atendimentos' => 0,
            'valor_bruto_total' => 0,
            'valor_repassado_total' => 0
        ];

        foreach ($resumoPorMes as $mes) {
            $totais['total_atendimentos'] += $mes['total_atendimentos'];
            $totais['valor_bruto_total'] += $mes['valor_bruto_total'];
            $totais['valor_repassado_total'] += $mes['valor_repassado_total'];
        }

        $totais['valor_retido_total'] = $totais['valor_bruto_total'] - $totais['valor_repassado_total'];

        return [
            'ano' => $ano,
            'resumo_por_mes' => $resumoPorMes,
            'totais_anuais' => $totais
        ];
    }

    public function getMesesDisponiveis(): array
    {
        return $this->repository->getMesesDisponiveis();
    }

    public function gerarRelatorioCompleto(string $mesReferencia): array
    {
        $financeiros = $this->getFinanceirosByMes($mesReferencia);
        $resumo = $this->getResumoMensal($mesReferencia);

        return [
            'mes_referencia' => $mesReferencia,
            'financeiros' => $financeiros,
            'resumo' => $resumo,
            'gerado_em' => now()->format('Y-m-d H:i:s')
        ];
    }

    private function validateFinanceiroData(array $data): void
    {
        // Validar mês de referência
        if (isset($data['mes_referencia'])) {
            $this->validateMesReferencia($data['mes_referencia']);
        }

        // Validar valores positivos
        if (isset($data['valor_bruto']) && $data['valor_bruto'] < 0) {
            throw new \InvalidArgumentException('Valor bruto deve ser positivo');
        }

        if (isset($data['total_atendimentos']) && $data['total_atendimentos'] < 0) {
            throw new \InvalidArgumentException('Total de atendimentos deve ser positivo');
        }

        // Validar profissional existe
        if (isset($data['profissional_id'])) {
            $profissional = $this->profissionalRepository->findById($data['profissional_id']);
            if (!$profissional) {
                throw new \Exception('Profissional não encontrado');
            }
        }
    }

    private function validateMesReferencia(string $mesReferencia): void
    {
        if (!preg_match('/^\d{4}-\d{1,2}$/', $mesReferencia)) {
            throw new \InvalidArgumentException('Formato de mês inválido. Use YYYY-M ou YYYY-MM');
        }

        try {
            Carbon::createFromFormat('Y-m', $mesReferencia);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Mês de referência inválido');
        }
    }

    private function checkDuplicateRegistro(int $profissionalId, string $mesReferencia, ?int $excludeId = null): void
    {
        $existing = $this->repository->findByProfissionalAndMes($profissionalId, $mesReferencia);
        
        if ($existing && (!$excludeId || $existing->id !== $excludeId)) {
            throw new \Exception('Já existe um registro financeiro para este profissional neste mês');
        }
    }

    private function calculateValorRepassado(float $valorBruto, float $percentualRepassado): float
    {
        return round($valorBruto * ($percentualRepassado / 100), 2);
    }
}