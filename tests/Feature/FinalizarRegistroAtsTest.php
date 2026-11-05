<?php

use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
use App\Models\RespuestaAts;
use App\Models\User;
use App\Services\RegistroAtsService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function operadorCierreAts(): User
{
    (new RolesPermisosSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->assignRole('operador');

    return $usuario;
}

function datosCierreAts(User $operador, RegistroAtsEpp $registro, array $respuestas): array
{
    return ['firma_final' => firmaAtsPrueba(), 'respuestas' => $respuestas, 'version_preguntas' => app(RegistroAtsService::class)->prepararFinalizacion($operador, $registro->getKey())['versionPreguntas']];
}

it('finaliza el mismo registro conservando el inicio y permite una nueva labor', function (): void {
    Storage::fake('local');
    $this->freezeTime();
    $operador = operadorCierreAts();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey(), 'fecha_inicio' => now()->subHour()]);
    $inicial = PreguntaAts::factory()->create();
    RespuestaAts::factory()->create(['id_registro' => $registro->getKey(), 'id_pregunta' => $inicial->getKey(), 'respuesta' => 'Sí']);
    Storage::disk('local')->put($registro->fotografia_epp, 'foto inicial');
    Storage::disk('local')->put($registro->firma_inicial, 'firma inicial');
    $final = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $inicioAnterior = $registro->fecha_inicio->copy();
    $datos = datosCierreAts($operador, $registro, [$final->getKey() => 'No']);
    $this->actingAs($operador)->get(route('registros.finalizar', $registro))->assertOk()->assertSee('type="radio"', false)->assertSee($final->pregunta)->assertDontSee($inicial->pregunta);
    $this->post(route('registros.finalizar.guardar', $registro), $datos)->assertSessionHasNoErrors()->assertRedirect(route('registros.show', $registro));
    $registro->refresh();
    $this->assertDatabaseCount('registros_ats_epp', 1);
    $this->assertDatabaseHas('registros_ats_epp', ['id_registro' => $registro->getKey(), 'estado' => 'Finalizado', 'fecha_finalizacion' => now()->toDateTimeString(), 'id_revisor' => null]);
    expect($registro->fecha_inicio->equalTo($inicioAnterior))->toBeTrue();
    expect(Storage::disk('local')->get($registro->fotografia_epp))->toBe('foto inicial');
    expect(Storage::disk('local')->get($registro->firma_inicial))->toBe('firma inicial');
    Storage::disk('local')->assertExists($registro->firma_final);
    $this->assertDatabaseHas('respuestas_ats', ['id_registro' => $registro->getKey(), 'id_pregunta' => $inicial->getKey(), 'respuesta' => 'Sí']);
    $this->assertDatabaseHas('respuestas_ats', ['id_registro' => $registro->getKey(), 'id_pregunta' => $final->getKey(), 'respuesta' => 'No']);
    $this->get(route('registros.firma-final', $registro))->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get(route('registros.show', $registro))->assertOk()->assertSee('Firma final del trabajador')->assertDontSee(route('registros.finalizar', $registro));
    $this->get(route('registros.create'))->assertOk();
});

it('impide modificar o finalizar nuevamente un registro cerrado', function (): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey(), 'estado' => 'Finalizado', 'firma_final' => 'firma-original.png', 'fecha_finalizacion' => now()->subMinutes(5)]);
    $final = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $datos = datosCierreAts($operador, $registro, [$final->getKey() => 'Sí']);
    $this->actingAs($operador)->get(route('registros.finalizar', $registro))->assertRedirect(route('registros.show', $registro));
    $this->post(route('registros.finalizar.guardar', $registro), $datos)->assertSessionHasErrors('registro');
    expect($registro->fresh()->firma_final)->toBe('firma-original.png');
    $this->assertDatabaseCount('respuestas_ats', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('exige todas las respuestas finales sin admitir respuestas iniciales o texto libre', function (string $caso): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey()]);
    $inicial = PreguntaAts::factory()->create();
    $primera = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $segunda = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $respuestas = [$primera->getKey() => 'Sí', $segunda->getKey() => 'No'];
    if ($caso === 'falta una') {
        unset($respuestas[$segunda->getKey()]);
    }
    if ($caso === 'incluye inicial') {
        $respuestas[$inicial->getKey()] = 'Sí';
    }
    if ($caso === 'texto libre') {
        $respuestas[$primera->getKey()] = 'Otra respuesta';
    }
    $this->actingAs($operador)->post(route('registros.finalizar.guardar', $registro), datosCierreAts($operador, $registro, $respuestas))->assertSessionHasErrors();
    expect($registro->fresh()->estado)->toBe('En curso');
    $this->assertDatabaseCount('respuestas_ats', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['falta una', 'incluye inicial', 'texto libre']);

it('exige una firma final válida y no la guarda como entrada anterior de sesión', function (bool $incluirFirma): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey()]);
    $pregunta = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $datos = datosCierreAts($operador, $registro, [$pregunta->getKey() => 'Sí']);
    if ($incluirFirma) {
        $datos['firma_final'] = 'data:image/png;base64,aG9sYQ==';
    } else {
        unset($datos['firma_final']);
    }
    $this->actingAs($operador)->post(route('registros.finalizar.guardar', $registro), $datos)->assertSessionHasErrors('firma_final')->assertSessionMissing('_old_input.firma_final');
    expect($registro->fresh()->estado)->toBe('En curso');
    $this->assertDatabaseCount('respuestas_ats', 0);
})->with(['firma inválida' => true, 'sin firma' => false]);

