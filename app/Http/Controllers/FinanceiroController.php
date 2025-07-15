<?php

namespace App\Http\Controllers;

use App\Services\FinanceiroService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FinanceiroController extends Controller
{
    public function __construct(
        protected FinanceiroService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $ano = $request->query('ano');
            $mes = $request->query('mes');
            $profissional = $request->query('profissional_id');
            $paginate = $request->query('paginate', true);

            if ($ano) {
                $financeiros = $this->service->getFinanceirosByAno((int) $ano);
                return response()->json(['data' => $financeiros]);
            }

            if ($mes) {
                $financeiros = $this->service->getFinanceirosByMes($mes);
                return response()->json(['data' => $financeiros]);
            }

            if ($profissional) {
                $financeiros = $this->service->getFinanceirosByProfissional((int) $profissional);
                return response()->json(['data' => $financeiros]);
            }

            if ($paginate === 'false') {
                $financeiros = $this->service->getAllFinanceiros();
                return response()->json(['data' => $financeiros]);
            }

            $perPage = (int) $request->query('per_page', 15);
            $financeiros = $this->service->getPaginatedFinanceiros($perPage);
            
            return response()->json($financeiros);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $financeiro = $this->service->getFinanceiroById($id);
            
            if (!$financeiro) {
                return response()->json(['message' => 'Registro financeiro não encontrado'], 404);
            }
            
            return response()->json(['data' => $financeiro]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profissional_id' => 'required|exists:profissionais,id',
            'mes_referencia' => 'required|string|regex:/^\d{4}-\d{1,2}$/',
            'total_atendimentos' => 'required|integer|min:0',
            'valor_bruto' => 'required|numeric|min:0'
        ]);

        try {
            $financeiro = $this->service->createFinanceiro($validated);
            
            return response()->json([
                'data' => $financeiro,
                'message' => 'Registro financeiro criado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'profissional_id' => 'sometimes|exists:profissionais,id',
            'mes_referencia' => 'sometimes|string|regex:/^\d{4}-\d{1,2}$/',
            'total_atendimentos' => 'sometimes|integer|min:0',
            'valor_bruto' => 'sometimes|numeric|min:0'
        ]);

        try {
            $updated = $this->service->updateFinanceiro($id, $validated);
            
            if (!$updated) {
                return response()->json(['message' => 'Registro financeiro não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Registro financeiro atualizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteFinanceiro($id);
            
            if (!$deleted) {
                return response()->json(['message' => 'Registro financeiro não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Registro financeiro removido com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function resumoMensal(string $mes): JsonResponse
    {
        try {
            $resumo = $this->service->getResumoMensal($mes);
            return response()->json(['data' => $resumo]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function resumoAnual(int $ano): JsonResponse
    {
        try {
            $resumo = $this->service->getResumoAnual($ano);
            return response()->json(['data' => $resumo]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function mesesDisponiveis(): JsonResponse
    {
        try {
            $meses = $this->service->getMesesDisponiveis();
            return response()->json(['data' => $meses]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function relatorioCompleto(string $mes): JsonResponse
    {
        try {
            $relatorio = $this->service->gerarRelatorioCompleto($mes);
            return response()->json(['data' => $relatorio]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}