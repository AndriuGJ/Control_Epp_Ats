<?php

use App\Http\Requests\CapturarEppRequest;
use App\Http\Requests\FiltrarRegistrosAtsRequest;
use App\Http\Requests\FiltrarReportesRequest;
use App\Http\Requests\FinalizarRegistroAtsRequest;
use App\Http\Requests\GuardarMaquinaRequest;
use App\Http\Requests\GuardarPreguntaAtsRequest;
use App\Http\Requests\GuardarUsuarioRequest;
use App\Http\Requests\IniciarRegistroAtsRequest;
use App\Http\Requests\IniciarSesionRequest;
use App\Http\Requests\RevisarRegistroAtsRequest;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

it('resuelve los mensajes de cada formulario en español sin depender del idioma de respaldo', function (string $idioma, string $clase, array $datos, string $campo, string $mensaje): void {
    app()->setLocale($idioma);
    app('translator')->setFallback('en');
    $request = $clase::create('/formulario', 'POST', $datos);

    $validador = Validator::make($datos, $request->rules(), $request->messages(), $request->attributes());

    expect($validador->fails())->toBeTrue()
        ->and($validador->errors()->first($campo))->toBe($mensaje);
})->with(['es', 'es_PE'])->with([
    'captura EPP' => [CapturarEppRequest::class, [], 'fotografia', 'Toma una fotografía con la cámara.'],
    'filtros de registros' => [FiltrarRegistrosAtsRequest::class, ['fecha_desde' => 'incorrecta'], 'fecha_desde', 'Selecciona una fecha de inicio válida.'],
    'filtros de reportes' => [FiltrarReportesRequest::class, ['fecha_hasta' => 'incorrecta'], 'fecha_hasta', 'Selecciona una fecha de fin válida.'],
    'respuestas finales' => [FinalizarRegistroAtsRequest::class, ['respuestas' => [7 => '']], 'respuestas.7', 'Marca Sí o No en todas las preguntas finales.'],
    'máquina' => [GuardarMaquinaRequest::class, ['descripcion' => []], 'descripcion', 'El campo descripción debe ser texto.'],
    'posición de pregunta' => [GuardarPreguntaAtsRequest::class, ['posicion' => 0], 'posicion', 'La posición debe ser al menos 1.'],
    'correo del usuario' => [GuardarUsuarioRequest::class, ['correo' => 'incorrecto'], 'correo', 'El campo correo electrónico debe ser un correo válido.'],
    'contraseña del usuario' => [GuardarUsuarioRequest::class, [], 'contrasena', 'El campo contraseña es obligatorio.'],
    'respuestas iniciales' => [IniciarRegistroAtsRequest::class, ['respuestas' => [7 => '']], 'respuestas.7', 'Debes responder todas las preguntas iniciales.'],
    'inicio de sesión' => [IniciarSesionRequest::class, [], 'correo', 'Ingresa tu correo electrónico.'],
    'revisión' => [RevisarRegistroAtsRequest::class, [], 'firma_revisor', 'Dibuja tu firma antes de guardar la revisión.'],
]);

it('entrega los mensajes centralizados al validar una solicitud HTTP', function (): void {
    app()->setLocale('es_PE');
    $this->post(route('sesion.store'), [])->assertSessionHasErrors([
        'correo' => 'Ingresa tu correo electrónico.',
        'contrasena' => 'Ingresa tu contraseña.',
    ]);

    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin)->post(route('usuarios.store'), [])->assertSessionHasErrors([
        'correo' => 'El campo correo electrónico es obligatorio.',
        'contrasena' => 'El campo contraseña es obligatorio.',
        'dni' => 'El campo DNI es obligatorio.',
    ]);
});

it('permite cambiar el texto desde el catálogo sin modificar los Request', function (): void {
    app()->setLocale('es_PE');
    app('translator')->addLines(['validation.formularios.iniciar_sesion.correo.required' => 'Escribe tu correo para continuar.'], 'es_PE');

    $this->post(route('sesion.store'), [])->assertSessionHasErrors([
        'correo' => 'Escribe tu correo para continuar.',
    ]);
});
