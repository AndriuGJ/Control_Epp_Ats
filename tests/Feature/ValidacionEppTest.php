<?php

use App\Jobs\EnviarAvisoValidacionEpp;
use App\Mail\AvisoValidacionEpp;
use App\Models\Configuracion;
use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\User;
use App\Models\ValidacionEpp;
use App\Services\RegistroAtsService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Testing\Fakes\MailFake;

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

    $this->travel(10)->minutes();
    $this->post($resolver, ['decision' => 'aprobar'])->assertSessionHasErrors('decision');
    expect($validacion->fresh()->id_supervisor)->toBeNull();
});

it('avisa a los revisores activos al enviar y a los cinco minutos, y deja reenviar al vencer', function (): void {
    $correo = new MailFake(app('mail.manager'));
    Mail::shouldReceive('build')->twice()->andReturn($correo);
    $operador = User::factory()->create()->assignRole(['operador', 'supervisor']);
    $supervisor = User::factory()->create()->assignRole(['supervisor', 'administrador']);
    $administrador = User::factory()->create()->assignRole('administrador');
    $inactivo = User::factory()->create(['estado' => false])->assignRole('supervisor');
    $otroOperador = User::factory()->create()->assignRole('operador');
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    PreguntaAts::factory()->create();
    Configuracion::query()->findOrFail(1)->update([
        'avisos_epp' => true, 'usuario_smtp' => 'avisos@example.com', 'contrasena_smtp' => 'clave-de-prueba',
    ]);
    config(['queue.default' => 'sync']);

    $capturar = fn (): string => $this->actingAs($operador)
        ->postJson(route('registros.captura'), ['fotografia' => UploadedFile::fake()->image('captura.jpg')])
        ->assertOk()->json('captura');
    $enviar = fn (string $token) => $this->post(route('validaciones-epp.store'), [
        'id_maquina' => $maquina->getKey(),
        'captura_epp' => $token,
    ])->assertRedirect(route('registros.create'))->assertSessionHasNoErrors();
    $procesarCola = fn () => $this->artisan('queue:work database --queue=epp --once')->assertSuccessful();

    $token = $capturar();
    $fechaCaptura = now();
    $this->travel(2)->minutes();
    $fechaEnvio = now();
    $enviar($token);
    $validacion = ValidacionEpp::query()->firstOrFail();

    expect($validacion->fecha_fotografia->equalTo($fechaCaptura))->toBeTrue()
        ->and($validacion->vence_en->equalTo($fechaEnvio->copy()->addMinutes(10)))->toBeTrue();
    $procesarCola();
    $correo->assertSent(AvisoValidacionEpp::class, fn (AvisoValidacionEpp $aviso): bool => ! $aviso->recordatorio
        && $aviso->hasBcc($supervisor->correo) && $aviso->hasBcc($administrador->correo)
        && ! $aviso->hasBcc($inactivo->correo) && ! $aviso->hasBcc($otroOperador->correo)
        && ! $aviso->hasBcc($operador->correo) && count($aviso->bcc) === 2
        && $aviso->validacion->is($validacion));

    $this->travelTo($fechaEnvio->copy()->addMinutes(4));
    $procesarCola();
    $correo->assertSentCount(1);
    $this->travelTo($fechaEnvio->copy()->addMinutes(5));
    $procesarCola();
    $correo->assertSent(AvisoValidacionEpp::class, fn (AvisoValidacionEpp $aviso): bool => $aviso->recordatorio
        && $aviso->hasBcc($supervisor->correo) && $aviso->hasBcc($administrador->correo));
    $correo->assertSentCount(2);

    $this->travelTo($fechaEnvio->copy()->addMinutes(10));
    $this->getJson(route('validaciones-epp.estado'))->assertExactJson(['estado' => 'Vencida']);
    $this->get(route('registros.create'))->assertOk()->assertSee('Coordina con el supervisor');
    $this->actingAs($supervisor)->post(route('validaciones-epp.resolver', $validacion), ['decision' => 'aprobar'])
        ->assertSessionHasErrors('decision');
    $enviar($capturar());
    $this->assertDatabaseCount('validaciones_epp', 2);
    Storage::disk('local')->assertExists($validacion->fotografia);
});

it('omite el recordatorio si ya no procede', function (string $caso): void {
    Mail::shouldReceive('build')->never();
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $validacion = ValidacionEpp::factory()->create();
    $configuracion = Configuracion::query()->findOrFail(1);
    $configuracion->update([
        'avisos_epp' => true, 'usuario_smtp' => 'avisos@example.com', 'contrasena_smtp' => 'clave-de-prueba',
    ]);
    $this->travel(5)->minutes();

    match ($caso) {
        'aprobada' => $validacion->update(['estado' => ValidacionEpp::APROBADA]),
        'rechazada' => $validacion->update(['estado' => ValidacionEpp::RECHAZADA]),
        'vencida' => $this->travel(5)->minutes(),
        'supervisor inactivo' => $supervisor->update(['estado' => false]),
        'avisos desactivados' => $configuracion->update(['avisos_epp' => false]),
        'destinatario sin permisos' => $supervisor->removeRole('supervisor'),
    };

    app()->call([(new EnviarAvisoValidacionEpp($validacion->getKey(), recordatorio: true)), 'handle']);
})->with(['aprobada', 'rechazada', 'vencida', 'supervisor inactivo', 'destinatario sin permisos', 'avisos desactivados']);
