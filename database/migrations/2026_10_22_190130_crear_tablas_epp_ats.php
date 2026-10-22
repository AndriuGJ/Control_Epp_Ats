<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maquinas', function (Blueprint $tabla): void {
            $tabla->id('id_maquina');
            $tabla->string('nombre', 100);
            $tabla->text('descripcion')->nullable();
            $tabla->string('fotografia')->nullable();
            $tabla->boolean('estado')->default(true);
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
        });
        Schema::create('usuarios_maquinas', function (Blueprint $tabla): void {
            $tabla->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnDelete();
            $tabla->foreignId('id_maquina')->constrained('maquinas', 'id_maquina')->cascadeOnDelete();
            $tabla->primary(['id_usuario', 'id_maquina']);
        });
        Schema::create('preguntas_ats', function (Blueprint $tabla): void {
            $tabla->id('id_pregunta');
            $tabla->string('pregunta');
            $tabla->string('etapa', 10);
            $tabla->unsignedInteger('posicion');
            $tabla->boolean('estado')->default(true);
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
        });
        Schema::create('registros_ats_epp', function (Blueprint $tabla): void {
            $tabla->id('id_registro');
            $tabla->foreignId('id_operador')->constrained('usuarios', 'id_usuario')->restrictOnDelete();
            $tabla->foreignId('id_maquina')->constrained('maquinas', 'id_maquina')->restrictOnDelete();
            $tabla->foreignId('id_revisor')->nullable()->constrained('usuarios', 'id_usuario')->restrictOnDelete();
            $tabla->string('fotografia_epp');
            $tabla->dateTime('fecha_fotografia');
            $tabla->string('firma_inicial');
            $tabla->dateTime('fecha_inicio');
            $tabla->string('firma_final')->nullable();
            $tabla->dateTime('fecha_finalizacion')->nullable();
            $tabla->string('estado', 20)->default('En curso');
            $tabla->string('firma_revisor')->nullable();
            $tabla->dateTime('fecha_revision')->nullable();
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
            $tabla->index(['id_operador', 'estado']);
            $tabla->index('fecha_inicio');
        });
        Schema::create('respuestas_ats', function (Blueprint $tabla): void {
            $tabla->id('id_respuesta');
            $tabla->foreignId('id_registro')->constrained('registros_ats_epp', 'id_registro')->cascadeOnDelete();
            $tabla->foreignId('id_pregunta')->constrained('preguntas_ats', 'id_pregunta')->restrictOnDelete();
            $tabla->text('respuesta');
            $tabla->timestamp('creado_en')->nullable();
            $tabla->timestamp('actualizado_en')->nullable();
            $tabla->unique(['id_registro', 'id_pregunta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_ats');
        Schema::dropIfExists('registros_ats_epp');
        Schema::dropIfExists('preguntas_ats');
        Schema::dropIfExists('usuarios_maquinas');
        Schema::dropIfExists('maquinas');
    }
};
