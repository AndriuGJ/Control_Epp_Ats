<?php

namespace Database\Factories;

use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
use App\Models\RespuestaAts;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RespuestaAts> */
class RespuestaAtsFactory extends Factory
{
    protected $model = RespuestaAts::class;

    public function definition(): array
    {
        return ['id_registro' => RegistroAtsEpp::factory(), 'id_pregunta' => PreguntaAts::factory(), 'respuesta' => 'Sí'];
    }
}
