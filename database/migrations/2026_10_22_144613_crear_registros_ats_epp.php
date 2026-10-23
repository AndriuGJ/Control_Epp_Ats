<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_ats_epp', function (Blueprint $tabla): void {
            $tabla->id('id_registro');
            $tabla->foreignId('id_operador')->constrained('users')->restrictOnDelete();
            $tabla->foreignId('id_maquina')->constrained('maquinas', 'id_maquina')->restrictOnDelete();
            $tabla->foreignId('id_revisor')->nullable()->constrained('users')->restrictOnDelete();
            $tabla->string('fotografia_epp');
            $tabla->dateTime('fecha_fotografia');
            $tabla->string('firma_inicial');
            $tabla->dateTime('fecha_inicio');
            $tabla->string('firma_final')->nullable();
            $tabla->dateTime('fecha_finalizacion')->nullable();
            $tabla->string('estado', 20)->default('En curso');
            $tabla->string('firma_revisor')->nullable();
            $tabla->dateTime('fecha_revision')->nullable();
            $tabla->timestamps();
            $tabla->index(['id_operador', 'estado']);
            $tabla->index('fecha_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_ats_epp');
    }
};
