<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Agendamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'paciente_id',
        'profissional_id',
        'data_hora',
        'recorrente',
        'dia_semana'
    ];

    protected $casts = [
        'data_hora' => 'datetime',
        'recorrente' => 'boolean'
    ];

    // Dias da semana disponíveis
    public const DIAS_SEMANA = [
        'segunda' => 'Segunda-feira',
        'terca' => 'Terça-feira',
        'quarta' => 'Quarta-feira',
        'quinta' => 'Quinta-feira',
        'sexta' => 'Sexta-feira',
        'sabado' => 'Sábado',
        'domingo' => 'Domingo'
    ];

    // Status do agendamento
    public const STATUS = [
        'agendado' => 'Agendado',
        'confirmado' => 'Confirmado',
        'realizado' => 'Realizado',
        'cancelado' => 'Cancelado',
        'faltou' => 'Paciente Faltou'
    ];

    // Relacionamentos
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function profissional()
    {
        return $this->belongsTo(Profissional::class);
    }

    public function atendimentos()
    {
        return $this->hasMany(Atendimento::class);
    }

    // Accessor para dia da semana em português
    public function getDiaSemanaNomeAttribute(): string
    {
        if ($this->dia_semana) {
            return self::DIAS_SEMANA[$this->dia_semana] ?? $this->dia_semana;
        }
        
        Carbon::setLocale('pt_BR');
        return Carbon::parse($this->data_hora)->translatedFormat('l');
    }

    // Accessor para status baseado na data e atendimentos
    public function getStatusAttribute(): string
    {
        // Se tem atendimento confirmado
        if ($this->atendimentos()->where('confirmado', true)->exists()) {
            return 'realizado';
        }

        // Se já passou da data/hora
        if (Carbon::parse($this->data_hora)->lt(now())) {
            return 'faltou';
        }

        // Se está próximo (menos de 2 horas)
        if (Carbon::parse($this->data_hora)->diffInHours(now()) <= 2 && Carbon::parse($this->data_hora)->isFuture()) {
            return 'confirmado';
        }

        return 'agendado';
    }

    // Accessor para cor do status
    public function getStatusCorAttribute(): string
    {
        return match($this->status) {
            'agendado' => 'blue',
            'confirmado' => 'orange',
            'realizado' => 'green',
            'cancelado' => 'red',
            'faltou' => 'gray',
            default => 'blue'
        };
    }

    // Verificar se pode ser editado
    public function canBeEdited(): bool
    {
        return $this->data_hora > now() && !$this->atendimentos()->exists();
    }

    // Verificar se pode ser cancelado
    public function canBeCancelled(): bool
    {
        return Carbon::parse($this->data_hora)->gt(now());
    }

    // Scopes
    public function scopeByPaciente($query, int $pacienteId)
    {
        return $query->where('paciente_id', $pacienteId);
    }

    public function scopeByProfissional($query, int $profissionalId)
    {
        return $query->where('profissional_id', $profissionalId);
    }

    public function scopeByData($query, Carbon $data)
    {
        return $query->whereDate('data_hora', $data->toDateString());
    }

    public function scopeByPeriodo($query, Carbon $inicio, Carbon $fim)
    {
        return $query->whereBetween('data_hora', [$inicio, $fim]);
    }

    public function scopeRecorrentes($query)
    {
        return $query->where('recorrente', true);
    }

    public function scopeFuturos($query)
    {
        return $query->where('data_hora', '>', now());
    }

    public function scopePassados($query)
    {
        return $query->where('data_hora', '<', now());
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data_hora', today());
    }
}