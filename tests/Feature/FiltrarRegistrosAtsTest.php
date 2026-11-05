<?php

use App\Models\Maquina;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function usuarioFiltrosAts(string $rol = 'supervisor'): User
{
    (new RolesPermisosSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    return $usuario;
}

it('permite combinar trabajador máquina fecha estado y revisión sin incluir otros registros', function (): void {
    $supervisor = usuarioFiltrosAts();
    $trabajador = User::factory()->create(['nombres' => 'Juan Carlos', 'apellidos' => 'Torres Diaz', 'dni' => '45678901']);
    $maquina = Maquina::factory()->create(['estado' => false]);
    $esperado = RegistroAtsEpp::factory()->finalizado()->create(['id_operador' => $trabajador->getKey(), 'id_maquina' => $maquina->getKey(), 'fecha_inicio' => '2026-10-05 14:30:00']);
    RegistroAtsEpp::factory()->finalizado()->create(['id_operador' => $trabajador->getKey(), 'id_maquina' => $maquina->getKey(), 'fecha_inicio' => '2026-10-05 14:30:00', 'fecha_revision' => '2026-10-06 10:00:00', 'id_revisor' => $supervisor->getKey()]);
    RegistroAtsEpp::factory()->create(['id_operador' => $trabajador->getKey(), 'id_maquina' => $maquina->getKey(), 'fecha_inicio' => '2026-10-05 14:30:00']);
    RegistroAtsEpp::factory()->finalizado()->create(['id_operador' => $trabajador->getKey(), 'fecha_inicio' => '2026-10-05 14:30:00']);
    RegistroAtsEpp::factory()->finalizado()->create(['id_operador' => $trabajador->getKey(), 'id_maquina' => $maquina->getKey(), 'fecha_inicio' => '2026-09-01 14:30:00']);
    RegistroAtsEpp::factory()->finalizado()->create(['id_maquina' => $maquina->getKey(), 'fecha_inicio' => '2026-10-05 14:30:00']);
    $respuesta = $this->actingAs($supervisor)->get(route('registros.index', [
        'trabajador' => 'Juan Torres', 'id_maquina' => $maquina->getKey(),
        'fecha_desde' => '2026-10-05', 'fecha_hasta' => '2026-10-05',
        'estado' => 'Finalizado', 'revision' => 'Pendiente',
    ]))->assertOk()->assertSee('Registros encontrados: 1');
    expect($respuesta->viewData('registros')->modelKeys())->toBe([$esperado->getKey()]);
    expect($respuesta->viewData('maquinas')->contains('id_maquina', $maquina->getKey()))->toBeTrue();
});

it('busca por nombres apellidos combinados o por el DNI del trabajador', function (string $busqueda): void {
    $supervisor = usuarioFiltrosAts();
    $trabajador = User::factory()->create(['nombres' => 'Juan Carlos', 'apellidos' => 'Torres Diaz', 'dni' => '45678901']);
    $esperado = RegistroAtsEpp::factory()->create(['id_operador' => $trabajador->getKey()]);
    $otro = User::factory()->create(['nombres' => 'Luis', 'apellidos' => 'Flores', 'dni' => '98765432']);
    RegistroAtsEpp::factory()->create(['id_operador' => $otro->getKey()]);
    $respuesta = $this->actingAs($supervisor)->get(route('registros.index', ['trabajador' => $busqueda]))->assertOk();
    expect($respuesta->viewData('registros')->modelKeys())->toBe([$esperado->getKey()]);
})->with(['nombre parcial' => 'Carlo', 'apellido' => 'Torres', 'nombre completo' => 'Juan Torres', 'varios espacios' => '  Juan   Torres  ', 'dni' => '45678901', 'dni parcial con cero' => '0']);

it('incluye ambos extremos del intervalo de fechas y excluye días adyacentes', function (): void {
    $supervisor = usuarioFiltrosAts();
    $primero = RegistroAtsEpp::factory()->create(['fecha_inicio' => '2026-10-05 00:00:00']);
    $ultimo = RegistroAtsEpp::factory()->create(['fecha_inicio' => '2026-10-06 23:59:59']);
    RegistroAtsEpp::factory()->create(['fecha_inicio' => '2026-10-04 23:59:59']);
    RegistroAtsEpp::factory()->create(['fecha_inicio' => '2026-10-07 00:00:00']);
    $respuesta = $this->actingAs($supervisor)->get(route('registros.index', ['fecha_desde' => '2026-10-05', 'fecha_hasta' => '2026-10-06']))->assertOk();
    expect($respuesta->viewData('registros')->modelKeys())->toBe([$ultimo->getKey(), $primero->getKey()]);
});

it('permite filtrar con una sola fecha límite', function (array $filtros, string $fechaEsperada): void {
    $supervisor = usuarioFiltrosAts();
    RegistroAtsEpp::factory()->create(['fecha_inicio' => '2026-10-04 23:59:59']);
    RegistroAtsEpp::factory()->create(['fecha_inicio' => '2026-10-06 00:00:00']);
    $respuesta = $this->actingAs($supervisor)->get(route('registros.index', $filtros))->assertOk();
    expect($respuesta->viewData('registros')->pluck('fecha_inicio')->map->toDateTimeString()->all())->toBe([$fechaEsperada]);
})->with([
    'solo desde' => [['fecha_desde' => '2026-10-05'], '2026-10-06 00:00:00'],
    'solo hasta' => [['fecha_hasta' => '2026-10-05'], '2026-10-04 23:59:59'],
]);

it('separa el estado de la labor del estado de revisión', function (array $filtros, string $esperado): void {
    $supervisor = usuarioFiltrosAts();
    $registros = [
        'curso' => RegistroAtsEpp::factory()->create(),
        'pendiente' => RegistroAtsEpp::factory()->finalizado()->create(),
        'revisado' => RegistroAtsEpp::factory()->finalizado()->create(['fecha_revision' => now(), 'id_revisor' => $supervisor->getKey()]),
    ];
    $respuesta = $this->actingAs($supervisor)->get(route('registros.index', $filtros))->assertOk();
    expect($respuesta->viewData('registros')->modelKeys())->toBe([$registros[$esperado]->getKey()]);
})->with([
    'en curso' => [['estado' => 'En curso'], 'curso'],
    'pendiente' => [['revision' => 'Pendiente'], 'pendiente'],
    'revisado' => [['revision' => 'Revisado'], 'revisado'],
]);

it('mantiene el aislamiento del operador y no revela máquinas de registros ajenos', function (): void {
    $operador = usuarioFiltrosAts('operador');
    $maquinaPropia = Maquina::factory()->create(['nombre' => 'Máquina del operador', 'estado' => false]);
    $maquinaAjena = Maquina::factory()->create(['nombre' => 'Máquina confidencial']);
    $propio = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquinaPropia->getKey()]);
    $otro = User::factory()->create(['nombres' => 'Trabajador reservado', 'apellidos' => 'Ajeno']);
    RegistroAtsEpp::factory()->create(['id_operador' => $otro->getKey(), 'id_maquina' => $maquinaAjena->getKey()]);
    $respuesta = $this->actingAs($operador)->get(route('registros.index'))->assertOk()->assertDontSee('Máquina confidencial')->assertDontSee('Trabajador reservado');
    expect($respuesta->viewData('registros')->modelKeys())->toBe([$propio->getKey()]);
    expect($respuesta->viewData('maquinas')->modelKeys())->toBe([$maquinaPropia->getKey()]);
    $filtrado = $this->get(route('registros.index', ['trabajador' => 'reservado', 'id_maquina' => $maquinaAjena->getKey(), 'id_operador' => $otro->getKey()]))->assertOk()->assertSee('No se encontraron registros');
    expect($filtrado->viewData('registros')->total())->toBe(0);
});

