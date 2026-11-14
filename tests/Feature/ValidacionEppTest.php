<?php

use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\User;
use App\Models\ValidacionEpp;
use App\Services\RegistroAtsService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->freezeSecond();
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
});

it('mantiene el ATS bloqueado durante la espera, muestra el motivo y permite aprobar otra foto', function (): void {
    $operador = User::factory()->create()->assignRole('operador');
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    PreguntaAts::factory()->create(['pregunta' => '¿El área está despejada?']);
    $enviar = function () use ($operador, $maquina): ValidacionEpp {
        $foto = UploadedFile::fake()->image('captura.jpg');
        $token = $this->actingAs($operador)
            ->postJson(route('registros.captura'), ['fotografia' => $foto])
            ->assertOk()->json('captura');
        $this->post(route('validaciones-epp.store'), ['id_maquina' => $maquina->getKey(), 'captura_epp' => $token])
            ->assertSessionHasNoErrors()->assertRedirect(route('registros.create'));

        return ValidacionEpp::query()->latest('id_validacion_epp')->firstOrFail();
    };

    $primera = $enviar();
    $this->get(route('registros.create'))->assertOk()->assertSee('Pendiente')->assertDontSee('¿El área está despejada?');
    $this->getJson(route('validaciones-epp.estado'))->assertOk()->assertExactJson(['estado' => 'Pendiente']);
    $this->get(route('validaciones-epp.index'))->assertForbidden();

    $this->actingAs($supervisor)->get(route('validaciones-epp.index'))->assertOk()
        ->assertSee(route('validaciones-epp.fotografia', $primera));
    $this->post(route('validaciones-epp.resolver', $primera), ['decision' => 'rechazar'])->assertSessionHasErrors('motivo_rechazo');
    $this->post(route('validaciones-epp.resolver', $primera), ['decision' => 'rechazar', 'motivo_rechazo' => 'Faltan guantes.'])
        ->assertSessionHasNoErrors();
    $this->actingAs($operador)->get(route('registros.create'))->assertOk()
        ->assertSee('Faltan guantes.')->assertSee('Enviar foto al supervisor')->assertDontSee('¿El área está despejada?');

    $segunda = $enviar();
    $this->actingAs($supervisor)->post(route('validaciones-epp.resolver', $segunda), ['decision' => 'aprobar'])
        ->assertSessionHasNoErrors();
    $this->actingAs($operador)->get(route('registros.create'))->assertOk()
        ->assertSee('¿El área está despejada?')->assertDontSee('data-camara', false);
    expect($primera->fresh()->estado)->toBe(ValidacionEpp::RECHAZADA)
        ->and($segunda->fresh()->id_supervisor)->toBe($supervisor->getKey())
        ->and($segunda->fresh()->vence_en->diffInMinutes(now(), true))->toBe(15.0);
    $this->assertDatabaseCount('registros_ats_epp', 0);
});

it('rechaza un inicio directo cuando la aprobación no habilita esa labor', function (string $caso): void {
    $operador = User::factory()->create()->assignRole('operador');
    $maquina = Maquina::factory()->create();
    $otraMaquina = Maquina::factory()->create();
    $operador->maquinas()->attach([$maquina->getKey(), $otraMaquina->getKey()]);
    $pregunta = PreguntaAts::factory()->create();
    $validacion = aprobacionEppPrueba($operador, $maquina);

    match ($caso) {
        'pendiente' => $validacion->update(['estado' => ValidacionEpp::PENDIENTE]),
        'rechazada' => $validacion->update(['estado' => ValidacionEpp::RECHAZADA, 'motivo_rechazo' => 'Falta casco.']),
        'vencida' => $this->travel(15)->minutes(),
        'otra persona' => $validacion->update(['id_operador' => User::factory()->create()->getKey()]),
        'otra máquina' => $validacion->update(['id_maquina' => $otraMaquina->getKey()]),
        'sin foto' => Storage::disk('local')->delete($validacion->fotografia),
    };

    $this->actingAs($operador)->postJson(route('registros.store'), [
        'id_maquina' => $maquina->getKey(),
        'id_validacion_epp' => $validacion->getKey(),
        'firma_inicial' => firmaAtsPrueba(),
        'respuestas' => [$pregunta->getKey() => 'Sí'],
        'version_preguntas' => app(RegistroAtsService::class)->prepararInicio($operador)['versionPreguntas'],
    ])->assertUnprocessable()->assertJsonValidationErrors('id_validacion_epp');
    $this->assertDatabaseCount('registros_ats_epp', 0);
    expect($validacion->fresh()->usada_en)->toBeNull();
})->with(['pendiente', 'rechazada', 'vencida', 'otra persona', 'otra máquina', 'sin foto']);

it('protege la foto y no permite que una persona apruebe su propia validación', function (): void {
    $operador = User::factory()->create()->assignRole(['operador', 'supervisor']);
    $otro = User::factory()->create()->assignRole('operador');
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $validacion = ValidacionEpp::factory()->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey()]);
    Storage::disk('local')->put($validacion->fotografia, 'foto de prueba');

    $foto = route('validaciones-epp.fotografia', $validacion);
    $resolver = route('validaciones-epp.resolver', $validacion);
    $this->actingAs($otro)->get($foto)->assertNotFound();
    $this->post($resolver, ['decision' => 'aprobar'])->assertForbidden();
    $this->actingAs($operador)->post($resolver, ['decision' => 'aprobar'])->assertForbidden();
    $this->actingAs($supervisor)->get($foto)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->travel(30)->minutes();
    $this->post($resolver, ['decision' => 'aprobar'])->assertSessionHasErrors('decision');
    expect($validacion->fresh()->id_supervisor)->toBeNull();
});
