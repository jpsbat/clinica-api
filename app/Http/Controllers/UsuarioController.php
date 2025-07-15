<?php

namespace App\Http\Controllers;

use App\Services\UsuarioService;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UsuarioController extends Controller
{
    public function __construct(
        protected UsuarioService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $search = $request->query('search');
            $tipo = $request->query('tipo');
            $paginate = $request->query('paginate', true);

            if ($search) {
                $usuarios = $this->service->searchUsuarios($search);
                return response()->json(['data' => $usuarios]);
            }

            if ($tipo) {
                $usuarios = $this->service->getUsuariosByTipo($tipo);
                return response()->json(['data' => $usuarios]);
            }

            if ($paginate === 'false') {
                $usuarios = $this->service->getAllUsuarios();
                return response()->json(['data' => $usuarios]);
            }

            $perPage = (int) $request->query('per_page', 15);
            $usuarios = $this->service->getPaginatedUsuarios($perPage);
            
            return response()->json($usuarios);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $usuario = $this->service->getUsuarioById($id);
            
            if (!$usuario) {
                return response()->json(['message' => 'Usuário não encontrado'], 404);
            }
            
            return response()->json(['data' => $usuario]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:usuarios,email',
            'password' => 'required|string|min:8',
            'tipo' => 'required|in:admin,gestao,recepcao'
        ]);

        try {
            $usuario = $this->service->createUsuario($validated);
            
            return response()->json([
                'data' => $usuario,
                'message' => 'Usuário criado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:usuarios,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'tipo' => 'sometimes|in:admin,gestao,recepcao'
        ]);

        try {
            $updated = $this->service->updateUsuario($id, $validated);
            
            if (!$updated) {
                return response()->json(['message' => 'Usuário não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Usuário atualizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteUsuario($id);
            
            if (!$deleted) {
                return response()->json(['message' => 'Usuário não encontrado'], 404);
            }
            
            return response()->json(['message' => 'Usuário removido com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function changePassword(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $changed = $this->service->changePassword(
                $id,
                $validated['current_password'],
                $validated['new_password']
            );
            
            if (!$changed) {
                return response()->json(['message' => 'Erro ao alterar senha'], 400);
            }
            
            return response()->json(['message' => 'Senha alterada com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:8|confirmed'
        ]);

        try {
            $reset = $this->service->resetPassword($id, $validated['new_password']);
            
            if (!$reset) {
                return response()->json(['message' => 'Erro ao resetar senha'], 400);
            }
            
            return response()->json(['message' => 'Senha resetada com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->service->getStatistics();
            return response()->json(['data' => $stats]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function tipos(): JsonResponse
    {
        return response()->json([
            'data' => Usuario::TIPOS
        ]);
    }
}