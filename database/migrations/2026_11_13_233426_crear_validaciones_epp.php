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
        Schema::create('validaciones_epp', function (Blueprint $table) {
            $table->id('id_validacion_epp');
            $table->foreignId('id_operador')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_maquina')->constrained('maquinas', 'id_maquina')->restrictOnDelete();
            $table->foreignId('id_supervisor')->nullable()->constrained('users')->restrictOnDelete();
            $table->uuid('token_captura')->unique();
            $table->string('fotografia');
            $table->dateTime('fecha_fotografia');
            $table->string('estado', 20)->default('Pendiente');
            $table->dateTime('fecha_revision')->nullable();
            $table->dateTime('vence_en');
            $table->text('motivo_rechazo')->nullable();
            $table->dateTime('usada_en')->nullable();
            $table->index(['id_operador', 'id_validacion_epp']);
            $table->index(['estado', 'vence_en']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validaciones_epp');
    }
};
