<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return ['nombres' => fake()->firstName(), 'apellidos' => fake()->lastName(), 'dni' => fake()->unique()->numerify('########'), 'correo' => fake()->unique()->safeEmail(), 'contrasena' => 'ClavePruebas123!', 'estado' => true];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => ['correo_verificado_en' => null]);
    }
}
