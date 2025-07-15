<?php

namespace App\Http\Controllers;

use App\Services\PacienteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PacienteController extends Controller
{
    public function __construct(
        protected PacienteService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $tipoPagamento = $request->query('tipo_pagamento');
            $paginate = $request->query('paginate', true);

            if ($search) {
                $pacientes = $this->service->searchPacientes($search);
                return response()->json(['data' => $pacientes]);
            }

            if ($tipoPagamento) {
                $pacientes = $this->service->getPacientesByTipoPagamento($tipoPagamento);
                return response()->json(['data' => $pacientes]);
            }

            if ($paginate === 'false') {
                $pacientes = $this->service->getAllPacientes();
                return response()->json(['data' => $pacientes]);
            }

            $perPage = (int) $request->query('per_page', 15);
            $pacientes = $this->service->getPaginatedPacientes($perPage);
            
            return response()->json($pacientes);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $paciente = $this->service->getPacienteById($id);
            
            if (!$paciente) {
                return response()->json(['message' => 'Paciente não encontrado'], 404);
            }
            
            return response()->json(['data' => $paciente]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'data_nascimento' => 'required|date|before:today',
                'telefone' => 'required|string|max:20',
                'cpf_responsavel' => 'required|string',
                'nome_responsavel' => 'required|string|max:255',
                'tipo_pagamento' => 'required|in:particular,convenio',
                'valor' => 'nullable|numeric|min:0|required_if:tipo_pagamento,particular'
            ]);

            $paciente = $this->service->createPaciente($validated);
            
            return response()->json([
                'data' => $paciente,
                'message' => 'Paciente criado com sucesso'
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nome' => 'sometimes|string|max:255',
                'data_nascimento' => 'sometimes|date|before:today',
                'telefone' => 'sometimes|string|max:20',
                'cpf_responsavel' => 'sometimes|string',
                'nome_responsavel' => 'sometimes|string|max:255',
                'tipo_pagamento' => 'sometimes|in:particular,convenio',
                'valor' => 'nullable|numeric|min:0'
            ]);

            $updated = $this->service->updatePaciente($id, $validated);
            
            if (!$updated) {
                return response()->json(['message' => 'Paciente não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Paciente atualizado com sucesso']);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->deletePaciente($id);
            
            if (!$deleted) {
                return response()->json(['message' => 'Paciente não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Paciente removido com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}