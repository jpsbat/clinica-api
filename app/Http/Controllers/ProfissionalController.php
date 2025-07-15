<?php

namespace App\Http\Controllers;

use App\Services\ProfissionalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfissionalController extends Controller
{
    public function __construct(
        protected ProfissionalService $service
    ) {}

    public function index(): JsonResponse
    {
        $profissionais = $this->service->getAllProfissionais();
        
        return response()->json([
            'data' => $profissionais
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $profissional = $this->service->getProfissionalById($id);
        
        if (!$profissional) {
            return response()->json(['message' => 'Profissional não encontrado'], 404);
        }
        
        return response()->json([
            'data' => $profissional
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'especialidade' => 'required|string|max:255',
            'percentual_repassado' => 'required|numeric|min:0|max:100'
        ]);

        try {
            $profissional = $this->service->createProfissional($validated);
            
            return response()->json([
                'data' => $profissional,
                'message' => 'Profissional criado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'especialidade' => 'sometimes|string|max:255',
            'percentual_repassado' => 'sometimes|numeric|min:0|max:100'
        ]);

        try {
            $updated = $this->service->updateProfissional($id, $validated);
            
            if (!$updated) {
                return response()->json(['message' => 'Profissional não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Profissional atualizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->deleteProfissional($id);
        
        if (!$deleted) {
            return response()->json(['message' => 'Profissional não encontrado'], 404);
        }
        
        return response()->json(['message' => 'Profissional removido com sucesso']);
    }
}