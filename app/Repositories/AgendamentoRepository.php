<?php

namespace App\Repositories;

use App\Models\Agendamento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AgendamentoRepository
{
    public function __construct(
        protected Agendamento $model
    ) {}

    public function getAll(): Collection
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->orderBy('data_hora', 'desc')
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->orderBy('data_hora', 'desc')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Agendamento
    {
        return $this->model->with(['paciente', 'profissional', 'atendimentos'])->find($id);
    }

    public function getByPaciente(int $pacienteId): Collection
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->byPaciente($pacienteId)
            ->orderBy('data_hora', 'desc')
            ->get();
    }

    public function getByProfissional(int $profissionalId): Collection
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->byProfissional($profissionalId)
            ->orderBy('data_hora', 'desc')
            ->get();
    }

    public function getByData(Carbon $data): Collection
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->byData($data)
            ->orderBy('data_hora')
            ->get();
    }

    public function getByPeriodo(Carbon $inicio, Carbon $fim): Collection
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->byPeriodo($inicio, $fim)
            ->orderBy('data_hora')
            ->get();
    }

    public function getAgendamentosHoje(): Collection
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->hoje()
            ->orderBy('data_hora')
            ->get();
    }

    public function getProximos(int $dias = 7): Collection
    {
        $inicio = now();
        $fim = now()->addDays($dias);

        return $this->getByPeriodo($inicio, $fim);
    }

    public function getRecorrentes(): Collection
    {
        return $this->model
            ->with(['paciente', 'profissional'])
            ->recorrentes()
            ->orderBy('dia_semana')
            ->orderBy('data_hora')
            ->get();
    }

    public function checkConflito(int $profissionalId, Carbon $dataHora, ?int $excludeId = null): bool
    {
        $query = $this->model
            ->where('profissional_id', $profissionalId)
            ->where('data_hora', $dataHora);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function create(array $data): Agendamento
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $agendamento = $this->findById($id);
        
        if (!$agendamento) {
            return false;
        }

        return $agendamento->update($data);
    }

    public function delete(int $id): bool
    {
        $agendamento = $this->findById($id);
        
        if (!$agendamento) {
            return false;
        }

        return $agendamento->delete();
    }

    public function getEstatisticas(Carbon $inicio, Carbon $fim): array
    {
        $agendamentos = $this->getByPeriodo($inicio, $fim);

        $stats = [
            'total' => $agendamentos->count(),
            'realizados' => 0,
            'faltaram' => 0,
            'cancelados' => 0,
            'agendados' => 0,
            'por_profissional' => [],
            'por_dia' => []
        ];

        foreach ($agendamentos as $agendamento) {
            // Contar por status
            $status = $agendamento->status;
            if (isset($stats[$status])) {
                $stats[$status]++;
            } else {
                $stats['agendados']++;
            }

            // Contar por profissional
            $profissionalNome = $agendamento->profissional->nome;
            if (!isset($stats['por_profissional'][$profissionalNome])) {
                $stats['por_profissional'][$profissionalNome] = 0;
            }
            $stats['por_profissional'][$profissionalNome]++;

            // Contar por dia
            $dia = $agendamento->data_hora->format('Y-m-d');
            if (!isset($stats['por_dia'][$dia])) {
                $stats['por_dia'][$dia] = 0;
            }
            $stats['por_dia'][$dia]++;
        }

        return $stats;
    }
}