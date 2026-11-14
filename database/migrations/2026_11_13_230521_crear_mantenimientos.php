<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id('id_mantenimiento');
            $table->foreignId('id_maquina')->constrained('maquinas', 'id_maquina')->restrictOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_mantenimiento');
            $table->unsignedBigInteger('minutos_uso_acumulados');
            $table->text('observaciones')->nullable();
            $table->unique(['id_maquina', 'fecha_mantenimiento']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
