<?php

use App\Models\RegistroAtsEpp;
use App\Models\RespuestaAts;
use App\Models\User;
use App\Services\RegistroAtsService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function usuarioRevisionAts(string $rol = 'supervisor'): User
{
    (new RolesPermisosSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    return $usuario;
}

it('guarda el supervisor su firma y la fecha sin modificar los datos de la labor', function (): void {
    Storage::fake('local');
    $this->freezeTime();
    $supervisor = usuarioRevisionAts();
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    $respuesta = RespuestaAts::factory()->create(['id_registro' => $registro->getKey(), 'respuesta' => 'No']);
    $datosLabor = $registro->only(['id_operador', 'id_maquina', 'fecha_inicio', 'fecha_finalizacion', 'firma_inicial', 'firma_final', 'fecha_fotografia', 'fotografia_epp', 'estado']);
    Storage::disk('local')->put($registro->firma_final, 'firma final original');
    $this->actingAs($supervisor)->get(route('registros.show', $registro))->assertOk()->assertSee('Pendiente de revisión')->assertSee('Guardar revisión')->assertSee($respuesta->pregunta->pregunta);
    $this->post(route('registros.revisar', $registro), [
        'firma_revisor' => firmaAtsPrueba(), 'id_revisor' => 999999,
        'fecha_revision' => '2000-01-01', 'estado' => 'En curso',
    ])->assertSessionHasNoErrors()->assertRedirect(route('registros.show', $registro));
    $registro->refresh();
    $this->assertDatabaseHas('registros_ats_epp', ['id_registro' => $registro->getKey(), 'id_revisor' => $supervisor->getKey(), 'fecha_revision' => now()->toDateTimeString(), 'estado' => 'Finalizado']);
    expect($registro->only(array_keys($datosLabor)))->toEqual($datosLabor);
    expect($respuesta->fresh()->respuesta)->toBe('No');
    Storage::disk('local')->assertExists($registro->firma_revisor);
    expect(Storage::disk('local')->get($registro->firma_revisor))->toBe(base64_decode(substr(firmaAtsPrueba(), strlen('data:image/png;base64,'))));
    expect(Storage::disk('local')->get($registro->firma_final))->toBe('firma final original');
    $this->get(route('registros.show', $registro))->assertOk()->assertSee('Revisado')->assertSee($supervisor->nombres)->assertSee(route('registros.firma-revisor', $registro))->assertDontSee('Guardar revisión');
    $this->get(route('registros.index'))->assertOk()->assertSee('Revisado')->assertDontSee('Pendiente de revisión');
    $this->get(route('panel'))->assertOk()->assertViewHas('pendientesRevision', 0)->assertViewHas('finalizados', 1);
});

it('permite revisar al administrador que tiene el permiso correspondiente', function (): void {
    Storage::fake('local');
    $administrador = usuarioRevisionAts('administrador');
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    $this->actingAs($administrador)->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertSessionHasNoErrors()->assertRedirect(route('registros.show', $registro));
    expect($registro->fresh()->id_revisor)->toBe($administrador->getKey());
    Storage::disk('local')->assertExists($registro->fresh()->firma_revisor);
});

it('exige iniciar sesión para revisar o consultar la firma del supervisor', function (): void {
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    $this->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertRedirect(route('login'));
    $this->get(route('registros.firma-revisor', $registro))->assertRedirect(route('login'));
    expect($registro->fresh()->fecha_revision)->toBeNull();
});

it('impide que un operador registre una revisión aunque conozca la dirección', function (): void {
    Storage::fake('local');
    $operador = usuarioRevisionAts('operador');
    $registro = RegistroAtsEpp::factory()->finalizado()->create(['id_operador' => $operador->getKey()]);
    $this->actingAs($operador)->get(route('registros.show', $registro))->assertOk()->assertDontSee('Guardar revisión');
    $this->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertForbidden();
    expect($registro->fresh()->fecha_revision)->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('rechaza la revisión de una labor todavía en curso o con cierre incompleto', function (array $cambios): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $registro = RegistroAtsEpp::factory()->finalizado()->create($cambios);
    $this->actingAs($supervisor)->get(route('registros.show', $registro))->assertOk()->assertDontSee('Guardar revisión');
    $this->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertSessionHasErrors(['registro' => 'Solo se pueden revisar registros con la labor finalizada.']);
    expect($registro->fresh()->id_revisor)->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    'en curso' => [['estado' => 'En curso']],
    'sin firma final' => [['firma_final' => null]],
    'sin fecha de finalización' => [['fecha_finalizacion' => null]],
]);

it('exige que otro supervisor revise el registro del propio revisor', function (): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $registro = RegistroAtsEpp::factory()->finalizado()->create(['id_operador' => $supervisor->getKey()]);
    $this->actingAs($supervisor)->get(route('registros.show', $registro))->assertOk()->assertSee('Otro supervisor debe revisar tu registro.')->assertDontSee('Guardar revisión');
    $this->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertSessionHasErrors('registro');
    expect($registro->fresh()->fecha_revision)->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('impide sobrescribir una revisión incluso desde una segunda cuenta de supervisor', function (): void {
    Storage::fake('local');
    $this->freezeTime();
    $supervisor = usuarioRevisionAts();
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    $this->actingAs($supervisor)->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertSessionHasNoErrors();
    $revisionOriginal = $registro->fresh()->only(['id_revisor', 'fecha_revision', 'firma_revisor']);
    $archivosOriginales = Storage::disk('local')->allFiles();
    $otroSupervisor = usuarioRevisionAts();
    $this->travel(10)->minutes();
    $this->actingAs($otroSupervisor)->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertSessionHasErrors(['registro' => 'Este registro ya fue revisado y su revisión no se puede modificar.']);
    expect($registro->fresh()->only(array_keys($revisionOriginal)))->toEqual($revisionOriginal);
    expect(Storage::disk('local')->allFiles())->toBe($archivosOriginales);
});

it('valida la firma del revisor y no la conserva en la sesión cuando hay errores', function (mixed $firma, string $mensaje): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    $this->actingAs($supervisor)->post(route('registros.revisar', $registro), ['firma_revisor' => $firma])->assertSessionHasErrors(['firma_revisor' => $mensaje])->assertSessionMissing('_old_input.firma_revisor');
    expect($registro->fresh()->id_revisor)->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    'sin firma' => [null, 'Dibuja tu firma antes de guardar la revisión.'],
    'no es texto' => [['archivo'], 'Dibuja una firma válida en el recuadro.'],
    'demasiado grande' => [str_repeat('a', 300001), 'La firma es demasiado grande. Limpia el recuadro y vuelve a firmar.'],
    'imagen inválida' => ['data:image/png;base64,aG9sYQ==', 'Dibuja una firma válida antes de guardar el registro.'],
]);

it('protege la firma privada y permite consultarla al propietario y al supervisor', function (): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $operador = usuarioRevisionAts('operador');
    $otroOperador = usuarioRevisionAts('operador');
    $registro = RegistroAtsEpp::factory()->finalizado()->create(['id_operador' => $operador->getKey()]);
    $this->actingAs($supervisor)->get(route('registros.firma-revisor', $registro))->assertNotFound();
    $this->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertSessionHasNoErrors();
    $this->get(route('registros.firma-revisor', $registro))->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($operador)->get(route('registros.firma-revisor', $registro))->assertOk();
    $this->get(route('registros.show', $registro))->assertOk()->assertSee('Revisión del supervisor')->assertDontSee('Guardar revisión');
    $this->actingAs($otroOperador)->get(route('registros.firma-revisor', $registro))->assertNotFound();
    Storage::disk('local')->delete($registro->fresh()->firma_revisor);
    $this->actingAs($supervisor)->get(route('registros.firma-revisor', $registro))->assertNotFound();
});

it('devuelve no encontrado al intentar revisar un registro inexistente', function (): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $this->actingAs($supervisor)->post(route('registros.revisar', 999999), ['firma_revisor' => firmaAtsPrueba()])->assertNotFound();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('revierte la revisión y elimina su firma nueva si falla el guardado de datos', function (): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    Storage::disk('local')->put($registro->firma_final, 'firma final original');
    RegistroAtsEpp::updating(function (): void {
        throw new RuntimeException('Fallo de revisión simulado');
    });
    expect(fn () => app(RegistroAtsService::class)->revisar($supervisor, $registro->getKey(), ['firma_revisor' => firmaAtsPrueba()]))->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('registros_ats_epp', ['id_registro' => $registro->getKey(), 'id_revisor' => null, 'fecha_revision' => null, 'firma_revisor' => null, 'estado' => 'Finalizado']);
    expect(Storage::disk('local')->allFiles())->toBe([$registro->firma_final]);
    expect(Storage::disk('local')->get($registro->firma_final))->toBe('firma final original');
});

it('no reemplaza los datos existentes de una revisión incompleta', function (array $revision): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $registro = RegistroAtsEpp::factory()->finalizado()->create($revision);
    $anterior = $registro->only(['id_revisor', 'fecha_revision', 'firma_revisor']);
    $this->actingAs($supervisor)->get(route('registros.show', $registro))->assertOk()->assertDontSee('Guardar revisión');
    $this->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertSessionHasErrors('registro');
    expect($registro->fresh()->only(array_keys($anterior)))->toEqual($anterior);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    'solo fecha' => [['fecha_revision' => '2026-10-01 10:00:00']],
    'solo firma' => [['firma_revisor' => 'firmas/revision-existente.png']],
]);

it('rechaza una revisión invocada desde una cuenta inactiva sin guardar archivos', function (): void {
    Storage::fake('local');
    $supervisor = usuarioRevisionAts();
    $supervisor->update(['estado' => false]);
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    $this->actingAs($supervisor)->post(route('registros.revisar', $registro), ['firma_revisor' => firmaAtsPrueba()])->assertRedirect(route('login'));
    expect($registro->fresh()->fecha_revision)->toBeNull();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});
