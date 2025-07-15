<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained();
            $table->foreignId('profissional_id')->constrained('profissionais');
            $table->dateTime('data_hora');
            $table->boolean('recorrente')->default(false);
            $table->string('dia_semana')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};