it('permite terminar una labor aunque la máquina haya sido desautorizada o desactivada', function (): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $maquina = Maquina::factory()->create(['estado' => false]);
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey()]);
    $pregunta = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $this->actingAs($operador)->post(route('registros.finalizar.guardar', $registro), datosCierreAts($operador, $registro, [$pregunta->getKey() => 'Sí']))->assertRedirect(route('registros.show', $registro));
    expect($registro->fresh()->estado)->toBe('Finalizado');
});

it('protege el cierre y la firma final de registros de otro trabajador', function (): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $otro = User::factory()->create();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $otro->getKey()]);
    $this->get(route('registros.finalizar', $registro))->assertRedirect(route('login'));
    $this->actingAs($operador)->get(route('registros.finalizar', $registro))->assertNotFound();
    $this->get(route('registros.firma-final', $registro))->assertNotFound();
    $this->post(route('registros.finalizar.guardar', $registro), ['firma_final' => firmaAtsPrueba(), 'respuestas' => [1 => 'Sí'], 'version_preguntas' => str_repeat('a', 64)])->assertNotFound();
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $this->actingAs($supervisor)->post(route('registros.finalizar.guardar', $registro), [])->assertForbidden();
});

it('rechaza un cierre si cambiaron las preguntas finales', function (): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey()]);
    $pregunta = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $datos = datosCierreAts($operador, $registro, [$pregunta->getKey() => 'Sí']);
    $pregunta->update(['pregunta' => 'Pregunta final actualizada']);
    $this->actingAs($operador)->post(route('registros.finalizar.guardar', $registro), $datos)->assertSessionHasErrors('respuestas');
    expect($registro->fresh()->estado)->toBe('En curso');
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('revierte las respuestas finales y la firma nueva si falla el cierre', function (): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey()]);
    $pregunta = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $datos = datosCierreAts($operador, $registro, [$pregunta->getKey() => 'Sí']);
    RegistroAtsEpp::updating(function (): void {
        throw new RuntimeException('Fallo de cierre simulado');
    });
    expect(fn () => app(RegistroAtsService::class)->finalizar($operador, $registro->getKey(), $datos))->toThrow(RuntimeException::class);
    expect($registro->fresh()->estado)->toBe('En curso');
    $this->assertDatabaseCount('respuestas_ats', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('completa el flujo de cámara inicio y cierre sin reutilizar una captura consumida', function (): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $inicial = PreguntaAts::factory()->create(['etapa' => 'Inicial']);
    $final = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $foto = UploadedFile::fake()->createWithContent('captura.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    $token = $this->actingAs($operador)->post(route('registros.captura'), ['fotografia' => $foto], ['Accept' => 'application/json'])->assertOk()->json('captura');
    $version = $this->get(route('registros.create'))->assertOk()->viewData('versionPreguntas');
    $datosInicio = ['id_maquina' => $maquina->getKey(), 'captura_epp' => $token, 'firma_inicial' => firmaAtsPrueba(), 'respuestas' => [$inicial->getKey() => 'Sí'], 'version_preguntas' => $version];
    $this->post(route('registros.store'), $datosInicio)->assertSessionHasNoErrors()->assertRedirect();
    $registro = RegistroAtsEpp::query()->sole();
    $this->get(route('registros.show', $registro))->assertOk()->assertSee(route('registros.finalizar', $registro));
    $versionFinal = $this->get(route('registros.finalizar', $registro))->assertOk()->viewData('versionPreguntas');
    $this->post(route('registros.finalizar.guardar', $registro), ['firma_final' => firmaAtsPrueba(), 'respuestas' => [$final->getKey() => 'No'], 'version_preguntas' => $versionFinal])->assertSessionHasNoErrors()->assertRedirect(route('registros.show', $registro));
    expect($registro->fresh()->estado)->toBe('Finalizado');
    $this->assertDatabaseCount('registros_ats_epp', 1);
    $this->assertDatabaseCount('respuestas_ats', 2);
    $this->post(route('registros.store'), $datosInicio)->assertSessionHasErrors('captura_epp');
    $this->assertDatabaseCount('registros_ats_epp', 1);
});

it('impide el cierre si no hay preguntas finales y no muestra una firma final inexistente', function (): void {
    Storage::fake('local');
    $operador = operadorCierreAts();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey()]);
    $datos = datosCierreAts($operador, $registro, [123 => 'Sí']);
    $this->actingAs($operador)->get(route('registros.finalizar', $registro))->assertOk()->assertSee('El administrador debe registrar las preguntas finales');
    $this->get(route('registros.firma-final', $registro))->assertNotFound();
    $this->post(route('registros.finalizar.guardar', $registro), $datos)->assertSessionHasErrors('respuestas');
    expect($registro->fresh()->estado)->toBe('En curso');
    $this->assertDatabaseCount('respuestas_ats', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});
