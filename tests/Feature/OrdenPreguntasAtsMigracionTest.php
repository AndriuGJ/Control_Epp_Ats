<?php

use App\Models\PreguntaAts;
use App\Models\RespuestaAts;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

it('corrige posiciones antiguas duplicadas conservando identificadores y respuestas', function (): void {
    $migracion = require database_path('migrations/2026_10_31_120000_asegurar_posicion_unica_por_etapa_en_preguntas_ats.php');
    $migracion->down();
    $primera = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);
    $duplicada = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);
    $tercera = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 2]);
    $final = PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 1]);
    $respuesta = RespuestaAts::factory()->create(['id_pregunta' => $duplicada->getKey()]);

    $migracion->up();

    expect($primera->fresh()->posicion)->toBe(1)
        ->and($duplicada->fresh()->posicion)->toBe(3)
        ->and($tercera->fresh()->posicion)->toBe(2)
        ->and($final->fresh()->posicion)->toBe(1)
        ->and($respuesta->fresh()->id_pregunta)->toBe($duplicada->getKey());

    expect(fn () => PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]))
        ->toThrow(UniqueConstraintViolationException::class);
});
