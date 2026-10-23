<?php

use App\Models\Maquina;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);
beforeEach(function (): void {
    $this->withoutVite();
});

it('restringe la administración al rol autorizado', function (string $rol): void {
    $this->seed(RolesPermisosSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);
    $this->actingAs($usuario)->get('/usuarios')->assertForbidden();
    $this->get('/maquinas')->assertForbidden();
    $this->post('/maquinas', ['nombre' => 'Equipo no permitido', 'estado' => 1])->assertForbidden();
    $this->assertDatabaseMissing('maquinas', ['nombre' => 'Equipo no permitido']);
})->with(['operador', 'supervisor']);

it('registra y actualiza máquinas desde la administración', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin)->get('/maquinas/create')->assertOk();
    $this->post('/maquinas', ['nombre' => 'Transpaleta 01', 'descripcion' => 'Equipo eléctrico', 'estado' => 1])->assertRedirect(route('maquinas.index'));
    $maquina = Maquina::where('nombre', 'Transpaleta 01')->firstOrFail();
    $this->put(route('maquinas.update', $maquina), ['nombre' => 'Transpaleta 01', 'descripcion' => 'Fuera de servicio', 'estado' => 0])->assertRedirect(route('maquinas.index'));
    $this->assertDatabaseHas('maquinas', ['id_maquina' => $maquina->getKey(), 'estado' => false]);
});
it('crea un operador y asigna sus máquinas autorizadas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $maquina = Maquina::factory()->create();
    $this->actingAs($admin)->get('/usuarios/create')->assertOk()->assertSee($maquina->nombre);
    $this->post('/usuarios', ['nombres' => 'Juan', 'apellidos' => 'Pérez', 'dni' => '12345678', 'correo' => 'juan@example.test', 'contrasena' => 'ClaveNueva123!', 'contrasena_confirmation' => 'ClaveNueva123!', 'estado' => 1, 'rol' => 'operador', 'maquinas' => [$maquina->getKey()]])->assertRedirect(route('usuarios.index'));
    $usuario = User::where('correo', 'juan@example.test')->firstOrFail();
    expect($usuario->hasRole('operador'))->toBeTrue();
    $this->assertDatabaseHas('usuarios_maquinas', ['id_usuario' => $usuario->getKey(), 'id_maquina' => $maquina->getKey()]);
    $this->get(route('usuarios.edit', $usuario))->assertOk();
});
it('conserva la contraseña al editar sin una nueva y permite retirar autorizaciones', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $operador = User::factory()->create();
    $operador->assignRole('operador');
    $maquina = Maquina::factory()->create();
    $operador->maquinas()->attach($maquina);
    $hash = $operador->contrasena;
    $this->actingAs($admin)->put(route('usuarios.update', $operador), ['nombres' => $operador->nombres, 'apellidos' => $operador->apellidos, 'dni' => $operador->dni, 'correo' => $operador->correo, 'contrasena' => '', 'estado' => 1, 'rol' => 'operador'])->assertRedirect(route('usuarios.index'));
    expect($operador->fresh()->contrasena)->toBe($hash);
    $this->assertDatabaseMissing('usuarios_maquinas', ['id_usuario' => $operador->getKey()]);
});
it('impide que el administrador desactive su propia cuenta', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $this->actingAs($admin)->put(route('usuarios.update', $admin), ['nombres' => $admin->nombres, 'apellidos' => $admin->apellidos, 'dni' => $admin->dni, 'correo' => $admin->correo, 'estado' => 0, 'rol' => 'administrador'])->assertSessionHasErrors('rol');
    expect($admin->fresh()->estado)->toBeTrue();
});
it('muestra al operador únicamente sus registros y máquinas activas autorizadas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $operador = User::factory()->create(['nombres' => 'Operador propio']);
    $operador->assignRole('operador');
    $autorizada = Maquina::factory()->create(['nombre' => 'Equipo autorizado']);
    $inactiva = Maquina::factory()->create(['nombre' => 'Equipo inactivo', 'estado' => false]);
    $ajena = Maquina::factory()->create(['nombre' => 'Equipo ajeno']);
    $operador->maquinas()->attach([$autorizada->getKey(), $inactiva->getKey()]);
    RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey(), 'id_maquina' => $autorizada->getKey()]);
    RegistroAtsEpp::factory()->create(['id_maquina' => $ajena->getKey()]);
    $this->actingAs($operador)->get('/panel')->assertOk()->assertSee('Equipo autorizado')->assertDontSee('Equipo inactivo')->assertDontSee('Equipo ajeno');
});
it('escapa los datos de usuarios al mostrarlos en las vistas', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    Maquina::factory()->create(['nombre' => '<script>alert(1)</script>']);
    $this->actingAs($admin)->get('/maquinas')->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

it('crea un administrador por consola sin credenciales predeterminadas', function (): void {
    $this->artisan('app:crear-administrador')
        ->expectsQuestion('Nombres', 'Responsable')
        ->expectsQuestion('Apellidos', 'Logística')
        ->expectsQuestion('DNI (8 dígitos)', '87654321')
        ->expectsQuestion('Correo electrónico', 'responsable@example.test')
        ->expectsQuestion('Contraseña (mínimo 12 caracteres, letras y números)', 'ClaveTemporal123!')
        ->expectsQuestion('Confirma la contraseña', 'ClaveTemporal123!')
        ->assertSuccessful();
    $usuario = User::where('correo', 'responsable@example.test')->firstOrFail();
    expect($usuario->hasRole('administrador'))->toBeTrue();
    expect(Hash::check('ClaveTemporal123!', $usuario->contrasena))->toBeTrue();
});
