<?php

namespace App\Services;

use App\Models\RegistroAtsEpp;
use App\Models\User;
use App\Models\ValidacionEpp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ValidacionEppService
{
    public function __construct(private CapturaEppService $capturas) {}

    public function ultima(User $operador): ?ValidacionEpp
    {
        return ValidacionEpp::query()->where('id_operador', $operador->getKey())->latest('id_validacion_epp')->first();
    }

    public function enviar(User $usuario, int $idMaquina, ?array $captura, string $token): ValidacionEpp
    {
        $ruta = null;

        try {
            return DB::transaction(function () use ($usuario, $idMaquina, $captura, $token, &$ruta): ValidacionEpp {
                $operador = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                abort_unless($operador->estado && $operador->can('registros.crear'), 403);
                if (RegistroAtsEpp::query()->where('id_operador', $operador->getKey())->where('estado', 'En curso')->exists()) {
                    throw ValidationException::withMessages(['captura_epp' => 'Finaliza tu ATS en curso antes de enviar otra foto.']);
                }

                $maquina = $operador->maquinas()->whereKey($idMaquina)->where('estado', true)->lockForUpdate()->first();
                if (! $maquina) {
                    throw ValidationException::withMessages(['id_maquina' => 'Selecciona una máquina activa para la que tengas autorización.']);
                }

                $ultima = $this->ultima($operador);
                if ($ultima && in_array($ultima->estadoActual(), [ValidacionEpp::PENDIENTE, ValidacionEpp::APROBADA], true)
                    && $operador->maquinas()->whereKey($ultima->id_maquina)->where('estado', true)->exists()) {
                    throw ValidationException::withMessages(['captura_epp' => 'Ya tienes una validación vigente. Revisa su estado antes de enviar otra.']);
                }

                $foto = $this->capturas->validar($operador, $captura, $token);
                if (ValidacionEpp::query()->where('token_captura', $token)->exists()) {
                    throw ValidationException::withMessages(['captura_epp' => 'Esta foto ya fue enviada. Toma otra fotografía.']);
                }
                $ruta = 'registros/epp/'.basename($foto['ruta']);
                if (! Storage::disk('local')->copy($foto['ruta'], $ruta)) {
                    throw ValidationException::withMessages(['captura_epp' => 'No se pudo guardar la foto para revisión. Inténtalo nuevamente.']);
                }
                $fechaFoto = Carbon::parse($foto['fecha']);

                return ValidacionEpp::query()->create([
                    'id_operador' => $operador->getKey(),
                    'id_maquina' => $maquina->getKey(),
                    'token_captura' => $token,
                    'fotografia' => $ruta,
                    'fecha_fotografia' => $fechaFoto,
                    'estado' => ValidacionEpp::PENDIENTE,
                    'vence_en' => $fechaFoto->copy()->addMinutes(config('epp.minutos_revision')),
                ]);
            });
        } catch (Throwable $excepcion) {
            if ($ruta !== null) {
                Storage::disk('local')->delete($ruta);
            }
            throw $excepcion;
        }
    }

    public function resolver(ValidacionEpp $validacion, User $supervisor, string $decision, ?string $motivo): void
    {
        DB::transaction(function () use ($validacion, $supervisor, $decision, $motivo): void {
            $operador = User::query()->lockForUpdate()->findOrFail($validacion->id_operador);
            $validacion = ValidacionEpp::query()->lockForUpdate()->findOrFail($validacion->getKey());
            abort_unless($supervisor->estado && $supervisor->can('epp.validar') && $supervisor->getKey() !== $operador->getKey(), 403);

            if ($validacion->estadoActual() !== ValidacionEpp::PENDIENTE || $this->ultima($operador)?->getKey() !== $validacion->getKey()) {
                throw ValidationException::withMessages(['decision' => 'Esta solicitud ya fue revisada, reemplazada o venció.']);
            }

            $aprobar = $decision === 'aprobar';
            if ($aprobar && (! $operador->estado || ! $operador->maquinas()->whereKey($validacion->id_maquina)->where('estado', true)->exists()
                || ! Storage::disk('local')->exists($validacion->fotografia))) {
                throw ValidationException::withMessages(['decision' => 'La foto o la autorización de la máquina ya no está disponible.']);
            }

            $ahora = now();
            $validacion->update([
                'estado' => $aprobar ? ValidacionEpp::APROBADA : ValidacionEpp::RECHAZADA,
                'id_supervisor' => $supervisor->getKey(),
                'fecha_revision' => $ahora,
                'motivo_rechazo' => $aprobar ? null : $motivo,
                'vence_en' => $aprobar ? $ahora->copy()->addMinutes(config('epp.minutos_aprobacion')) : $validacion->vence_en,
            ]);
        });
    }

    public function exigirAprobada(User $operador, int $idMaquina, int $idValidacion): ValidacionEpp
    {
        $validacion = ValidacionEpp::query()->where('id_operador', $operador->getKey())
            ->latest('id_validacion_epp')->lockForUpdate()->first();
        if (! $validacion || $validacion->getKey() !== $idValidacion || (int) $validacion->id_maquina !== $idMaquina
            || $validacion->estadoActual() !== ValidacionEpp::APROBADA
            || ! Storage::disk('local')->exists($validacion->fotografia)) {
            throw ValidationException::withMessages(['id_validacion_epp' => 'Necesitas una aprobación de EPP vigente y sin usar para esta máquina.']);
        }

        return $validacion;
    }
}
