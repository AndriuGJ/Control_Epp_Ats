<?php

namespace Database\Factories;

use App\Models\Maquina;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Maquina> */
class MaquinaFactory extends Factory
{
    protected $model = Maquina::class;

    public function definition(): array
    {
        return ['nombre' => fake()->unique()->bothify('Transpaleta ##??'), 'descripcion' => 'Equipo de logística', 'estado' => true];
    }
}
