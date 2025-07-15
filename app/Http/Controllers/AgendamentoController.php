<?php

namespace App\Http\Controllers;

use App\Services\AgendamentoService;
use App\Models\Agendamento;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgendamentoController extends Controller
{
    public function __construct(
        protected AgendamentoService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $pacienteId = $request->query('paciente_id');
            $profissionalId = $request->query('profissional_id');
            $data = $request->query('data');
            $inicio = $request->query('inicio');
            $fim = $request->query('fim');
            $hoje = $request->query('hoje');
            $proximos = $request->query('proximos');
            $recorrentes = $request->query('recorrentes');
            $paginate = $request->query('paginate', true);

            if ($hoje) {
                $agendamentos = $this->service->getAgendamentosHoje();
                return response()->json(['data' => $agendamentos]);
            }

            if ($proximos) {
                $dias = (int) $proximos;
                $agendamentos = $this->service->getProximosAgendamentos($dias);
                return response()->json(['data' => $agendamentos]);
            }

            if ($recorrentes) {
                $agendamentos = $this->service->getAgendamentosRecorrentes();
                return response()->json(['data' => $agendamentos]);
            }

            if ($pacienteId) {
                $agendamentos = $this->service->getAgendamentosByPaciente((int) $pacienteId);
                return response()->json(['data' => $agendamentos]);
            }

            if ($profissionalId) {
                $agendamentos = $this->service->getAgendamentosByProfissional((int) $profissionalId);
                return response()->json(['data' => $agendamentos]);
            }

            if ($data) {
                $agendamentos = $this->service->getAgendamentosByData($data);
                return response()->json(['data' => $agendamentos]);
            }

            if ($inicio && $fim) {
                $agendamentos = $this->service->getAgendamentosByPeriodo($inicio, $fim);
                return response()->json(['data' => $agendamentos]);
            }

            if ($paginate === 'false') {
                $agendamentos = $this->service->getAllAgendamentos();
                return response()->json(['data' => $agendamentos]);
            }

            $perPage = (int) $request->query('per_page', 15);
            $agendamentos = $this->service->getPaginatedAgendamentos($perPage);
            
            return response()->json($agendamentos);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $agendamento = $this->service->getAgendamentoById($id);
            
            if (!$agendamento) {
                return response()->json(['message' => 'Agendamento não encontrado'], 404);
            }
            
            return response()->json(['data' => $agendamento]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'profissional_id' => 'required|exists:profissionais,id',
            'data_hora' => 'required|date|after:now',
            'recorrente' => 'boolean',
            'dia_semana' => 'nullable|string|in:segunda,terca,quarta,quinta,sexta,sabado,domingo'
        ]);

        try {
            $agendamento = $this->service->createAgendamento($validated);
            
            return response()->json([
                'data' => $agendamento,
                'message' => 'Agendamento criado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'paciente_id' => 'sometimes|exists:pacientes,id',
            'profissional_id' => 'sometimes|exists:profissionais,id',
            'data_hora' => 'sometimes|date|after:now',
            'recorrente' => 'sometimes|boolean',
            'dia_semana' => 'nullable|string|in:segunda,terca,quarta,quinta,sexta,sabado,domingo'
        ]);

        try {
            $updated = $this->service->updateAgendamento($id, $validated);
            
            if (!$updated) {
                return response()->json(['message' => 'Agendamento não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Agendamento atualizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $cancelled = $this->service->cancelarAgendamento($id);
            
            if (!$cancelled) {
                return response()->json(['message' => 'Agendamento não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Agendamento cancelado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function confirmar(int $id): JsonResponse
    {
        try {
            $confirmado = $this->service->confirmarAgendamento($id);
            
            if (!$confirmado) {
                return response()->json(['message' => 'Erro ao confirmar agendamento'], 400);
            }
            
            return response()->json(['message' => 'Agendamento confirmado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function estatisticas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inicio' => 'required|date',
            'fim' => 'required|date|after_or_equal:inicio'
        ]);

        try {
            $stats = $this->service->getEstatisticas($validated['inicio'], $validated['fim']);
            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function diasSemana(): JsonResponse
    {
        return response()->json([
            'data' => Agendamento::DIAS_SEMANA
        ]);
    }
}