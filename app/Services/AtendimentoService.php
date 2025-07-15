<?php

namespace App\Services;

use App\Repositories\AtendimentoRepository;
use App\Repositories\AgendamentoRepository;
use App\Models\Atendimento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AtendimentoService
{
    public function __construct(
        protected AtendimentoRepository $repository,
        protected AgendamentoRepository $agendamentoRepository
    ) {}

    public function getAllAtendimentos(): Collection
    {
        return $this->repository->getAll();
    }

    public function getPaginatedAtendimentos(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getAtendimentoById(int $id): ?Atendimento
    {
        return $this->repository->findById($id);
    }

    public function getAtendimentoByAgendamento(int $agendamentoId): ?Atendimento
    {
        return $this->repository->findByAgendamento($agendamentoId);
    }

    public function getAtendimentosByProfissional(int $profissionalId): Collection
    {
        return $this->repository->getByProfissional($profissionalId);
    }

    public function getAtendimentosByPaciente(int $pacienteId): Collection
    {
        return $this->repository->getByPaciente($pacienteId);
    }

    public function getAtendimentosByData(string $data): Collection
    {
        $dataCarbon = Carbon::parse($data);
        return $this->repository->getByData($dataCarbon);
    }

    public function getAtendimentosByPeriodo(string $inicio, string $fim): Collection
    {
        $inicioCarbon = Carbon::parse($inicio);
        $fimCarbon = Carbon::parse($fim);

        if ($inicioCarbon->gt($fimCarbon)) {
            throw new \InvalidArgumentException('Data de início deve ser anterior à data de fim');
        }

        return $this->repository->getByPeriodo($inicioCarbon, $fimCarbon);
    }

    public function getAtendimentosHoje(): Collection
    {
        return $this->repository->getAtendimentosHoje();
    }

    public function getAtendimentosConfirmados(): Collection
    {
        return $this->repository->getConfirmados();
    }

    public function getAtendimentosPendentes(): Collection
    {
        return $this->repository->getPendentes();
    }

    public function getAtendimentosAtrasados(): Collection
    {
        return $this->repository->getAtrasados();
    }

    public function createAtendimento(array $data): Atendimento
    {
        $this->validateAtendimentoData($data);
        
        // Verificar se o agendamento existe e não tem atendimento
        $agendamento = $this->agendamentoRepository->findById($data['agendamento_id']);
        if (!$agendamento) {
            throw new \Exception('Agendamento não encontrado');
        }

        $existingAtendimento = $this->repository->findByAgendamento($data['agendamento_id']);
        if ($existingAtendimento) {
            throw new \Exception('Agendamento já possui um atendimento registrado');
        }

        // Se não forneceu data_hora, usar a do agendamento
        if (!isset($data['data_hora'])) {
            $data['data_hora'] = $agendamento->data_hora;
        }

        return $this->repository->create($data);
    }

    public function createFromAgendamento(int $agendamentoId): Atendimento
    {
        $agendamento = $this->agendamentoRepository->findById($agendamentoId);
        
        if (!$agendamento) {
            throw new \Exception('Agendamento não encontrado');
        }

        // Verificar se já existe atendimento
        $existingAtendimento = $this->repository->findByAgendamento($agendamentoId);
        if ($existingAtendimento) {
            throw new \Exception('Agendamento já possui um atendimento registrado');
        }

        return $this->repository->create([
            'agendamento_id' => $agendamentoId,
            'data_hora' => $agendamento->data_hora,
            'confirmado' => false
        ]);
    }

    public function updateAtendimento(int $id, array $data): bool
    {
        $atendimento = $this->repository->findById($id);
        
        if (!$atendimento) {
            return false;
        }

        if (!$atendimento->canBeEdited()) {
            throw new \Exception('Atendimento não pode ser editado');
        }

        $this->validateAtendimentoData($data);
        
        return $this->repository->update($id, $data);
    }

    public function deleteAtendimento(int $id): bool
    {
        $atendimento = $this->repository->findById($id);
        
        if (!$atendimento) {
            return false;
        }

        if ($atendimento->confirmado) {
            throw new \Exception('Não é possível excluir atendimento confirmado');
        }

        return $this->repository->delete($id);
    }

    public function confirmarAtendimento(int $id): bool
    {
        $atendimento = $this->repository->findById($id);
        
        if (!$atendimento) {
            throw new \Exception('Atendimento não encontrado');
        }

        if (!$atendimento->canBeConfirmed()) {
            throw new \Exception('Atendimento não pode ser confirmado');
        }

        return $this->repository->confirmar($id);
    }

    public function cancelarAtendimento(int $id): bool
    {
        $atendimento = $this->repository->findById($id);
        
        if (!$atendimento) {
            throw new \Exception('Atendimento não encontrado');
        }

        return $this->repository->cancelar($id);
    }

    public function getEstatisticas(string $inicio, string $fim): array
    {
        $inicioCarbon = Carbon::parse($inicio);
        $fimCarbon = Carbon::parse($fim);

        return $this->repository->getEstatisticas($inicioCarbon, $fimCarbon);
    }

    public function getRelatorioProfissional(int $profissionalId, string $inicio, string $fim): array
    {
        $inicioCarbon = Carbon::parse($inicio);
        $fimCarbon = Carbon::parse($fim);

        return $this->repository->getRelatorioProfissional($profissionalId, $inicioCarbon, $fimCarbon);
    }

    public function gerarRelatorioCompleto(string $inicio, string $fim): array
    {
        $atendimentos = $this->getAtendimentosByPeriodo($inicio, $fim);
        $estatisticas = $this->getEstatisticas($inicio, $fim);

        return [
            'periodo' => [
                'inicio' => $inicio,
                'fim' => $fim
            ],
            'atendimentos' => $atendimentos,
            'estatisticas' => $estatisticas,
            'gerado_em' => now()->format('Y-m-d H:i:s')
        ];
    }

    public function processarAtendimentosAtrasados(): array
    {
        $atrasados = $this->getAtendimentosAtrasados();
        $processados = [];

        foreach ($atrasados as $atendimento) {
            // Marcar como cancelado automaticamente
            $this->repository->update($atendimento->id, ['confirmado' => false]);
            
            $processados[] = [
                'id' => $atendimento->id,
                'paciente' => $atendimento->agendamento->paciente->nome,
                'profissional' => $atendimento->agendamento->profissional->nome,
                'data_hora' => $atendimento->data_hora->format('d/m/Y H:i'),
                'acao' => 'Marcado como perdido'
            ];
        }

        return $processados;
    }

    private function validateAtendimentoData(array $data): void
    {
        // Validar agendamento se fornecido
        if (isset($data['agendamento_id'])) {
            $agendamento = $this->agendamentoRepository->findById($data['agendamento_id']);
            if (!$agendamento) {
                throw new \Exception('Agendamento não encontrado');
            }
        }

        // Validar data e hora se fornecida
        if (isset($data['data_hora'])) {
            $dataHora = Carbon::parse($data['data_hora']);
            
            // Não pode ser muito no futuro (máximo 1 dia)
            if ($dataHora->isFuture() && $dataHora->diffInDays(now()) > 1) {
                throw new \InvalidArgumentException('Data do atendimento não pode ser muito no futuro');
            }
        }
    }
}