<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_maquinas', function (Blueprint $tabla): void {
            $tabla->foreignId('id_usuario')->constrained('users')->cascadeOnDelete();
            $tabla->foreignId('id_maquina')->constrained('maquinas', 'id_maquina')->cascadeOnDelete();
            $tabla->primary(['id_usuario', 'id_maquina']);
            $tabla->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_maquinas');
    }
};
