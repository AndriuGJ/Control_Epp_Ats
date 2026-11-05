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
            $tabla->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maquinas');
    }
};
