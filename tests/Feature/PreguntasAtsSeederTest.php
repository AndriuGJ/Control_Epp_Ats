<?php

use App\Models\PreguntaAts;
use App\Models\RespuestaAts;
use Database\Seeders\PreguntasAtsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('carga diez preguntas activas de inicio y diez finales ordenadas por etapa', function (): void {
    $this->seed(PreguntasAtsSeeder::class);

    $this->assertDatabaseCount('preguntas_ats', 20);
    foreach (['Inicial', 'Final'] as $etapa) {
        $preguntas = PreguntaAts::query()->where('etapa', $etapa)->orderBy('posicion')->get();
        expect($preguntas)->toHaveCount(10);
        expect($preguntas->pluck('posicion')->all())->toBe(range(1, 10));
        expect($preguntas->every(fn (PreguntaAts $pregunta): bool => $pregunta->estado))->toBeTrue();
    }
});

it('puede repetirse sin duplicar preguntas ni cambiar sus fechas', function (): void {
    $this->freezeTime();
    $this->seed(PreguntasAtsSeeder::class);
    $anteriores = PreguntaAts::query()->orderBy('id_pregunta')->get()->toArray();
    $this->travel(1)->hours();

    $this->seed(PreguntasAtsSeeder::class);

    $this->assertDatabaseCount('preguntas_ats', 20);
    expect(PreguntaAts::query()->orderBy('id_pregunta')->get()->toArray())->toBe($anteriores);
});

it('conserva las preguntas de la empresa y agrega las nuevas después de sus posiciones', function (): void {
    $inicial = PreguntaAts::factory()->create(['pregunta' => 'Pregunta inicial de la empresa', 'etapa' => 'Inicial', 'posicion' => 50, 'estado' => false]);
    $final = PreguntaAts::factory()->create(['pregunta' => 'Pregunta final de la empresa', 'etapa' => 'Final', 'posicion' => 75]);
    $respuesta = RespuestaAts::factory()->create(['id_pregunta' => $inicial->getKey(), 'respuesta' => 'No']);

    $this->seed(PreguntasAtsSeeder::class);

    $this->assertDatabaseCount('preguntas_ats', 22);
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $inicial->getKey(), 'pregunta' => 'Pregunta inicial de la empresa', 'etapa' => 'Inicial', 'posicion' => 50, 'estado' => false]);
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $final->getKey(), 'pregunta' => 'Pregunta final de la empresa', 'etapa' => 'Final', 'posicion' => 75]);
    expect(PreguntaAts::query()->where('etapa', 'Inicial')->whereKeyNot($inicial->getKey())->orderBy('posicion')->pluck('posicion')->all())->toBe(range(51, 60));
    expect(PreguntaAts::query()->where('etapa', 'Final')->whereKeyNot($final->getKey())->orderBy('posicion')->pluck('posicion')->all())->toBe(range(76, 85));
    expect($respuesta->fresh()->respuesta)->toBe('No');
});

it('no reactiva ni reordena preguntas sembradas que ya tienen respuestas', function (): void {
    $this->seed(PreguntasAtsSeeder::class);
    $pregunta = PreguntaAts::query()->where('etapa', 'Inicial')->orderBy('posicion')->firstOrFail();
    $pregunta->update(['estado' => false, 'posicion' => 90]);
    $respuesta = RespuestaAts::factory()->create(['id_pregunta' => $pregunta->getKey()]);
    $anterior = $pregunta->fresh()->toArray();

    $this->seed(PreguntasAtsSeeder::class);

    $this->assertDatabaseCount('preguntas_ats', 20);
    expect($pregunta->fresh()->toArray())->toBe($anterior);
    expect($respuesta->fresh()->id_pregunta)->toBe($pregunta->getKey());
});
