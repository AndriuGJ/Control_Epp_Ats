<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    $this->withoutVite();
});

it('muestra el acceso en español y protege el panel', function (): void {
    $this->get('/')->assertRedirect('/panel');
    $this->get('/panel')->assertRedirect(route('login'));
    $this->get('/ingresar')->assertOk()->assertSee('Inicia sesión')->assertSee('Correo electrónico');
});
it('autentica con las columnas en español y cierra la sesión', function (): void {
    $usuario = User::factory()->create(['correo' => 'operador@example.test', 'contrasena' => 'ClavePruebas123!']);
    $this->post('/ingresar', ['correo' => ' OPERADOR@EXAMPLE.TEST ', 'contrasena' => 'ClavePruebas123!'])->assertRedirect(route('panel'));
    $this->assertAuthenticatedAs($usuario);
    expect(Hash::check('ClavePruebas123!', $usuario->contrasena))->toBeTrue();
    $this->get('/panel')->assertOk()->assertSee($usuario->nombres);
    $this->post('/salir')->assertRedirect(route('login'));
    $this->assertGuest();
});
it('rechaza credenciales inválidas sin guardar la contraseña en la sesión', function (): void {
    $usuario = User::factory()->create();
    $this->from('/ingresar')->post('/ingresar', ['correo' => $usuario->correo, 'contrasena' => 'Incorrecta123!'])
        ->assertRedirect('/ingresar')->assertSessionHasErrors('correo')->assertSessionMissing('_old_input.contrasena');
    $this->assertGuest();
});
it('rechaza una cuenta inactiva aunque la contraseña sea correcta', function (): void {
    $usuario = User::factory()->create(['estado' => false]);
    $this->post('/ingresar', ['correo' => $usuario->correo, 'contrasena' => 'ClavePruebas123!'])->assertSessionHasErrors('correo');
    $this->assertGuest();
});
it('cierra la sesión si la cuenta fue desactivada después de ingresar', function (): void {
    $usuario = User::factory()->create(['estado' => false]);
    $this->actingAs($usuario)->get('/panel')->assertRedirect(route('login'));
    $this->assertGuest();
});
it('limita los intentos de acceso', function (): void {
    $this->freezeTime();
    $usuario = User::factory()->create();
    for ($intento = 0; $intento < 5; $intento++) {
        $this->post('/ingresar', ['correo' => $usuario->correo, 'contrasena' => 'Incorrecta123!']);
    }
    $this->post('/ingresar', ['correo' => $usuario->correo, 'contrasena' => 'ClavePruebas123!'])->assertSessionHasErrors(['correo' => 'Demasiados intentos. Intenta nuevamente en 60 segundos.']);
    $this->assertGuest();
});
it('guarda el token de recordar sesión en la columna en español', function (): void {
    $usuario = User::factory()->create();
    $this->post('/ingresar', ['correo' => $usuario->correo, 'contrasena' => 'ClavePruebas123!', 'recordarme' => 1])->assertRedirect(route('panel'));
    expect($usuario->fresh()->token_recuerdo)->not->toBeEmpty();
    expect($usuario->fresh()->toArray())->not->toHaveKeys(['contrasena', 'token_recuerdo']);
});

it('rechaza un correo enviado como lista sin generar un error interno', function (): void {
    $this->post('/ingresar', ['correo' => ['incorrecto'], 'contrasena' => 'ClavePruebas123!'])->assertSessionHasErrors('correo');
    $this->assertGuest();
});
