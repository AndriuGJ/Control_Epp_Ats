<?php

use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
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

function operadorInicialAts(): User
{
    (new RolesPermisosSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->assignRole('operador');

    return $usuario;
}

function datosInicialesAts(User $operador, Maquina $maquina, array $respuestas): array
{
    return [
        'id_maquina' => $maquina->getKey(),
        'fotografia_epp' => UploadedFile::fake()->createWithContent('epp.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')),
        'firma_inicial' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAZAAAAB4CAYAAADc36SXAAAFKklEQVR4nO3dS3bbOBBAUTunF+Ch95D9LyV76GHvQD3ozrGPQopkEZ8CcO80P8Wi+FiAKL0/Ho83ALjqR+8HAMCYBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAAAgREABCBASAEAEBIERAEvn4/Pno/RgAznp/PJyzMniOxz9//3rv9VgAzjCBJGDyAEYkIJ1txcP0AYxAQDoSD2BkAtKJZSuy+fj8+XBccoVN9A72XqSmD3pwPBL1V+8HAPTzauL4/mtiwhYTSGOu9sjg6Dh0nHKGgDT06mrPC5NWrsbBmz3YIyCNiAe93Z0qTCU8E5AGjt7Z4gVITaUvXoSE3wSkMvGgp9one8tbaxOQisSDXlpPCaaSNQlIJXtXZt4aSU2999qEZC0CUoF40EO2k7flrfm5kbAwLxpayxaO53//++NzETUXE0hBr+LhhUMNWeOxZaTHyjkmkEJMHrQ04sn4+2PbmkoyP3a2mUAKOIqH6YNSem+SlzZiCPliAqnAwU8NM55s9z57y1QyBhPITUffZW764K4Zw/GK5eBxmEBu8OU71LZaPN7evHtrJCaQoDNXSQ56olYMxx4/i7wEJEA8qGW2TfLSLG/lYgnrIgcwtbjSPmZ5KxcTyAVn4+GA5grhiPOz68sEcpLJg9IsV93n5sS+TCAnXImH6YMzXDnX42fbjgnkgMmDkpzc6nNzYjsmkBeuxsP00Vf2E4R49ONCsA4TyA4H3BhGuJlTOPrz7q06TCAbIvFwMNZ3JRYZngOb5HmJehkmkBMcVH1Ep4sMz5cTVG7evVWGCeTJ0YcjHv0ZB17M2Vgc3Xez93taEY5xee6uE5BvLF21cycYr/4e8eAuz+N5lrD+Z9O8rjPBuPrzzhIPJ5y57C1v8ScBeYvHw/SxrdR0kZ1N8vnt3VPCf5YPiMnjvh7B6D19mDrW4nndtnRA7sRj1ekjw3TR82pQOODLsgExeZyTIRgZ/m3LVfCnJQNyNx4zTx81NrtL6rF0ZeqAbcsFxOTxZYTpoifhgNeWCkiJeIw8fYwejJbTh3jAsWUCstrkMXosnrWKh3DAeUsEpFQ8Mk8fswWjNZvkcN0SAXk2Qzyyb3aXVHv6MHVAzPQB6X3DWQkrTxc17/kQDrhn6oCUOvm0nj5WDsaRUv9n8YD7pg3IKJvmYrGvxvQoHFDOlAEpGY/S04dg9GGTHMqbLiDZJo+VNrtLKjl9mDqgjqkCUjoeV6cP00UZpeIhHFDXNAHpMXkIRk6Wq6CNKQJSIx7P04dYtHF3+jB1QDvDB6TF5OGKto07b7sWDmhv6IDUioeTUQ6RL/eK/Hkg5v3xGPOrfmtPHh+fPx9OQO1Elq6EA/oacgJpsWzlJJSXJUXIYciAPHPSGNuV6cPUAXkMF5AZPhyRL2efT+GAfH70fgBX1PxkVvISD8hpmAkk20eUcN/R9CEckNsQARGP+byaJm2SwxjSB0Q81vD7OTV1wDhSB0Q85rS1dCUcMJ7Um+jPJw8nkzmJB4xpiDvR3RU+D9+PAvMYIiDM4SgewgFjSb2ExTrEA8aTehOdedjngPmYQGhiKxTiAWOzB0JT3hAB8xAQAEIsYQEQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhAgIACECAkCIgAAQIiAAhPwLnagYvV7kWxsAAAAASUVORK5CYII=',
        'respuestas' => $respuestas,
        'version_preguntas' => app(RegistroAtsService::class)->prepararInicio($operador)['versionPreguntas'],
    ];
}

