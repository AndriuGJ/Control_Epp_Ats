<?php

use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
use App\Models\RespuestaAts;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
it('distingue al operador y al revisor del mismo registro', function (): void {
    $operador = User::factory()->create();
    $revisor = User::factory()->create();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey(), 'id_revisor' => $revisor->getKey()]);
    expect($registro->operador->is($operador))->toBeTrue();
    expect($registro->revisor->is($revisor))->toBeTrue();
    expect($operador->registros()->sole()->is($registro))->toBeTrue();
    expect($revisor->revisiones()->sole()->is($registro))->toBeTrue();
});
it('permite guardar el inicio sin cierre ni revisión', function (): void {
    $registro = RegistroAtsEpp::factory()->create();
    expect($registro->fresh()->estado)->toBe('En curso');
    expect($registro->fresh()->fecha_finalizacion)->toBeNull();
    expect($registro->fresh()->id_revisor)->toBeNull();
});
it('rechaza una autorización duplicada', function (): void {
    $operador = User::factory()->create();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    expect(fn () => $operador->maquinas()->attach($maquina))->toThrow(QueryException::class);
});
it('rechaza dos respuestas a la misma pregunta en un registro', function (): void {
    $respuesta = RespuestaAts::factory()->create();
    expect(fn () => RespuestaAts::create(['id_registro' => $respuesta->id_registro, 'id_pregunta' => $respuesta->id_pregunta, 'respuesta' => 'No']))->toThrow(QueryException::class);
});
it('conserva la integridad de las claves foráneas', function (): void {
    $pregunta = PreguntaAts::factory()->create();
    expect(fn () => RespuestaAts::create(['id_registro' => 999999, 'id_pregunta' => $pregunta->getKey(), 'respuesta' => 'Sí']))->toThrow(QueryException::class);
});
it('puede ejecutar el seeder de roles más de una vez sin duplicar', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $this->seed(RolesPermisosSeeder::class);
    $this->assertDatabaseCount('roles', 3);
    $this->assertDatabaseCount('permisos', 7);
});
