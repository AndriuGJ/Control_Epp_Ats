<?php

use App\Models\Configuracion;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesPermisosSeeder::class);
});

it('limita la configuración a administradores', function (): void {
    $supervisor = User::factory()->create()->assignRole('supervisor');

    $this->get(route('configuracion.edit'))->assertRedirect(route('login'));
    $this->actingAs($supervisor)->get(route('configuracion.edit'))->assertForbidden();
    $this->put(route('configuracion.update'), [])->assertForbidden();
    $this->get(route('panel'))->assertDontSee(route('configuracion.edit'));
});

it('guarda la marca y el SMTP, protege la clave y aplica el logo al acceso y al reporte', function (): void {
    Storage::fake('local');
    $administrador = User::factory()->create()->assignRole('administrador');
    $marca = [
        'nombre_empresa' => 'Macro de prueba',
        'area' => 'Seguridad',
        'avisos_epp' => '1',
        'servidor_smtp' => 'smtp.gmail.com',
        'puerto_smtp' => '465',
        'usuario_smtp' => 'remitente@example.com',
    ];
    $this->actingAs($administrador)->put(route('configuracion.update'), $marca + [
        'logo' => UploadedFile::fake()->image('marca.png', 30, 30),
        'contrasena_smtp' => 'aplicacion-de-prueba',
    ])->assertSessionHasNoErrors()->assertRedirect(route('configuracion.edit'));

    $configuracion = Configuracion::query()->findOrFail(1);
    Storage::disk('local')->assertExists($configuracion->logo);
    expect($configuracion->getRawOriginal('contrasena_smtp'))->not->toBe('aplicacion-de-prueba')
        ->and($configuracion->contrasena_smtp)->toBe('aplicacion-de-prueba');
    $logo = route('marca.logo', ['version' => basename($configuracion->logo)]);
    $this->get(route('configuracion.edit'))->assertOk()->assertDontSee('aplicacion-de-prueba');
    $this->get(route('reportes.index'))->assertOk()->assertSee($logo);
    $respuesta = $this->get(route('reportes.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($respuesta->getContent())->toContain('/Subtype /Image');
    $this->put(route('configuracion.update'), $marca)->assertSessionHasNoErrors();
    expect($configuracion->refresh()->contrasena_smtp)->toBe('aplicacion-de-prueba');
    $this->put(route('configuracion.update'), array_replace($marca, ['usuario_smtp' => 'otro@example.com']))
        ->assertSessionHasErrors('contrasena_smtp');

    $this->post(route('logout'));
    $this->get(route('login'))->assertOk()->assertSee('Macro de prueba')->assertSee($logo);
    $this->get($logo)->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('rechaza archivos y servidores no permitidos sin guardar la clave en la sesión', function (): void {
    $administrador = User::factory()->create()->assignRole('administrador');
    $this->actingAs($administrador)->from(route('configuracion.edit'))->put(route('configuracion.update'), [
        'nombre_empresa' => 'Macro',
        'area' => 'Logística',
        'avisos_epp' => '1',
        'servidor_smtp' => '127.0.0.1',
        'puerto_smtp' => '465',
        'usuario_smtp' => 'remitente@example.com',
        'contrasena_smtp' => 'clave-invalida-en-el-formulario',
        'logo' => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
    ])->assertSessionHasErrors(['logo', 'servidor_smtp'])->assertSessionMissing('_old_input.contrasena_smtp');

    expect(Configuracion::query()->findOrFail(1)->contrasena_smtp)->toBeNull();
});
