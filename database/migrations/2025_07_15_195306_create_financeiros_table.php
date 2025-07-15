<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financeiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profissional_id')->constrained();
            $table->string('mes_referencia');
            $table->integer('total_atendimentos');
            $table->decimal('valor_bruto', 8, 2);
            $table->decimal('valor_repassado', 8, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiros');
    }
};