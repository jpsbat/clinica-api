<?php

namespace App\Services;

use App\Repositories\AgendamentoRepository;
use App\Repositories\PacienteRepository;
use App\Repositories\ProfissionalRepository;
use App\Models\Agendamento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AgendamentoService
{
    public function __construct(
        protected AgendamentoRepository $repository,
        protected PacienteRepository $pacienteRepository,
        protected ProfissionalRepository $profissionalRepository
    ) {}

    public function getAllAgendamentos(): Collection
    {
        return $this->repository->getAll();
    }

    public function getPaginatedAgendamentos(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getAgendamentoById(int $id): ?Agendamento
    {
        return $this->repository->findById($id);
    }

    public function getAgendamentosByPaciente(int $pacienteId): Collection
    {
        $paciente = $this->pacienteRepository->findById($pacienteId);
        
        if (!$paciente) {
            throw new \Exception('Paciente não encontrado');
        }

        return $this->repository->getByPaciente($pacienteId);
    }

    public function getAgendamentosByProfissional(int $profissionalId): Collection
    {
        $profissional = $this->profissionalRepository->findById($profissionalId);
        
        if (!$profissional) {
            throw new \Exception('Profissional não encontrado');
        }

        return $this->repository->getByProfissional($profissionalId);
    }

    public function getAgendamentosByData(string $data): Collection
    {
        $dataCarbon = Carbon::parse($data);
        return $this->repository->getByData($dataCarbon);
    }

    public function getAgendamentosByPeriodo(string $inicio, string $fim): Collection
    {
        $inicioCarbon = Carbon::parse($inicio);
        $fimCarbon = Carbon::parse($fim);

        if ($inicioCarbon->gt($fimCarbon)) {
            throw new \InvalidArgumentException('Data de início deve ser anterior à data de fim');
        }

        return $this->repository->getByPeriodo($inicioCarbon, $fimCarbon);
    }

    public function getAgendamentosHoje(): Collection
    {
        return $this->repository->getAgendamentosHoje();
    }

    public function getProximosAgendamentos(int $dias = 7): Collection
    {
        return $this->repository->getProximos($dias);
    }

    public function createAgendamento(array $data): Agendamento
    {
        $this->validateAgendamentoData($data);
        
        $dataHora = Carbon::parse($data['data_hora']);
        
        // Verificar conflitos de horário
        $this->checkConflitos($data['profissional_id'], $dataHora);
        
        // Definir dia da semana se for recorrente
        if (isset($data['recorrente']) && $data['recorrente']) {
            $data['dia_semana'] = $this->getDiaSemanaPorData($dataHora);
        }
        
        $agendamento = $this->repository->create($data);
        
        // Criar agendamentos recorrentes se necessário
        if ($data['recorrente'] ?? false) {
            $this->criarAgendamentosRecorrentes($agendamento, $data);
        }
        
        return $agendamento;
    }

    public function updateAgendamento(int $id, array $data): bool
    {
        $agendamento = $this->repository->findById($id);
        
        if (!$agendamento) {
            return false;
        }

        if (!$agendamento->canBeEdited()) {
            throw new \Exception('Agendamento não pode ser editado');
        }

        $this->validateAgendamentoData($data);
        
        // Verificar conflitos se mudou horário ou profissional
        if (isset($data['data_hora']) || isset($data['profissional_id'])) {
            $dataHora = isset($data['data_hora']) 
                ? Carbon::parse($data['data_hora']) 
                : $agendamento->data_hora;
            
            $profissionalId = $data['profissional_id'] ?? $agendamento->profissional_id;
            
            $this->checkConflitos($profissionalId, $dataHora, $id);
        }
        
        // Atualizar dia da semana se mudou a data
        if (isset($data['data_hora'])) {
            $data['dia_semana'] = $this->getDiaSemanaPorData(Carbon::parse($data['data_hora']));
        }
        
        return $this->repository->update($id, $data);
    }

    public function cancelarAgendamento(int $id): bool
    {
        $agendamento = $this->repository->findById($id);
        
        if (!$agendamento) {
            throw new \Exception('Agendamento não encontrado');
        }

        if (!$agendamento->canBeCancelled()) {
            throw new \Exception('Agendamento não pode ser cancelado');
        }

        return $this->repository->delete($id);
    }

    public function confirmarAgendamento(int $id): bool
    {
        // Esta funcionalidade será implementada quando criarmos os atendimentos
        $agendamento = $this->repository->findById($id);
        
        if (!$agendamento) {
            throw new \Exception('Agendamento não encontrado');
        }

        // Por enquanto, apenas retorna true
        // Na implementação real, criaria um atendimento
        return true;
    }

    public function getEstatisticas(string $inicio, string $fim): array
    {
        $inicioCarbon = Carbon::parse($inicio);
        $fimCarbon = Carbon::parse($fim);

        return $this->repository->getEstatisticas($inicioCarbon, $fimCarbon);
    }

    public function getAgendamentosRecorrentes(): Collection
    {
        return $this->repository->getRecorrentes();
    }

    private function validateAgendamentoData(array $data): void
    {
        // Validar se paciente existe
        if (isset($data['paciente_id'])) {
            $paciente = $this->pacienteRepository->findById($data['paciente_id']);
            if (!$paciente) {
                throw new \Exception('Paciente não encontrado');
            }
        }

        // Validar se profissional existe
        if (isset($data['profissional_id'])) {
            $profissional = $this->profissionalRepository->findById($data['profissional_id']);
            if (!$profissional) {
                throw new \Exception('Profissional não encontrado');
            }
        }

        // Validar data e hora
        if (isset($data['data_hora'])) {
            $dataHora = Carbon::parse($data['data_hora']);
            
            if ($dataHora->isPast()) {
                throw new \InvalidArgumentException('Data e hora não podem ser no passado');
            }

            // Validar horário comercial (8h às 18h)
            $hora = $dataHora->hour;
            if ($hora < 8 || $hora >= 18) {
                throw new \InvalidArgumentException('Agendamentos devem ser entre 8h e 18h');
            }

            // Validar dias úteis (segunda a sábado) - Domingo é 0
            if ($dataHora->dayOfWeek === 0) {
                throw new \InvalidArgumentException('Agendamentos não são permitidos aos domingos');
            }
        }

        // Validar dia da semana se for recorrente
        if (isset($data['recorrente']) && $data['recorrente']) {
            if (isset($data['dia_semana']) && !array_key_exists($data['dia_semana'], Agendamento::DIAS_SEMANA)) {
                throw new \InvalidArgumentException('Dia da semana inválido');
            }
        }
    }

    private function checkConflitos(int $profissionalId, Carbon $dataHora, ?int $excludeId = null): void
    {
        if ($this->repository->checkConflito($profissionalId, $dataHora, $excludeId)) {
            throw new \Exception('Profissional já possui agendamento neste horário');
        }
    }

    private function getDiaSemanaPorData(Carbon $data): string
    {
        $diasSemana = [
            1 => 'segunda',
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado',
            0 => 'domingo'
        ];

        return $diasSemana[$data->dayOfWeek];
    }

    private function criarAgendamentosRecorrentes(Agendamento $agendamentoBase, array $data): void
    {
        $dataAtual = Carbon::parse($data['data_hora'])->addWeek();
        $dataLimite = $dataAtual->copy()->addMonths(3); // Criar para os próximos 3 meses

        while ($dataAtual->lte($dataLimite)) {
            // Verificar se não há conflito
            if (!$this->repository->checkConflito($data['profissional_id'], $dataAtual)) {
                $this->repository->create([
                    'paciente_id' => $data['paciente_id'],
                    'profissional_id' => $data['profissional_id'],
                    'data_hora' => $dataAtual->copy(),
                    'recorrente' => true,
                    'dia_semana' => $data['dia_semana']
                ]);
            }

            $dataAtual->addWeek();
        }
    }
}