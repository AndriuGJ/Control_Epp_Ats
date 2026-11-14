<?php

use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 11, 13)->setTime(20, 0));
    $this->seed(RolesPermisosSeeder::class);
});

it('suma los ATS finalizados por máquina sin duplicar tramos y avisa al alcanzar el límite', function (): void {
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $maquina = Maquina::factory()->create(['intervalo_mantenimiento_horas' => 4]);
    $otra = Maquina::factory()->create(['intervalo_mantenimiento_horas' => 100]);

    foreach ([['08:00', '12:00'], ['09:00', '10:00'], ['10:30', '12:00']] as [$inicio, $fin]) {
        RegistroAtsEpp::factory()->finalizado()->create([
            'id_maquina' => $maquina->getKey(),
            'fecha_inicio' => '2026-11-13 '.$inicio.':00',
            'fecha_finalizacion' => '2026-11-13 '.$fin.':00',
        ]);
    }
    RegistroAtsEpp::factory()->create([
        'id_maquina' => $maquina->getKey(),
        'fecha_inicio' => '2026-11-13 13:00:00',
    ]);
    RegistroAtsEpp::factory()->finalizado()->create([
        'id_maquina' => $otra->getKey(),
        'fecha_inicio' => '2026-11-13 08:00:00',
        'fecha_finalizacion' => '2026-11-13 09:30:00',
    ]);

    $this->actingAs($supervisor)->get(route('mantenimientos.index'))
        ->assertOk()
        ->assertSee('Mantenimiento pendiente')
        ->assertViewHas('resumenes', fn (array $resumenes): bool => $resumenes[$maquina->getKey()]['minutos'] === 240
            && $resumenes[$maquina->getKey()]['restantes'] === 0
            && $resumenes[$otra->getKey()]['minutos'] === 90
            && $resumenes[$otra->getKey()]['restantes'] === 5910);
});

it('guarda el uso del ciclo y cuenta por separado los tramos posteriores al mantenimiento', function (): void {
    $admin = User::factory()->create()->assignRole('administrador');
    $maquina = Maquina::factory()->create(['intervalo_mantenimiento_horas' => 4]);
    Mantenimiento::factory()->for($maquina, 'maquina')->create(['fecha_mantenimiento' => '2026-11-13 08:30:00']);
    RegistroAtsEpp::factory()->for($maquina, 'maquina')->finalizado()->create([
        'fecha_inicio' => '2026-11-13 08:00:00',
        'fecha_finalizacion' => '2026-11-13 13:00:00',
    ]);

    $this->actingAs($admin)->post(route('mantenimientos.store', $maquina), [
        'fecha_mantenimiento' => '2026-11-13T12:00',
        'observaciones' => 'Limpieza y revisión preventiva.',
    ])->assertSessionHasNoErrors()->assertRedirect(route('mantenimientos.show', $maquina));

    $this->assertDatabaseHas('mantenimientos', [
        'id_maquina' => $maquina->getKey(),
        'id_usuario' => $admin->getKey(),
        'fecha_mantenimiento' => '2026-11-13 12:00:00',
        'minutos_uso_acumulados' => 210,
    ]);
    $this->assertDatabaseCount('registros_ats_epp', 1);
    $this->get(route('mantenimientos.show', $maquina))
        ->assertOk()
        ->assertSee('Historial de mantenimiento')
        ->assertSee('Limpieza y revisión preventiva.')
        ->assertViewHas('resumen', fn (array $resumen): bool => $resumen['minutos'] === 60 && ! $resumen['pendiente']);
});

it('rechaza fechas futuras o que no sean posteriores al último mantenimiento', function (string $fecha): void {
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $maquina = Maquina::factory()->create();
    Mantenimiento::factory()->for($maquina, 'maquina')->create(['fecha_mantenimiento' => '2026-11-13 10:00:00']);

    $this->actingAs($supervisor)->post(route('mantenimientos.store', $maquina), ['fecha_mantenimiento' => $fecha])
        ->assertSessionHasErrors('fecha_mantenimiento');

    $this->assertDatabaseCount('mantenimientos', 1);
})->with(['2026-11-13T10:00', '2026-11-13T09:59', '2026-11-13T20:01']);

it('evita cerrar un ciclo si hay un ATS abierto anterior al mantenimiento', function (): void {
    $supervisor = User::factory()->create()->assignRole('supervisor');
    $maquina = Maquina::factory()->create();
    RegistroAtsEpp::factory()->for($maquina, 'maquina')->create(['fecha_inicio' => '2026-11-13 08:00:00']);

    $this->actingAs($supervisor)->post(route('mantenimientos.store', $maquina), ['fecha_mantenimiento' => '2026-11-13T12:00'])
        ->assertSessionHasErrors('fecha_mantenimiento');

    $this->assertDatabaseCount('mantenimientos', 0);
});

it('reserva la consulta y el registro de mantenimientos para los roles autorizados', function (): void {
    $operador = User::factory()->create()->assignRole('operador');
    $maquina = Maquina::factory()->create();

    $this->actingAs($operador)->get(route('mantenimientos.index'))->assertForbidden();
    $this->get(route('mantenimientos.show', $maquina))->assertForbidden();
    $this->post(route('mantenimientos.store', $maquina), ['fecha_mantenimiento' => '2026-11-13T12:00'])->assertForbidden();
    $this->assertDatabaseCount('mantenimientos', 0);
});

it('permite configurar y retirar el intervalo de una máquina y rechaza un límite de cero', function (): void {
    $admin = User::factory()->create()->assignRole('administrador');
    $maquina = Maquina::factory()->create();
    $ruta = route('maquinas.update', $maquina);
    $datos = ['nombre' => $maquina->nombre, 'estado' => true];

    $this->actingAs($admin)->put($ruta, $datos + ['intervalo_mantenimiento_horas' => 100])->assertSessionHasNoErrors();
    expect($maquina->fresh()->intervalo_mantenimiento_horas)->toBe(100);

    $this->put($ruta, $datos + ['intervalo_mantenimiento_horas' => 0])->assertSessionHasErrors('intervalo_mantenimiento_horas');
    expect($maquina->fresh()->intervalo_mantenimiento_horas)->toBe(100);

    $this->put($ruta, $datos + ['intervalo_mantenimiento_horas' => ''])->assertSessionHasNoErrors();
    $this->get(route('mantenimientos.index'))->assertOk()->assertSee('Sin intervalo');
    expect($maquina->fresh()->intervalo_mantenimiento_horas)->toBeNull();
});
