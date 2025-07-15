<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class Usuario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nome',
        'email',
        'password',
        'tipo'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'tipo' => 'string',
        'email_verified_at' => 'datetime'
    ];

    // Tipos de usuário disponíveis
    public const TIPOS = [
        'admin' => 'Administrador',
        'gestao' => 'Gestão',
        'recepcao' => 'Recepção'
    ];

    // Mutator para hash da senha
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    // Accessor para nome do tipo
    public function getTipoNomeAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    // Verificar se o usuário é admin
    public function isAdmin(): bool
    {
        return $this->tipo === 'admin';
    }

    // Verificar se o usuário é gestão
    public function isGestao(): bool
    {
        return $this->tipo === 'gestao';
    }

    // Verificar se o usuário é recepção
    public function isRecepcao(): bool
    {
        return $this->tipo === 'recepcao';
    }

    // Verificar se pode gerenciar usuários
    public function canManageUsers(): bool
    {
        return in_array($this->tipo, ['admin', 'gestao']);
    }

    // Verificar se pode acessar relatórios financeiros
    public function canAccessFinancial(): bool
    {
        return in_array($this->tipo, ['admin', 'gestao']);
    }

    // Verificar senha
    public function checkPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }
}