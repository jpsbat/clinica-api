<?php

namespace App\Http\Controllers;

use App\Services\AtendimentoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AtendimentoController extends Controller
{
    public function __construct(
        protected AtendimentoService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $profissionalId = $request->query('profissional_id');
            $pacienteId = $request->query('paciente_id');
            $data = $request->query('data');
            $inicio = $request->query('inicio');
            $fim = $request->query('fim');
            $hoje = $request->query('hoje');
            $confirmados = $request->query('confirmados');
            $pendentes = $request->query('pendentes');
            $atrasados = $request->query('atrasados');
            $paginate = $request->query('paginate', true);

            if ($hoje) {
                $atendimentos = $this->service->getAtendimentosHoje();
                return response()->json(['data' => $atendimentos]);
            }

            if ($confirmados) {
                $atendimentos = $this->service->getAtendimentosConfirmados();
                return response()->json(['data' => $atendimentos]);
            }

            if ($pendentes) {
                $atendimentos = $this->service->getAtendimentosPendentes();
                return response()->json(['data' => $atendimentos]);
            }

            if ($atrasados) {
                $atendimentos = $this->service->getAtendimentosAtrasados();
                return response()->json(['data' => $atendimentos]);
            }

            if ($profissionalId) {
                $atendimentos = $this->service->getAtendimentosByProfissional((int) $profissionalId);
                return response()->json(['data' => $atendimentos]);
            }

            if ($pacienteId) {
                $atendimentos = $this->service->getAtendimentosByPaciente((int) $pacienteId);
                return response()->json(['data' => $atendimentos]);
            }

            if ($data) {
                $atendimentos = $this->service->getAtendimentosByData($data);
                return response()->json(['data' => $atendimentos]);
            }

            if ($inicio && $fim) {
                $atendimentos = $this->service->getAtendimentosByPeriodo($inicio, $fim);
                return response()->json(['data' => $atendimentos]);
            }

            if ($paginate === 'false') {
                $atendimentos = $this->service->getAllAtendimentos();
                return response()->json(['data' => $atendimentos]);
            }

            $perPage = (int) $request->query('per_page', 15);
            $atendimentos = $this->service->getPaginatedAtendimentos($perPage);
            
            return response()->json($atendimentos);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $atendimento = $this->service->getAtendimentoById($id);
            
            if (!$atendimento) {
                return response()->json(['message' => 'Atendimento não encontrado'], 404);
            }
            
            return response()->json(['data' => $atendimento]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agendamento_id' => 'required|exists:agendamentos,id',
            'data_hora' => 'nullable|date',
            'confirmado' => 'boolean'
        ]);

        try {
            $atendimento = $this->service->createAtendimento($validated);
            
            return response()->json([
                'data' => $atendimento,
                'message' => 'Atendimento criado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'data_hora' => 'sometimes|date',
            'confirmado' => 'sometimes|boolean'
        ]);

        try {
            $updated = $this->service->updateAtendimento($id, $validated);
            
            if (!$updated) {
                return response()->json(['message' => 'Atendimento não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Atendimento atualizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteAtendimento($id);
            
            if (!$deleted) {
                return response()->json(['message' => 'Atendimento não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Atendimento removido com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function confirmar(int $id): JsonResponse
    {
        try {
            $confirmado = $this->service->confirmarAtendimento($id);
            
            if (!$confirmado) {
                return response()->json(['message' => 'Erro ao confirmar atendimento'], 400);
            }
            
            return response()->json(['message' => 'Atendimento confirmado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancelar(int $id): JsonResponse
    {
        try {
            $cancelado = $this->service->cancelarAtendimento($id);
            
            if (!$cancelado) {
                return response()->json(['message' => 'Erro ao cancelar atendimento'], 400);
            }
            
            return response()->json(['message' => 'Atendimento cancelado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function createFromAgendamento(int $agendamentoId): JsonResponse
    {
        try {
            $atendimento = $this->service->createFromAgendamento($agendamentoId);
            
            return response()->json([
                'data' => $atendimento,
                'message' => 'Atendimento criado a partir do agendamento'
            ], 201);
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

    public function relatorioProfissional(Request $request, int $profissionalId): JsonResponse
    {
        $validated = $request->validate([
            'inicio' => 'required|date',
            'fim' => 'required|date|after_or_equal:inicio'
        ]);

        try {
            $relatorio = $this->service->getRelatorioProfissional(
                $profissionalId,
                $validated['inicio'],
                $validated['fim']
            );
            
            return response()->json(['data' => $relatorio]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function relatorioCompleto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inicio' => 'required|date',
            'fim' => 'required|date|after_or_equal:inicio'
        ]);

        try {
            $relatorio = $this->service->gerarRelatorioCompleto(
                $validated['inicio'],
                $validated['fim']
            );
            
            return response()->json(['data' => $relatorio]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function processarAtrasados(): JsonResponse
    {
        try {
            $processados = $this->service->processarAtendimentosAtrasados();
            
            return response()->json([
                'data' => $processados,
                'message' => 'Atendimentos atrasados processados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}