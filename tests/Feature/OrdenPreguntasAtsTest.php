<?php

use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
use App\Models\RespuestaAts;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('muestra todas las preguntas de la etapa en su orden incluyendo las inactivas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    PreguntaAts::factory()->create(['pregunta' => 'Segunda pregunta', 'etapa' => 'Inicial', 'posicion' => 5, 'estado' => false]);
    PreguntaAts::factory()->create(['pregunta' => 'Primera pregunta', 'etapa' => 'Inicial', 'posicion' => 2]);
    PreguntaAts::factory()->create(['pregunta' => 'Pregunta final', 'etapa' => 'Final']);

    $this->actingAs($admin)->get(route('preguntas.index'))->assertOk()->assertSee('Orden de preguntas');
    $this->get(route('preguntas.orden'))->assertOk()
        ->assertSeeInOrder(['Primera pregunta', 'Segunda pregunta'])->assertSee('Inactiva')->assertDontSee('Pregunta final');
    $this->get(route('preguntas.orden', ['etapa' => 'Final']))->assertOk()
        ->assertSee('Pregunta final')->assertDontSee('Primera pregunta');
});

it('muestra una lista vacía sin permitir guardar cuando la etapa no tiene preguntas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');

    $this->actingAs($admin)->get(route('preguntas.orden', ['etapa' => 'Final']))->assertOk()
        ->assertSee('No hay preguntas en esta etapa.')->assertDontSee('data-guardar-orden', false);
});

it('guarda el orden completo sin cambiar textos estados respuestas ni la otra etapa', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $primera = PreguntaAts::factory()->create(['pregunta' => 'Primera histórica', 'etapa' => 'Inicial', 'posicion' => 2]);
    $segunda = PreguntaAts::factory()->create(['pregunta' => 'Segunda inactiva', 'etapa' => 'Inicial', 'posicion' => 5, 'estado' => false]);
    $tercera = PreguntaAts::factory()->create(['pregunta' => 'Tercera activa', 'etapa' => 'Inicial', 'posicion' => 8]);
    $final = PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 1]);
    $respuesta = RespuestaAts::factory()->create(['id_pregunta' => $primera->getKey(), 'respuesta' => 'Sí']);
    $this->actingAs($admin);
    $version = $this->get(route('preguntas.orden'))->viewData('versionOrden');

    $this->put(route('preguntas.orden.guardar'), [
        'etapa' => 'Inicial', 'orden' => [$tercera->getKey(), $primera->getKey(), $segunda->getKey()], 'version_orden' => $version,
    ])->assertRedirect(route('preguntas.orden', ['etapa' => 'Inicial']))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $tercera->getKey(), 'posicion' => 1, 'pregunta' => 'Tercera activa', 'estado' => true]);
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $primera->getKey(), 'posicion' => 2, 'pregunta' => 'Primera histórica', 'estado' => true]);
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $segunda->getKey(), 'posicion' => 3, 'pregunta' => 'Segunda inactiva', 'estado' => false]);
    expect($final->fresh()->posicion)->toBe(1);
    expect($respuesta->fresh()->id_pregunta)->toBe($primera->getKey());
    expect($respuesta->fresh()->respuesta)->toBe('Sí');
    $this->get(route('preguntas.orden'))->assertOk()->assertSeeInOrder(['Tercera activa', 'Primera histórica', 'Segunda inactiva']);
});

it('rechaza listas incompletas duplicadas ajenas o inexistentes sin modificar el orden', function (string $caso, string $campo): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $primera = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);
    $segunda = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 2]);
    $final = PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 1]);
    $this->actingAs($admin);
    $version = $this->get(route('preguntas.orden'))->viewData('versionOrden');
    $orden = match ($caso) {
        'incompleta' => [$segunda->getKey()],
        'duplicada' => [$primera->getKey(), $primera->getKey()],
        'otra etapa' => [$final->getKey(), $segunda->getKey()],
        'inexistente' => [999999, $segunda->getKey()],
        'vacía' => [],
        'no lista' => [2 => $primera->getKey(), 5 => $segunda->getKey()],
    };

    $this->from(route('preguntas.orden'))->put(route('preguntas.orden.guardar'), [
        'etapa' => 'Inicial', 'orden' => $orden, 'version_orden' => $version,
    ])->assertSessionHasErrors($campo);

    expect($primera->fresh()->posicion)->toBe(1);
    expect($segunda->fresh()->posicion)->toBe(2);
    expect($final->fresh()->posicion)->toBe(1);
})->with([
    ['incompleta', 'orden'], ['duplicada', 'orden.0'], ['otra etapa', 'orden'],
    ['inexistente', 'orden'], ['vacía', 'orden'], ['no lista', 'orden'],
]);

