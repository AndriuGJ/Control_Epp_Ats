<?php

namespace Database\Factories;

use App\Models\Maquina;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegistroAtsEpp> */
class RegistroAtsEppFactory extends Factory
{
    protected $model = RegistroAtsEpp::class;

    public function definition(): array
    {
        return ['id_operador' => User::factory(), 'id_maquina' => Maquina::factory(), 'fotografia_epp' => 'epp/foto.jpg', 'fecha_fotografia' => now(), 'firma_inicial' => 'firmas/inicio.png', 'fecha_inicio' => now(), 'estado' => 'En curso'];
    }

    public function finalizado(): static
    {
        return $this->state(fn (): array => [
            'estado' => 'Finalizado',
            'firma_final' => 'firmas/final.png',
            'fecha_finalizacion' => now(),
        ]);
    }
}
