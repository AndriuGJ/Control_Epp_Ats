<?php

use App\Models\PreguntaAts;
use App\Models\RespuestaAts;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('permite al administrador registrar y editar preguntas de ambas etapas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin)->get(route('preguntas.create'))->assertOk();
    $this->post(route('preguntas.store'), ['pregunta' => 'Pregunta del formato de prueba', 'etapa' => 'Inicial', 'posicion' => 2, 'estado' => 1])->assertRedirect(route('preguntas.index'));
    $pregunta = PreguntaAts::query()->sole();
    $this->get(route('preguntas.edit', $pregunta))->assertOk();
    $this->put(route('preguntas.update', $pregunta), ['pregunta' => 'Pregunta final de prueba', 'etapa' => 'Final', 'posicion' => 3, 'estado' => 0])->assertRedirect(route('preguntas.index'));
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $pregunta->getKey(), 'pregunta' => 'Pregunta final de prueba', 'etapa' => 'Final', 'posicion' => 1, 'estado' => false]);
    $this->get(route('preguntas.index'))->assertOk()->assertSee('Pregunta final de prueba');
});

it('rechaza el acceso a preguntas para roles sin permiso', function (string $rol): void {
    $this->seed(RolesPermisosSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    $this->get(route('preguntas.index'))->assertRedirect(route('login'));
    $this->actingAs($usuario)->get(route('preguntas.index'))->assertForbidden();
    $this->post(route('preguntas.store'), ['pregunta' => 'No permitida', 'etapa' => 'Inicial', 'posicion' => 1, 'estado' => 1])->assertForbidden();
    $this->assertDatabaseCount('preguntas_ats', 0);
})->with(['operador', 'supervisor']);

it('valida la etapa antes de guardar una pregunta', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin)->post(route('preguntas.store'), ['pregunta' => 'Prueba', 'etapa' => 'Otra', 'posicion' => 0, 'estado' => 1])->assertSessionHasErrors(['etapa']);
    $this->assertDatabaseCount('preguntas_ats', 0);
});

it('conserva el texto y la etapa de las preguntas que ya tienen respuestas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $pregunta = PreguntaAts::factory()->create(['pregunta' => 'Texto histórico', 'etapa' => 'Inicial']);
    RespuestaAts::factory()->create(['id_pregunta' => $pregunta->getKey()]);
    $this->actingAs($admin)->put(route('preguntas.update', $pregunta), ['pregunta' => 'Texto cambiado', 'etapa' => 'Final', 'posicion' => 1, 'estado' => 1])->assertSessionHasErrors('pregunta');
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $pregunta->getKey(), 'pregunta' => 'Texto histórico', 'etapa' => 'Inicial']);
    $this->put(route('preguntas.update', $pregunta), ['pregunta' => 'Texto histórico', 'etapa' => 'Inicial', 'posicion' => 5, 'estado' => 0])->assertRedirect(route('preguntas.index'));
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $pregunta->getKey(), 'posicion' => $pregunta->posicion, 'estado' => false]);
});

it('escapa el texto de las preguntas en las vistas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    PreguntaAts::factory()->create(['pregunta' => '<script>alert(1)</script>']);
    $this->actingAs($admin)->get(route('preguntas.index'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('oculta la posición en los formularios y agrega las preguntas al final de su etapa', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 4, 'estado' => false]);
    $final = PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 8]);

    $this->actingAs($admin)->get(route('preguntas.create'))->assertOk()->assertDontSee('name="posicion"', false);
    $this->get(route('preguntas.edit', $final))->assertOk()->assertDontSee('name="posicion"', false);
    $this->post(route('preguntas.store'), ['pregunta' => 'Nueva inicial', 'etapa' => 'Inicial', 'estado' => 1])->assertRedirect(route('preguntas.index'));
    $this->post(route('preguntas.store'), ['pregunta' => 'Nueva final', 'etapa' => 'Final', 'estado' => 1])->assertRedirect(route('preguntas.index'));

    $this->assertDatabaseHas('preguntas_ats', ['pregunta' => 'Nueva inicial', 'etapa' => 'Inicial', 'posicion' => 5]);
    $this->assertDatabaseHas('preguntas_ats', ['pregunta' => 'Nueva final', 'etapa' => 'Final', 'posicion' => 9]);
});

it('ignora posiciones enviadas desde el formulario de registro', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $primera = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);

    $this->actingAs($admin)->post(route('preguntas.store'), [
        'pregunta' => 'Nueva pregunta', 'etapa' => 'Inicial', 'posicion' => 1, 'estado' => 1,
    ])->assertRedirect(route('preguntas.index'));

    expect($primera->fresh()->posicion)->toBe(1);
    $this->assertDatabaseHas('preguntas_ats', ['pregunta' => 'Nueva pregunta', 'posicion' => 2]);
});

it('conserva el orden al editar sin alterar respuestas ni preguntas de otra etapa', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $primera = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);
    $segunda = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 2, 'estado' => false]);
    $final = PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 2]);
    $respuesta = RespuestaAts::factory()->create(['id_pregunta' => $primera->getKey()]);

    $this->actingAs($admin)->put(route('preguntas.update', $primera), [
        'pregunta' => $primera->pregunta, 'etapa' => 'Inicial', 'posicion' => 2, 'estado' => 1,
    ])->assertRedirect(route('preguntas.index'));

    expect($primera->fresh()->posicion)->toBe(1);
    expect($segunda->fresh()->posicion)->toBe(2);
    expect($segunda->fresh()->estado)->toBeFalse();
    expect($final->fresh()->posicion)->toBe(2);
    expect($respuesta->fresh()->id_pregunta)->toBe($primera->getKey());
});

it('mueve una pregunta al final cuando cambia de etapa sin intercambiar otras preguntas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $pregunta = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);
    $ocupante = PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 2]);

    $this->actingAs($admin)->put(route('preguntas.update', $pregunta), [
        'pregunta' => $pregunta->pregunta, 'etapa' => 'Final', 'estado' => 1,
    ])->assertRedirect(route('preguntas.index'));

    expect($pregunta->fresh()->etapa)->toBe('Final');
    expect($pregunta->fresh()->posicion)->toBe(3);
    expect($ocupante->fresh()->posicion)->toBe(2);
});
