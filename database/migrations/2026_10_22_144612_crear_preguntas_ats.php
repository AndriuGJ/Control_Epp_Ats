<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas_ats', function (Blueprint $tabla): void {
            $tabla->id('id_pregunta');
            $tabla->string('pregunta');
            $tabla->string('etapa', 10);
            $tabla->unsignedInteger('posicion');
            $tabla->boolean('estado')->default(true);
            $tabla->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas_ats');
    }
};
