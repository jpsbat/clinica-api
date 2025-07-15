<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Atendimento extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agendamento_id',
        'data_hora',
        'confirmado'
    ];

    protected $casts = [
        'data_hora' => 'datetime',
        'confirmado' => 'boolean'
    ];

    // Status do atendimento
    public const STATUS = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'cancelado' => 'Cancelado'
    ];

    // Relacionamentos
    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class);
    }

    // Relacionamentos através do agendamento
    public function paciente()
    {
        return $this->hasOneThrough(
            Paciente::class,
            Agendamento::class,
            'id',
            'id',
            'agendamento_id',
            'paciente_id'
        );
    }

    public function profissional()
    {
        return $this->hasOneThrough(
            Profissional::class,
            Agendamento::class,
            'id',
            'id',
            'agendamento_id',
            'profissional_id'
        );
    }

    // Accessor para status
    public function getStatusAttribute(): string
    {
        if ($this->confirmado) {
            return 'confirmado';
        }

        $dataHora = $this->data_hora instanceof Carbon
            ? $this->data_hora
            : Carbon::parse($this->data_hora);

        if ($dataHora->isPast()) {
            return 'cancelado';
        }

        return 'pendente';
    }

    // Accessor para cor do status
    public function getStatusCorAttribute(): string
    {
        return match($this->status) {
            'pendente' => 'orange',
            'confirmado' => 'green',
            'cancelado' => 'red',
            default => 'gray'
        };
    }

    // Accessor para duração do atendimento (assumindo 50min por sessão)
    public function getDuracaoMinutosAttribute(): int
    {
        return 50; // Padrão de 50 minutos por sessão
    }

    // Accessor para horário de fim
    public function getDataHoraFimAttribute(): Carbon
    {
        $dataHora = $this->data_hora instanceof Carbon
            ? $this->data_hora
            : Carbon::parse($this->data_hora);

        return $dataHora->copy()->addMinutes($this->duracao_minutos);
    }

    // Verificar se pode ser editado
    public function canBeEdited(): bool
    {
        return !$this->confirmado && Carbon::parse($this->data_hora)->isFuture();
    }

    // Verificar se pode ser confirmado
    public function canBeConfirmed(): bool
    {
        return !$this->confirmado;
    }

    // Verificar se está em atraso
    public function isAtrasado(): bool
    {
        return !$this->confirmado && Carbon::parse($this->data_hora)->isPast();
    }

    // Scopes
    public function scopeConfirmados($query)
    {
        return $query->where('confirmado', true);
    }

    public function scopePendentes($query)
    {
        return $query->where('confirmado', false);
    }

    public function scopeByProfissional($query, int $profissionalId)
    {
        return $query->whereHas('agendamento', function ($q) use ($profissionalId) {
            $q->where('profissional_id', $profissionalId);
        });
    }

    public function scopeByPaciente($query, int $pacienteId)
    {
        return $query->whereHas('agendamento', function ($q) use ($pacienteId) {
            $q->where('paciente_id', $pacienteId);
        });
    }

    public function scopeByData($query, Carbon $data)
    {
        return $query->whereDate('data_hora', $data->toDateString());
    }

    public function scopeByPeriodo($query, Carbon $inicio, Carbon $fim)
    {
        return $query->whereBetween('data_hora', [$inicio, $fim]);
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data_hora', today());
    }

    public function scopeAtrasados($query)
    {
        return $query->where('confirmado', false)
                    ->where('data_hora', '<', now());
    }
}