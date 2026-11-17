<?php

namespace App\Services;

use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as ColeccionModelos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MantenimientoService
{
    /**
     * @param  ColeccionModelos<int, Maquina>  $maquinas
     * @return array<int, array{minutos: int, limite: ?int, restantes: ?int, pendiente: bool, ultimo: ?CarbonImmutable}>
     */
    public function resumen(ColeccionModelos $maquinas): array
    {
        $ultimos = Mantenimiento::query()->whereIn('id_maquina', $maquinas->modelKeys())
            ->groupBy('id_maquina')
            ->selectRaw('id_maquina, MAX(fecha_mantenimiento) AS ultima_fecha')
            ->pluck('ultima_fecha', 'id_maquina')
            ->map(fn (string $fecha): CarbonImmutable => CarbonImmutable::parse($fecha));

        $minutos = $this->calcularMinutos($maquinas->modelKeys(), $ultimos, CarbonImmutable::now());

        return $maquinas->mapWithKeys(function (Maquina $maquina) use ($ultimos, $minutos): array {
            $id = $maquina->getKey();
            $uso = $minutos[$id] ?? 0;
            $limite = $maquina->intervalo_mantenimiento_horas === null ? null : $maquina->intervalo_mantenimiento_horas * 60;

            return [$id => [
                'minutos' => $uso,
                'limite' => $limite,
                'restantes' => $limite === null ? null : max(0, $limite - $uso),
                'pendiente' => $limite !== null && $uso >= $limite,
                'ultimo' => $ultimos->get($id),
            ]];
        })->all();
    }

    public function registrar(Maquina $maquina, User $usuario, CarbonImmutable $fecha, ?string $observaciones): Mantenimiento
    {
        return DB::transaction(function () use ($maquina, $usuario, $fecha, $observaciones): Mantenimiento {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquina->getKey());
            $ultimo = $maquina->mantenimientos()->latest('fecha_mantenimiento')->first();
            if ($ultimo && $fecha->lessThanOrEqualTo($ultimo->fecha_mantenimiento)) {
                throw ValidationException::withMessages([
                    'fecha_mantenimiento' => 'La fecha debe ser posterior al último mantenimiento registrado.',
                ]);
            }

            if ($maquina->registros()->where('estado', 'En curso')->where('fecha_inicio', '<', $fecha)->exists()) {
                throw ValidationException::withMessages([
                    'fecha_mantenimiento' => 'Finaliza los ATS en curso de esta máquina que empezaron antes del mantenimiento para cerrar el conteo.',
                ]);
            }

            $id = $maquina->getKey();
            $desde = $ultimo ? collect([$id => $ultimo->fecha_mantenimiento]) : collect();
            $minutos = $this->calcularMinutos([$id], $desde, $fecha);

            return $maquina->mantenimientos()->create([
                'id_usuario' => $usuario->getKey(),
                'fecha_mantenimiento' => $fecha,
                'minutos_uso_acumulados' => $minutos[$id] ?? 0,
                'observaciones' => $observaciones,
            ]);
        });
    }

    /**
     * @param  list<int>  $ids
     * @param  Collection<int, CarbonImmutable>  $desde
     * @return array<int, int>
     */
    private function calcularMinutos(array $ids, Collection $desde, CarbonImmutable $hasta): array
    {
        $registros = RegistroAtsEpp::query()
            ->select('id_maquina', 'fecha_inicio', 'fecha_finalizacion')
            ->whereIn('id_maquina', $ids)
            ->where('estado', 'Finalizado')
            ->whereNotNull('fecha_finalizacion')
            ->where('fecha_inicio', '<', $hasta)
            ->when($ids !== [] && $desde->count() === count($ids),
                fn ($consulta) => $consulta->where('fecha_finalizacion', '>', $desde->min()))
            ->orderBy('id_maquina')
            ->orderBy('fecha_inicio')
            ->cursor();

        $segundos = [];
        $finContado = [];
        foreach ($registros as $registro) {
            $id = $registro->id_maquina;
            $inicio = max(
                $registro->fecha_inicio->getTimestamp(),
                $desde->get($id)?->getTimestamp() ?? PHP_INT_MIN,
                $finContado[$id] ?? PHP_INT_MIN,
            );
            $fin = min($registro->fecha_finalizacion->getTimestamp(), $hasta->getTimestamp());
            if ($fin > $inicio) {
                $segundos[$id] = ($segundos[$id] ?? 0) + $fin - $inicio;
                $finContado[$id] = $fin;
            }
        }

        return array_map(fn (int $total): int => intdiv($total, 60), $segundos);
    }
}