it('rechaza guardar una lista desactualizada si las preguntas o su orden cambiaron', function (string $cambio): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $primera = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);
    $segunda = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 2]);
    $this->actingAs($admin);
    $version = $this->get(route('preguntas.orden'))->viewData('versionOrden');
    if ($cambio === 'nueva pregunta') {
        PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 3]);
    } else {
        $actual = $this->put(route('preguntas.orden.guardar'), [
            'etapa' => 'Inicial', 'orden' => [$segunda->getKey(), $primera->getKey()], 'version_orden' => $version,
        ]);
        $actual->assertSessionHasNoErrors();
    }
    $antes = PreguntaAts::query()->orderBy('id_pregunta')->pluck('posicion', 'id_pregunta')->all();

    $this->put(route('preguntas.orden.guardar'), [
        'etapa' => 'Inicial', 'orden' => [$segunda->getKey(), $primera->getKey()], 'version_orden' => $version,
    ])->assertSessionHasErrors(['orden' => 'La lista de preguntas cambió o no está completa. Recarga la lista y vuelve a ordenar.']);

    expect(PreguntaAts::query()->orderBy('id_pregunta')->pluck('posicion', 'id_pregunta')->all())->toBe($antes);
})->with(['nueva pregunta', 'otro orden guardado']);

it('protege la consulta y el guardado del orden frente a usuarios sin permiso', function (string $rol): void {
    $this->seed(RolesPermisosSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    $this->get(route('preguntas.orden'))->assertRedirect(route('login'));
    $this->put(route('preguntas.orden.guardar'), [])->assertRedirect(route('login'));

    $this->actingAs($usuario)->get(route('preguntas.orden'))->assertForbidden();
    $this->put(route('preguntas.orden.guardar'), [])->assertForbidden();
})->with(['operador', 'supervisor']);

it('valida la etapa tanto al consultar como al guardar el orden', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');

    $this->actingAs($admin)->from(route('preguntas.index'))->get(route('preguntas.orden', ['etapa' => 'Otra']))->assertSessionHasErrors('etapa');
    $this->put(route('preguntas.orden.guardar'), [])->assertSessionHasErrors(['etapa', 'orden', 'version_orden']);
});

it('escapa el texto de las preguntas en la lista de orden', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    PreguntaAts::factory()->create(['pregunta' => '<script>alert(1)</script>']);

    $this->actingAs($admin)->get(route('preguntas.orden'))->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('aplica el orden guardado al formulario ATS de cada etapa y omite las inactivas', function (string $etapa): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $operador = User::factory()->create();
    $operador->assignRole('operador');
    $operador->maquinas()->attach(Maquina::factory()->create());
    $primera = PreguntaAts::factory()->create(['pregunta' => 'Control A', 'etapa' => $etapa, 'posicion' => 1]);
    $segunda = PreguntaAts::factory()->create(['pregunta' => 'Control B', 'etapa' => $etapa, 'posicion' => 2]);
    $inactiva = PreguntaAts::factory()->create(['pregunta' => 'Control inactivo', 'etapa' => $etapa, 'posicion' => 3, 'estado' => false]);
    $this->actingAs($admin);
    $version = $this->get(route('preguntas.orden', ['etapa' => $etapa]))->viewData('versionOrden');
    $this->put(route('preguntas.orden.guardar'), [
        'etapa' => $etapa, 'orden' => [$segunda->getKey(), $inactiva->getKey(), $primera->getKey()], 'version_orden' => $version,
    ])->assertSessionHasNoErrors();
    $ruta = route('registros.create');
    if ($etapa === 'Final') {
        $registro = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey()]);
        $ruta = route('registros.finalizar', $registro);
    }

    $this->actingAs($operador)->get($ruta)->assertOk()
        ->assertSeeInOrder(['Control B', 'Control A'])->assertDontSee('Control inactivo');
})->with(['Inicial', 'Final']);
