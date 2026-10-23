<?php

use App\Models\Maquina;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use App\Services\ReporteService;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function usuarioReportesAts(string $rol = 'supervisor'): User
{
    (new RolesPermisosSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->assignRole($rol);

    return $usuario;
}

it('permite consultar reportes al supervisor y al administrador', function (string $rol): void {
    $usuario = usuarioReportesAts($rol);
    $registro = RegistroAtsEpp::factory()->finalizado()->create();
    $respuesta = $this->actingAs($usuario)->get(route('reportes.index'))->assertOk()->assertSee('Descargar PDF')->assertSee($registro->operador->dni)->assertSee('Registros encontrados: 1');
    expect($respuesta->viewData('registros')->modelKeys())->toBe([$registro->getKey()]);
})->with(['supervisor', 'administrador']);

it('exige sesión y permiso para consultar o descargar reportes', function (): void {
    $this->get(route('reportes.index'))->assertRedirect(route('login'));
    $this->get(route('reportes.pdf'))->assertRedirect(route('login'));
    $operador = usuarioReportesAts('operador');
    $this->actingAs($operador)->get(route('reportes.index'))->assertForbidden();
    $this->get(route('reportes.pdf'))->assertForbidden();
});

it('descarga un PDF real con todas las páginas y los registros del filtro', function (): void {
    Storage::fake('local');
    $this->freezeTime();
    $supervisor = usuarioReportesAts();
    $operador = User::factory()->create(['nombres' => 'José Andrés', 'apellidos' => 'Muñoz Peña', 'dni' => '12345678']);
    $maquina = Maquina::factory()->create(['nombre' => 'Montacargas eléctrico de logística']);
    $registros = RegistroAtsEpp::factory()->count(45)->finalizado()->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey(), 'fecha_inicio' => '2026-10-01 08:00:00']);
    RegistroAtsEpp::factory()->create(['fecha_inicio' => '2026-09-01 08:00:00']);
    $exportados = null;
    View::composer('reportes.pdf', function ($vista) use (&$exportados): void {
        $exportados = $vista->getData()['registros'];
    });
    $filtros = ['fecha_desde' => '2026-10-01', 'fecha_hasta' => '2026-10-01', 'id_maquina' => $maquina->getKey(), 'estado' => 'Finalizado', 'revision' => 'Pendiente'];
    $previa = $this->actingAs($supervisor)->get(route('reportes.index', $filtros))->assertOk()->assertSee('Registros encontrados: 45');
    expect($previa->viewData('registros')->count())->toBe(15);
    $respuesta = $this->get(route('reportes.pdf', $filtros + ['page' => 2]))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertDownload('reporte-ats-epp-'.now()->format('Ymd-His').'.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($respuesta->getContent())->toStartWith('%PDF-');
    expect(preg_match_all('/\/Type\s*\/Page\b/', $respuesta->getContent()))->toBeGreaterThan(1);
    expect($respuesta->headers->get('Cache-Control'))->toContain('private', 'no-store');
    expect($exportados->modelKeys())->toBe($registros->reverse()->values()->modelKeys());
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('mantiene los filtros y la ruta de reportes al cambiar de página', function (): void {
    $supervisor = usuarioReportesAts();
    $operador = User::factory()->create(['nombres' => 'Ana', 'apellidos' => 'Gomez']);
    $maquina = Maquina::factory()->create();
    RegistroAtsEpp::factory()->count(16)->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey()]);
    $respuesta = $this->actingAs($supervisor)->get(route('reportes.index', ['trabajador' => 'Ana', 'extra' => 'ignorar']))->assertOk();
    $paginador = $respuesta->viewData('registros');
    expect(parse_url($paginador->nextPageUrl(), PHP_URL_PATH))->toBe('/reportes');
    parse_str(parse_url($paginador->nextPageUrl(), PHP_URL_QUERY), $consulta);
    expect($consulta)->toBe(['trabajador' => 'Ana', 'page' => '2']);
    $this->get($paginador->nextPageUrl())->assertOk()->assertSee(route('reportes.pdf', ['trabajador' => 'Ana']));
});

it('genera un PDF vacío con un mensaje claro cuando no hay coincidencias', function (): void {
    $supervisor = usuarioReportesAts();
    $this->actingAs($supervisor)->get(route('reportes.index'))->assertOk()->assertSee('No se encontraron registros');
    $exportados = null;
    View::composer('reportes.pdf', function ($vista) use (&$exportados): void {
        $exportados = $vista->getData()['registros'];
    });
    $respuesta = $this->get(route('reportes.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($respuesta->getContent())->toStartWith('%PDF-');
    expect($exportados)->toBeEmpty();
});

it('valida el mismo intervalo de fechas y estados en la consulta y la descarga', function (string $ruta): void {
    $supervisor = usuarioReportesAts();
    $this->actingAs($supervisor)->getJson(route($ruta, ['fecha_desde' => '2026-10-10', 'fecha_hasta' => '2026-10-01', 'estado' => 'Inexistente']))->assertUnprocessable()->assertJsonValidationErrors(['fecha_hasta', 'estado']);
})->with(['reportes.index', 'reportes.pdf']);

it('rechaza una descarga demasiado grande sin entregar un PDF truncado', function (): void {
    $supervisor = usuarioReportesAts();
    $operador = User::factory()->create();
    $maquina = Maquina::factory()->create();
    RegistroAtsEpp::factory()->count(ReporteService::MAXIMO_REGISTROS + 1)->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey()]);
    $this->actingAs($supervisor)->get(route('reportes.index'))->assertOk()->assertSee('Reduce el intervalo de fechas');
    $this->getJson(route('reportes.pdf'))->assertUnprocessable()->assertJsonValidationErrors('reporte');
});

it('conserva el alcance de consulta incluso para un permiso de reportes asignado directamente', function (): void {
    $operador = usuarioReportesAts('operador');
    $operador->givePermissionTo('reportes.generar');
    $propio = RegistroAtsEpp::factory()->create(['id_operador' => $operador->getKey()]);
    $ajeno = RegistroAtsEpp::factory()->create();
    $exportados = null;
    View::composer('reportes.pdf', function ($vista) use (&$exportados): void {
        $exportados = $vista->getData()['registros'];
    });
    $this->actingAs($operador)->get(route('reportes.pdf'))->assertOk();
    expect($exportados->modelKeys())->toBe([$propio->getKey()]);
    expect($exportados->contains('id_registro', $ajeno->getKey()))->toBeFalse();
});

it('escapa textos de usuarios y filtros en la plantilla PDF', function (): void {
    $supervisor = usuarioReportesAts();
    $html = view('reportes.pdf', [
        'registros' => collect(), 'filtros' => ['trabajador' => '<script>alert(1)</script>'],
        'maquina' => null, 'generadoPor' => $supervisor, 'fechaGeneracion' => now(),
    ])->render();
    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')->not->toContain('<script>alert(1)</script>');
});

it('permite descargar exactamente el máximo de registros sin recortar el listado', function (): void {
    $supervisor = usuarioReportesAts();
    $operador = User::factory()->create();
    $maquina = Maquina::factory()->create();
    RegistroAtsEpp::factory()->count(ReporteService::MAXIMO_REGISTROS)->create(['id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey()]);
    $exportados = null;
    View::composer('reportes.pdf', function ($vista) use (&$exportados): void {
        $exportados = $vista->getData()['registros'];
    });
    $respuesta = $this->actingAs($supervisor)->get(route('reportes.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($respuesta->getContent())->toStartWith('%PDF-');
    expect($exportados)->toHaveCount(ReporteService::MAXIMO_REGISTROS);
});
