<?php

namespace Database\Factories;

use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mantenimiento> */
class MantenimientoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_maquina' => Maquina::factory(),
            'id_usuario' => User::factory(),
            'fecha_mantenimiento' => now(),
            'minutos_uso_acumulados' => 0,
        ];
    }
}
