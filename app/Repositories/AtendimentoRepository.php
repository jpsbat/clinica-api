<?php

namespace App\Repositories;

use App\Models\Atendimento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AtendimentoRepository
{
    public function __construct(
        protected Atendimento $model
    ) {}

    public function getAll(): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->orderBy('data_hora', 'desc')
            ->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->orderBy('data_hora', 'desc')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Atendimento
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->find($id);
    }

    public function findByAgendamento(int $agendamentoId): ?Atendimento
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->where('agendamento_id', $agendamentoId)
            ->first();
    }

    public function getByProfissional(int $profissionalId): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->byProfissional($profissionalId)
            ->orderBy('data_hora', 'desc')
            ->get();
    }

    public function getByPaciente(int $pacienteId): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->byPaciente($pacienteId)
            ->orderBy('data_hora', 'desc')
            ->get();
    }

    public function getByData(Carbon $data): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->byData($data)
            ->orderBy('data_hora')
            ->get();
    }

    public function getByPeriodo(Carbon $inicio, Carbon $fim): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->byPeriodo($inicio, $fim)
            ->orderBy('data_hora')
            ->get();
    }

    public function getAtendimentosHoje(): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->hoje()
            ->orderBy('data_hora')
            ->get();
    }

    public function getConfirmados(): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->confirmados()
            ->orderBy('data_hora', 'desc')
            ->get();
    }

    public function getPendentes(): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->pendentes()
            ->orderBy('data_hora')
            ->get();
    }

    public function getAtrasados(): Collection
    {
        return $this->model
            ->with(['agendamento.paciente', 'agendamento.profissional'])
            ->atrasados()
            ->orderBy('data_hora')
            ->get();
    }

    public function create(array $data): Atendimento
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $atendimento = $this->findById($id);
        
        if (!$atendimento) {
            return false;
        }

        return $atendimento->update($data);
    }

    public function delete(int $id): bool
    {
        $atendimento = $this->findById($id);
        
        if (!$atendimento) {
            return false;
        }

        return $atendimento->delete();
    }

    public function confirmar(int $id): bool
    {
        return $this->update($id, ['confirmado' => true]);
    }

    public function cancelar(int $id): bool
    {
        return $this->update($id, ['confirmado' => false]);
    }

    public function getEstatisticas(Carbon $inicio, Carbon $fim): array
    {
        $atendimentos = $this->getByPeriodo($inicio, $fim);

        $stats = [
            'total' => $atendimentos->count(),
            'confirmados' => $atendimentos->where('confirmado', true)->count(),
            'pendentes' => $atendimentos->where('confirmado', false)->count(),
            'atrasados' => 0,
            'por_profissional' => [],
            'por_dia' => [],
            'taxa_confirmacao' => 0
        ];

        foreach ($atendimentos as $atendimento) {
            // Contar atrasados
            if ($atendimento->isAtrasado()) {
                $stats['atrasados']++;
            }

            // Contar por profissional
            $profissionalNome = $atendimento->agendamento->profissional->nome;
            if (!isset($stats['por_profissional'][$profissionalNome])) {
                $stats['por_profissional'][$profissionalNome] = [
                    'total' => 0,
                    'confirmados' => 0
                ];
            }
            $stats['por_profissional'][$profissionalNome]['total']++;
            if ($atendimento->confirmado) {
                $stats['por_profissional'][$profissionalNome]['confirmados']++;
            }

            // Contar por dia
            $dia = $atendimento->data_hora->format('Y-m-d');
            if (!isset($stats['por_dia'][$dia])) {
                $stats['por_dia'][$dia] = [
                    'total' => 0,
                    'confirmados' => 0
                ];
            }
            $stats['por_dia'][$dia]['total']++;
            if ($atendimento->confirmado) {
                $stats['por_dia'][$dia]['confirmados']++;
            }
        }

        // Calcular taxa de confirmação
        if ($stats['total'] > 0) {
            $stats['taxa_confirmacao'] = round(($stats['confirmados'] / $stats['total']) * 100, 2);
        }

        return $stats;
    }

    public function getRelatorioProfissional(int $profissionalId, Carbon $inicio, Carbon $fim): array
    {
        $atendimentos = $this->model
            ->with(['agendamento.paciente'])
            ->byProfissional($profissionalId)
            ->byPeriodo($inicio, $fim)
            ->orderBy('data_hora')
            ->get();

        return [
            'atendimentos' => $atendimentos,
            'total' => $atendimentos->count(),
            'confirmados' => $atendimentos->where('confirmado', true)->count(),
            'valor_total' => $this->calcularValorTotal($atendimentos)
        ];
    }

    private function calcularValorTotal(Collection $atendimentos): float
    {
        $total = 0;

        foreach ($atendimentos as $atendimento) {
            if ($atendimento->confirmado) {
                $paciente = $atendimento->agendamento->paciente;
                if ($paciente->tipo_pagamento === 'particular' && $paciente->valor) {
                    $total += $paciente->valor;
                }
            }
        }

        return $total;
    }
}