<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AtendimentoController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/users', function (Request $request) {
    return [
      'data' => [
        ['id' => 1, 'name' => 'John Doe'],
        ['id' => 2, 'name' => 'Jane Smith']
      ]
    ];
});

// Profissionais
Route::apiResource('profissionais', ProfissionalController::class);

// Pacientes
Route::apiResource('pacientes', PacienteController::class);

// Usuários
Route::apiResource('usuarios', UsuarioController::class);

// Usuários
Route::put('usuarios/{id}/change-password', [UsuarioController::class, 'changePassword']);
Route::put('usuarios/{id}/reset-password', [UsuarioController::class, 'resetPassword']);
Route::get('usuarios-statistics', [UsuarioController::class, 'statistics']);
Route::get('usuarios-tipos', [UsuarioController::class, 'tipos']);

// Financeiros
Route::apiResource('financeiros', FinanceiroController::class);

// Financeiros
Route::get('financeiros-resumo-mensal/{mes}', [FinanceiroController::class, 'resumoMensal']);
Route::get('financeiros-resumo-anual/{ano}', [FinanceiroController::class, 'resumoAnual']);
Route::get('financeiros-meses-disponiveis', [FinanceiroController::class, 'mesesDisponiveis']);
Route::get('financeiros-relatorio-completo/{mes}', [FinanceiroController::class, 'relatorioCompleto']);

// Agendamentos
Route::apiResource('agendamentos', AgendamentoController::class);

// Agendamentos
Route::put('agendamentos/{id}/confirmar', [AgendamentoController::class, 'confirmar']);
Route::get('agendamentos-estatisticas', [AgendamentoController::class, 'estatisticas']);
Route::get('agendamentos-dias-semana', [AgendamentoController::class, 'diasSemana']);

// Atendimentos
Route::apiResource('atendimentos', AtendimentoController::class);

// Atendimentos
Route::put('atendimentos/{id}/confirmar', [AtendimentoController::class, 'confirmar']);
Route::put('atendimentos/{id}/cancelar', [AtendimentoController::class, 'cancelar']);
Route::post('atendimentos/agendamento/{agendamento_id}', [AtendimentoController::class, 'createFromAgendamento']);
Route::get('atendimentos-estatisticas', [AtendimentoController::class, 'estatisticas']);
Route::get('atendimentos-relatorio-profissional/{profissional_id}', [AtendimentoController::class, 'relatorioProfissional']);
Route::get('atendimentos-relatorio-completo', [AtendimentoController::class, 'relatorioCompleto']);
Route::post('atendimentos-processar-atrasados', [AtendimentoController::class, 'processarAtrasados']);