it('conserva los filtros en la paginación y permite limpiarlos', function (): void {
    $supervisor = usuarioFiltrosAts();
    $operador = User::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Gomez']);
    $maquina = Maquina::factory()->create();
    $registros = RegistroAtsEpp::factory()->count(16)->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey(), 'fecha_inicio' => '2026-10-05 10:00:00']);
    $filtros = ['trabajador' => 'Ana', 'id_maquina' => $maquina->getKey(), 'fecha_desde' => '2026-10-05', 'fecha_hasta' => '2026-10-05', 'estado' => 'En curso'];
    $respuesta = $this->actingAs($supervisor)->get(route('registros.index', $filtros + ['extra' => 'no-conservar']))->assertOk()->assertSee('Limpiar filtros');
    $paginador = $respuesta->viewData('registros');
    parse_str(parse_url($paginador->nextPageUrl(), PHP_URL_QUERY), $consulta);
    expect($consulta)->toMatchArray($filtros + ['page' => '2']);
    expect($consulta)->not->toHaveKey('extra');
    $segunda = $this->get($paginador->nextPageUrl())->assertOk();
    expect($segunda->viewData('registros')->modelKeys())->toBe([$registros->first()->getKey()]);
    $limpia = $this->get(route('registros.index'))->assertOk();
    expect($limpia->viewData('filtros'))->toBeEmpty();
    expect($limpia->viewData('registros')->total())->toBe(16);
});

