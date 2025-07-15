<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Financeiro extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'profissional_id',
        'mes_referencia',
        'total_atendimentos',
        'valor_bruto',
        'valor_repassado'
    ];

    protected $casts = [
        'valor_bruto' => 'decimal:2',
        'valor_repassado' => 'decimal:2',
        'total_atendimentos' => 'integer'
    ];

    // Relacionamentos
    public function profissional()
    {
        return $this->belongsTo(Profissional::class);
    }

    // Accessor para valor retido pela clínica
    public function getValorRetidoAttribute(): float
    {
        return $this->valor_bruto - $this->valor_repassado;
    }

    // Accessor para percentual efetivo repassado
    public function getPercentualEfetivoAttribute(): float
    {
        if ($this->valor_bruto == 0) {
            return 0;
        }
        
        return ($this->valor_repassado / $this->valor_bruto) * 100;
    }

    // Accessor para valor médio por atendimento
    public function getValorMedioAtendimentoAttribute(): float
    {
        if ($this->total_atendimentos == 0) {
            return 0;
        }
        
        return $this->valor_bruto / $this->total_atendimentos;
    }

    // Mutator para formatar mês de referência
    public function setMesReferenciaAttribute($value): void
    {
        // Converte para formato YYYY-MM se necessário
        if (is_string($value) && strlen($value) === 7) {
            $this->attributes['mes_referencia'] = $value;
        } else {
            $carbon = Carbon::parse($value);
            $this->attributes['mes_referencia'] = $carbon->format('Y-m');
        }
    }

    // Accessor para nome do mês
    public function getMesNomeAttribute(): string
    {
        $carbon = Carbon::createFromFormat('Y-m', $this->mes_referencia);
        return $carbon->locale('pt_BR')->isoFormat('MMMM [de] YYYY');
    }

    // Scope para filtrar por ano
    public function scopeByAno($query, int $ano)
    {
        return $query->where('mes_referencia', 'LIKE', $ano . '%');
    }

    // Scope para filtrar por mês específico
    public function scopeByMes($query, string $mesReferencia)
    {
        return $query->where('mes_referencia', $mesReferencia);
    }

    // Scope para filtrar por profissional
    public function scopeByProfissional($query, int $profissionalId)
    {
        return $query->where('profissional_id', $profissionalId);
    }
}