<?php

namespace Database\Factories;

use App\Models\Maquina;
use App\Models\User;
use App\Models\ValidacionEpp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ValidacionEpp> */
class ValidacionEppFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_operador' => User::factory(),
            'id_maquina' => Maquina::factory(),
            'token_captura' => (string) Str::uuid(),
            'fotografia' => 'registros/epp/'.Str::uuid().'.png',
            'fecha_fotografia' => now(),
            'estado' => ValidacionEpp::PENDIENTE,
            'vence_en' => now()->addMinutes(30),
        ];
    }

    public function aprobada(): static
    {
        return $this->state(fn (): array => [
            'estado' => ValidacionEpp::APROBADA,
            'id_supervisor' => User::factory(),
            'fecha_revision' => now(),
            'vence_en' => now()->addMinutes(15),
        ]);
    }
}