it('valida los filtros antes de consultar la base de datos', function (array $filtros, string $campo): void {
    $supervisor = usuarioFiltrosAts();
    $this->actingAs($supervisor)->getJson(route('registros.index', $filtros))->assertUnprocessable()->assertJsonValidationErrors($campo);
})->with([
    'fecha inexistente' => [['fecha_desde' => '2026-02-30'], 'fecha_desde'],
    'fecha con otro formato' => [['fecha_hasta' => '23/10/2026'], 'fecha_hasta'],
    'intervalo invertido' => [['fecha_desde' => '2026-10-06', 'fecha_hasta' => '2026-10-05'], 'fecha_hasta'],
    'trabajador como arreglo' => [['trabajador' => ['Ana']], 'trabajador'],
    'búsqueda demasiado larga' => [['trabajador' => str_repeat('a', 101)], 'trabajador'],
    'máquina no numérica' => [['id_maquina' => 'incorrecta'], 'id_maquina'],
    'máquina negativa' => [['id_maquina' => -1], 'id_maquina'],
    'estado desconocido' => [['estado' => 'Borrado'], 'estado'],
    'revisión desconocida' => [['revision' => 'Cualquiera'], 'revision'],
    'página no numérica' => [['page' => 'incorrecta'], 'page'],
    'página cero' => [['page' => 0], 'page'],
]);

it('trata la búsqueda como un valor y escapa su contenido al mostrarlo en el formulario', function (): void {
    $supervisor = usuarioFiltrosAts();
    RegistroAtsEpp::factory()->create();
    $busqueda = '<script>alert(1)</script>';
    $respuesta = $this->actingAs($supervisor)->get(route('registros.index', ['trabajador' => $busqueda]))->assertOk()->assertSee($busqueda)->assertDontSee($busqueda, false);
    expect($respuesta->viewData('registros')->total())->toBe(0);
    $inyeccion = $this->get(route('registros.index', ['trabajador' => "' OR 1=1 --"]))->assertOk();
    expect($inyeccion->viewData('registros')->total())->toBe(0);
});

it('exige acceso y permiso para consultar los filtros de registros', function (): void {
    $this->get(route('registros.index'))->assertRedirect(route('login'));
    $sinPermiso = User::factory()->create();
    $this->actingAs($sinPermiso)->get(route('registros.index'))->assertForbidden();
});
