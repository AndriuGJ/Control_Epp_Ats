<?php

use App\Models\Maquina;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function fotografiaMaquinaPrueba(): UploadedFile
{
    return UploadedFile::fake()->createWithContent('equipo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
}

it('registra una máquina con fotografía y la muestra en el listado y la edición', function (): void {
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');

    $this->actingAs($admin)->post(route('maquinas.store'), [
        'nombre' => 'Transpaleta con fotografía', 'estado' => 1, 'fotografia' => fotografiaMaquinaPrueba(),
    ])->assertRedirect(route('maquinas.index'));

    $maquina = Maquina::where('nombre', 'Transpaleta con fotografía')->sole();
    Storage::disk('local')->assertExists($maquina->fotografia);
    $this->get(route('maquinas.index'))->assertOk()->assertSee(route('maquinas.fotografia', $maquina));
    $this->get(route('maquinas.edit', $maquina))->assertOk()->assertSee('multipart/form-data')->assertSee(route('maquinas.fotografia', $maquina));
    $this->get(route('maquinas.fotografia', $maquina))->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('conserva la fotografía al editar sin seleccionar otra imagen', function (): void {
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $ruta = fotografiaMaquinaPrueba()->store('maquinas', 'local');
    $maquina = Maquina::factory()->create(['fotografia' => $ruta]);

    $this->actingAs($admin)->put(route('maquinas.update', $maquina), ['nombre' => 'Nombre actualizado', 'estado' => 1])->assertRedirect(route('maquinas.index'));

    $this->assertDatabaseHas('maquinas', ['id_maquina' => $maquina->getKey(), 'nombre' => 'Nombre actualizado', 'fotografia' => $ruta]);
    Storage::disk('local')->assertExists($ruta);
});

it('reemplaza la fotografía anterior al guardar una nueva', function (): void {
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $rutaAnterior = fotografiaMaquinaPrueba()->store('maquinas', 'local');
    $maquina = Maquina::factory()->create(['fotografia' => $rutaAnterior]);

    $this->actingAs($admin)->put(route('maquinas.update', $maquina), [
        'nombre' => $maquina->nombre, 'estado' => 1, 'fotografia' => fotografiaMaquinaPrueba(),
    ])->assertRedirect(route('maquinas.index'));

    expect($maquina->fresh()->fotografia)->not->toBe($rutaAnterior);
    Storage::disk('local')->assertMissing($rutaAnterior);
    Storage::disk('local')->assertExists($maquina->fresh()->fotografia);
});

it('rechaza fotografías inválidas sin cambiar la imagen actual', function (Closure $archivo, string $mensaje): void {
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $ruta = fotografiaMaquinaPrueba()->store('maquinas', 'local');
    $maquina = Maquina::factory()->create(['fotografia' => $ruta]);

    $this->actingAs($admin)->put(route('maquinas.update', $maquina), [
        'nombre' => $maquina->nombre, 'estado' => 1, 'fotografia' => $archivo(),
    ])->assertSessionHasErrors(['fotografia' => $mensaje]);

    expect($maquina->fresh()->fotografia)->toBe($ruta);
    Storage::disk('local')->assertExists($ruta);
    expect(Storage::disk('local')->allFiles('maquinas'))->toHaveCount(1);
})->with([
    'texto disfrazado de PNG' => [fn () => UploadedFile::fake()->createWithContent('equipo.png', 'Esto no es una imagen.')->mimeType('text/plain'), 'La fotografía debe ser una imagen válida.'],
    'imagen que supera 4 MB' => [fn () => fotografiaMaquinaPrueba()->size(4097), 'La fotografía no debe superar los 4 MB.'],
    'SVG no permitido' => [fn () => UploadedFile::fake()->createWithContent('equipo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>'), 'La fotografía debe ser una imagen válida.'],
]);

it('protege la fotografía contra visitantes y operadores sin permiso de administración', function (): void {
    Storage::fake('local');
    $this->seed(RolesPermisosSeeder::class);
    $ruta = fotografiaMaquinaPrueba()->store('maquinas', 'local');
    $maquina = Maquina::factory()->create(['fotografia' => $ruta]);
    $operador = User::factory()->create();
    $operador->assignRole('operador');

    $this->get(route('maquinas.fotografia', $maquina))->assertRedirect(route('login'));
    $this->actingAs($operador)->get(route('maquinas.fotografia', $maquina))->assertForbidden();
    $this->post(route('maquinas.store'), ['nombre' => 'Sin permiso', 'estado' => 1, 'fotografia' => fotografiaMaquinaPrueba()])->assertForbidden();
    $this->assertDatabaseMissing('maquinas', ['nombre' => 'Sin permiso']);
    expect(Storage::disk('local')->allFiles('maquinas'))->toHaveCount(1);
});

it('devuelve no encontrado si la máquina no tiene fotografía', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('administrador');
    $maquina = Maquina::factory()->create(['fotografia' => null]);

    $this->actingAs($admin)->get(route('maquinas.fotografia', $maquina))->assertNotFound();
});

it('muestra Reportes en el menú y permite abrir su vista a los roles autorizados', function (string $rol): void {
    $this->seed(RolesPermisosSeeder::class);
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    $this->actingAs($usuario)->get(route('panel'))->assertOk()->assertSee(route('reportes.index'));
    $this->get(route('reportes.index'))->assertOk()->assertSee('Sección en preparación');
})->with(['administrador', 'supervisor']);

it('oculta Reportes y restringe su acceso a los operadores', function (): void {
    $this->seed(RolesPermisosSeeder::class);
    $operador = User::factory()->create();
    $operador->assignRole('operador');

    $this->get(route('reportes.index'))->assertRedirect(route('login'));
    $this->actingAs($operador)->get(route('panel'))->assertOk()->assertDontSee(route('reportes.index'));
    $this->get(route('reportes.index'))->assertForbidden();
});