it('inicia el registro con la identidad y fecha del servidor y conserva sus archivos privados', function (): void {
    Storage::fake('local');
    $this->freezeTime();
    $operador = operadorInicialAts();
    $otro = User::factory()->create();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $pregunta = PreguntaAts::factory()->create(['pregunta' => 'Pregunta de prueba del formato']);
    $datos = datosInicialesAts($operador, $maquina, [$pregunta->getKey() => '<script>alert(1)</script>']);
    $datos += ['id_operador' => $otro->getKey(), 'estado' => 'Finalizado', 'id_revisor' => $otro->getKey(), 'fecha_inicio' => '2000-01-01'];

    $this->actingAs($operador)->post(route('registros.store'), $datos)->assertSessionHasNoErrors()->assertRedirect();

    $registro = RegistroAtsEpp::query()->sole();
    $this->assertDatabaseHas('registros_ats_epp', ['id_registro' => $registro->getKey(), 'id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey(), 'estado' => 'En curso', 'fecha_inicio' => now()->toDateTimeString(), 'id_revisor' => null, 'fecha_finalizacion' => null]);
    $this->assertDatabaseHas('respuestas_ats', ['id_registro' => $registro->getKey(), 'id_pregunta' => $pregunta->getKey(), 'respuesta' => '<script>alert(1)</script>']);
    Storage::disk('local')->assertExists([$registro->fotografia_epp, $registro->firma_inicial]);
    expect(Storage::disk('local')->allFiles('registros'))->toHaveCount(2);
    $this->get(route('registros.show', $registro))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    $this->get(route('registros.fotografia', $registro))->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get(route('registros.firma', $registro))->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('muestra únicamente máquinas autorizadas y preguntas iniciales activas en el formulario', function (): void {
    $operador = operadorInicialAts();
    $permitida = Maquina::factory()->create(['nombre' => 'Equipo permitido']);
    $inactiva = Maquina::factory()->create(['nombre' => 'Equipo inactivo', 'estado' => false]);
    $operador->maquinas()->attach([$permitida->getKey(), $inactiva->getKey()]);
    Maquina::factory()->create(['nombre' => 'Equipo de otro operador']);
    PreguntaAts::factory()->create(['pregunta' => 'Segunda pregunta inicial', 'posicion' => 2]);
    PreguntaAts::factory()->create(['pregunta' => 'Primera pregunta inicial', 'posicion' => 1]);
    PreguntaAts::factory()->create(['pregunta' => 'Pregunta desactivada', 'estado' => false]);
    PreguntaAts::factory()->create(['pregunta' => 'Pregunta de cierre', 'etapa' => 'Final']);

    $this->actingAs($operador)->get(route('registros.create'))->assertOk()->assertSee('Equipo permitido')->assertDontSee('Equipo inactivo')->assertDontSee('Equipo de otro operador')->assertSeeInOrder(['Primera pregunta inicial', 'Segunda pregunta inicial'])->assertDontSee('Pregunta desactivada')->assertDontSee('Pregunta de cierre');
});

it('impide iniciar con una máquina inactiva o sin autorización', function (bool $autorizada, bool $activa): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $maquina = Maquina::factory()->create(['estado' => $activa]);
    if ($autorizada) {
        $operador->maquinas()->attach($maquina);
    }
    $pregunta = PreguntaAts::factory()->create();
    $datos = datosInicialesAts($operador, $maquina, [$pregunta->getKey() => 'Respuesta de prueba']);

    $this->actingAs($operador)->post(route('registros.store'), $datos)->assertSessionHasErrors(['id_maquina' => 'Selecciona una máquina activa para la que tengas autorización.']);
    $this->assertDatabaseCount('registros_ats_epp', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['sin autorización' => [false, true], 'autorizada pero inactiva' => [true, false]]);

it('rechaza respuestas incompletas o preguntas agregadas de otra etapa', function (bool $agregarFinal): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $primera = PreguntaAts::factory()->create();
    $segunda = PreguntaAts::factory()->create();
    $final = PreguntaAts::factory()->create(['etapa' => 'Final']);
    $respuestas = [$primera->getKey() => 'Sí'];
    if ($agregarFinal) {
        $respuestas += [$segunda->getKey() => 'Sí', $final->getKey() => 'No'];
    }
    $this->actingAs($operador)->post(route('registros.store'), datosInicialesAts($operador, $maquina, $respuestas))->assertSessionHasErrors('respuestas');
    $this->assertDatabaseCount('registros_ats_epp', 0);
    $this->assertDatabaseCount('respuestas_ats', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['falta una respuesta' => false, 'incluye pregunta final' => true]);

it('exige fotografía firma y respuestas antes de iniciar', function (): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $this->actingAs($operador)->post(route('registros.store'), [])->assertSessionHasErrors(['id_maquina', 'fotografia_epp', 'firma_inicial', 'respuestas']);
    $this->assertDatabaseCount('registros_ats_epp', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('rechaza firmas inválidas y fotografías que no son imágenes', function (string $campo): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $pregunta = PreguntaAts::factory()->create();
    $datos = datosInicialesAts($operador, $maquina, [$pregunta->getKey() => 'Sí']);
    $datos[$campo] = $campo === 'firma_inicial' ? 'data:image/png;base64,aG9sYQ==' : UploadedFile::fake()->create('archivo.txt', 1, 'text/plain');
    $this->actingAs($operador)->post(route('registros.store'), $datos)->assertSessionHasErrors($campo)->assertSessionMissing('_old_input.firma_inicial');
    $this->assertDatabaseCount('registros_ats_epp', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with(['firma_inicial', 'fotografia_epp']);

it('evita dos registros en curso del mismo operador incluso con otra máquina', function (): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $maquina = Maquina::factory()->create();
    $otraMaquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey(), 'id_maquina' => $otraMaquina->getKey()]);
    $pregunta = PreguntaAts::factory()->create();
    $this->actingAs($operador)->get(route('registros.create'))->assertRedirect(route('registros.show', $registro));
    $this->post(route('registros.store'), datosInicialesAts($operador, $maquina, [$pregunta->getKey() => 'Sí']))->assertSessionHasErrors('registro');
    $this->assertDatabaseCount('registros_ats_epp', 1);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('rechaza un formulario desactualizado si cambian las preguntas', function (): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $pregunta = PreguntaAts::factory()->create();
    $datos = datosInicialesAts($operador, $maquina, [$pregunta->getKey() => 'Sí']);
    $pregunta->update(['pregunta' => 'Una pregunta distinta']);
    $this->actingAs($operador)->post(route('registros.store'), $datos)->assertSessionHasErrors(['respuestas' => 'Las preguntas cambiaron. Recarga el formulario y responde la versión actual.']);
    $this->assertDatabaseCount('registros_ats_epp', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('evita iniciar sin preguntas iniciales configuradas', function (): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $this->actingAs($operador)->get(route('registros.create'))->assertOk()->assertSee('El administrador debe registrar las preguntas iniciales');
    $this->post(route('registros.store'), datosInicialesAts($operador, $maquina, [123 => 'Sí']))->assertSessionHasErrors('respuestas');
    $this->assertDatabaseCount('registros_ats_epp', 0);
});

it('oculta registros y archivos de otros operadores y permite la consulta al supervisor', function (): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $otro = User::factory()->create();
    $registro = RegistroAtsEpp::factory()->create(['id_operador' => $otro->getKey()]);
    Storage::disk('local')->put($registro->fotografia_epp, 'archivo de prueba');
    Storage::disk('local')->put($registro->firma_inicial, 'firma de prueba');
    $this->get(route('registros.index'))->assertRedirect(route('login'));
    $this->actingAs($operador)->get(route('registros.show', $registro))->assertNotFound();
    $this->get(route('registros.fotografia', $registro))->assertNotFound();
    $this->get(route('registros.firma', $registro))->assertNotFound();
    $this->get(route('registros.index'))->assertOk()->assertDontSee(route('registros.show', $registro));
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $this->actingAs($supervisor)->get(route('registros.show', $registro))->assertOk();
    $this->get(route('registros.fotografia', $registro))->assertOk();
    $this->get(route('registros.firma', $registro))->assertOk();
    $this->get(route('registros.create'))->assertForbidden();
    $this->post(route('registros.store'), [])->assertForbidden();
});

it('revierte los archivos y datos si falla la creación del registro', function (): void {
    Storage::fake('local');
    $operador = operadorInicialAts();
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $pregunta = PreguntaAts::factory()->create();
    RegistroAtsEpp::creating(function (): void {
        throw new RuntimeException('Fallo de persistencia simulado');
    });

    expect(fn () => app(RegistroAtsService::class)->iniciar($operador, datosInicialesAts($operador, $maquina, [$pregunta->getKey() => 'Sí'])))->toThrow(RuntimeException::class);
    $this->assertDatabaseCount('registros_ats_epp', 0);
    $this->assertDatabaseCount('respuestas_ats', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});
