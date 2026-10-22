<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas_ats', function (Blueprint $tabla): void {
            $tabla->id('id_respuesta');
            $tabla->foreignId('id_registro')->constrained('registros_ats_epp', 'id_registro')->cascadeOnDelete();
            $tabla->foreignId('id_pregunta')->constrained('preguntas_ats', 'id_pregunta')->restrictOnDelete();
            $tabla->text('respuesta');
            $tabla->timestamps();
            $tabla->unique(['id_registro', 'id_pregunta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_ats');
    }
};
