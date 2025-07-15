<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'data_nascimento',
        'telefone',
        'cpf_responsavel',
        'nome_responsavel',
        'tipo_pagamento',
        'valor'
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'valor' => 'decimal:2',
        'tipo_pagamento' => 'string'
    ];

    // Relacionamentos
    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    // Método para idade
    public function calcularIdade(): ?int
    {
        if (!$this->data_nascimento) {
            return null;
        }

        return Carbon::parse((string) $this->data_nascimento)->diffInYears(Carbon::now());
    }

    // Mutator para CPF (remove formatação)
    public function setCpfResponsavelAttribute($value): void
    {
        $this->attributes['cpf_responsavel'] = preg_replace('/\D/', '', $value);
    }

    // Mutator para telefone (remove formatação)
    public function setTelefoneAttribute($value): void
    {
        $this->attributes['telefone'] = preg_replace('/\D/', '', $value);
    }
}