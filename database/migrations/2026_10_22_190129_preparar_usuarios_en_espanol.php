<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columnas = [
        'id' => 'id_usuario', 'name' => 'nombres', 'email' => 'correo',
        'password' => 'contrasena', 'email_verified_at' => 'correo_verificado_en',
        'remember_token' => 'token_recuerdo', 'created_at' => 'creado_en', 'updated_at' => 'actualizado_en',
    ];

    public function up(): void
    {
        Schema::rename('users', 'usuarios');
        foreach ($this->columnas as $original => $nuevo) {
            Schema::table('usuarios', fn (Blueprint $tabla) => $tabla->renameColumn($original, $nuevo));
        }
        Schema::table('usuarios', function (Blueprint $tabla): void {
            $tabla->string('apellidos', 100)->default('');
            $tabla->string('dni', 8)->nullable()->unique();
            $tabla->boolean('estado')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $tabla): void {
            $tabla->dropUnique(['dni']);
            $tabla->dropColumn(['apellidos', 'dni', 'estado']);
        });
        foreach ($this->columnas as $original => $nuevo) {
            Schema::table('usuarios', fn (Blueprint $tabla) => $tabla->renameColumn($nuevo, $original));
        }
        Schema::rename('usuarios', 'users');
    }
};
