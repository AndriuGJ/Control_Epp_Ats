<?php

namespace Database\Factories;

use App\Models\PreguntaAts;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PreguntaAts> */
class PreguntaAtsFactory extends Factory
{
    protected $model = PreguntaAts::class;

    public function definition(): array
    {
        return ['pregunta' => fake()->sentence().'?', 'etapa' => 'Inicial', 'posicion' => 1, 'estado' => true];
    }
}
