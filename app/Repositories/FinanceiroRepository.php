<?php

namespace App\Repositories;

use App\Models\Financeiro;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FinanceiroRepository
{
    public function __construct(
        protected Financeiro $model
    ) {}

    public function getAll(): Collection
    {
        return $this->model
            ->with('profissional')
            ->orderBy('mes_referencia', 'desc')
            ->orderBy('profissional_id')
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('profissional')
            ->orderBy('mes_referencia', 'desc')
            ->orderBy('profissional_id')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Financeiro
    {
        return $this->model->with('profissional')->find($id);
    }

    public function findByProfissionalAndMes(int $profissionalId, string $mesReferencia): ?Financeiro
    {
        return $this->model
            ->where('profissional_id', $profissionalId)
            ->where('mes_referencia', $mesReferencia)
            ->first();
    }

    public function getByAno(int $ano): Collection
    {
        return $this->model
            ->with('profissional')
            ->byAno($ano)
            ->orderBy('mes_referencia', 'desc')
            ->orderBy('profissional_id')
            ->get();
    }

    public function getByMes(string $mesReferencia): Collection
    {
        return $this->model
            ->with('profissional')
            ->byMes($mesReferencia)
            ->orderBy('profissional_id')
            ->get();
    }

    public function getByProfissional(int $profissionalId): Collection
    {
        return $this->model
            ->with('profissional')
            ->byProfissional($profissionalId)
            ->orderBy('mes_referencia', 'desc')
            ->get();
    }

    public function create(array $data): Financeiro
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $financeiro = $this->findById($id);
        
        if (!$financeiro) {
            return false;
        }

        return $financeiro->update($data);
    }

    public function delete(int $id): bool
    {
        $financeiro = $this->findById($id);
        
        if (!$financeiro) {
            return false;
        }

        return $financeiro->delete();
    }

    public function getResumoByMes(string $mesReferencia): array
    {
        return $this->model
            ->selectRaw('
                COUNT(*) as total_profissionais,
                SUM(total_atendimentos) as total_atendimentos,
                SUM(valor_bruto) as valor_bruto_total,
                SUM(valor_repassado) as valor_repassado_total,
                AVG(valor_bruto) as valor_bruto_medio
            ')
            ->byMes($mesReferencia)
            ->first()
            ->toArray();
    }

    public function getResumoByAno(int $ano): array
    {
        return $this->model
            ->selectRaw('
                mes_referencia,
                COUNT(*) as total_profissionais,
                SUM(total_atendimentos) as total_atendimentos,
                SUM(valor_bruto) as valor_bruto_total,
                SUM(valor_repassado) as valor_repassado_total
            ')
            ->byAno($ano)
            ->groupBy('mes_referencia')
            ->orderBy('mes_referencia')
            ->get()
            ->toArray();
    }

    public function getTopProfissionaisByMes(string $mesReferencia, int $limit = 5): Collection
    {
        return $this->model
            ->with('profissional')
            ->byMes($mesReferencia)
            ->orderBy('valor_bruto', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getMesesDisponiveis(): array
    {
        return $this->model
            ->selectRaw('DISTINCT mes_referencia')
            ->orderBy('mes_referencia', 'desc')
            ->pluck('mes_referencia')
            ->toArray();
    }
}