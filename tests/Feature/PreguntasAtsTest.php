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
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $pregunta->getKey(), 'pregunta' => 'Pregunta final de prueba', 'etapa' => 'Final', 'posicion' => 3, 'estado' => false]);
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

it('valida la etapa y la posición antes de guardar una pregunta', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin)->post(route('preguntas.store'), ['pregunta' => 'Prueba', 'etapa' => 'Otra', 'posicion' => 0, 'estado' => 1])->assertSessionHasErrors(['etapa', 'posicion']);
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
    $this->assertDatabaseHas('preguntas_ats', ['id_pregunta' => $pregunta->getKey(), 'posicion' => 5, 'estado' => false]);
});

it('escapa el texto de las preguntas en las vistas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    PreguntaAts::factory()->create(['pregunta' => '<script>alert(1)</script>']);
    $this->actingAs($admin)->get(route('preguntas.index'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('sugiere la siguiente posición independiente para cada etapa incluyendo preguntas inactivas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 4, 'estado' => false]);
    PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 8]);

    $this->actingAs($admin)->get(route('preguntas.create'))->assertOk()
        ->assertViewHas('siguientesPosiciones', ['Inicial' => 5, 'Final' => 9])
        ->assertSee('value="5"', false);
});

it('rechaza una posición repetida al crear pero permite el mismo número en otra etapa', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1, 'estado' => false]);
    $datos = ['pregunta' => 'Pregunta nueva', 'etapa' => 'Inicial', 'posicion' => 1, 'estado' => 1];

    $this->actingAs($admin)->post(route('preguntas.store'), $datos)->assertSessionHasErrors('posicion');
    $this->assertDatabaseCount('preguntas_ats', 1);
    $this->post(route('preguntas.store'), array_replace($datos, ['etapa' => 'Final']))->assertRedirect(route('preguntas.index'));
    $this->assertDatabaseHas('preguntas_ats', ['pregunta' => 'Pregunta nueva', 'etapa' => 'Final', 'posicion' => 1]);
});

it('intercambia posiciones sin alterar respuestas ni preguntas de otra etapa', function (): void {
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

    expect($primera->fresh()->posicion)->toBe(2)
        ->and($segunda->fresh()->posicion)->toBe(1)
        ->and($segunda->fresh()->estado)->toBeFalse()
        ->and($final->fresh()->posicion)->toBe(2)
        ->and($respuesta->fresh()->id_pregunta)->toBe($primera->getKey());
});

it('rechaza intercambiar posiciones entre etapas distintas sin modificar ninguna pregunta', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $pregunta = PreguntaAts::factory()->create(['etapa' => 'Inicial', 'posicion' => 1]);
    $ocupante = PreguntaAts::factory()->create(['etapa' => 'Final', 'posicion' => 2]);

    $this->actingAs($admin)->put(route('preguntas.update', $pregunta), [
        'pregunta' => $pregunta->pregunta, 'etapa' => 'Final', 'posicion' => 2, 'estado' => 1,
    ])->assertSessionHasErrors('posicion');

    expect($pregunta->fresh()->etapa)->toBe('Inicial')
        ->and($pregunta->fresh()->posicion)->toBe(1)
        ->and($ocupante->fresh()->posicion)->toBe(2);
});
