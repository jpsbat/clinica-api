<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profissional extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'profissionais';

    protected $fillable = [
        'nome',
        'especialidade',
        'percentual_repassado'
    ];

    protected $casts = [
        'percentual_repassado' => 'decimal:2'
    ];

    // Relacionamentos
    public function agendamentos()
    {
        return $this->hasMany(Agendamento::class);
    }

    public function financeiros()
    {
        return $this->hasMany(Financeiro::class);
    }
}