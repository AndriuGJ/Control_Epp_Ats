<?php

namespace App\Services;

use App\Models\Maquina;
use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistroAtsService
{
    public function __construct(private CapturaEppService $capturas) {}

    /** @param array{fecha_desde?: ?string, fecha_hasta?: ?string, trabajador?: ?string, id_maquina?: int|string|null, estado?: ?string, revision?: ?string} $filtros */
    public function listar(User $usuario, array $filtros = []): LengthAwarePaginator
    {
        $consulta = $this->visiblesPara($usuario);
        if (! empty($filtros['fecha_desde'])) {
            $consulta->where('fecha_inicio', '>=', $filtros['fecha_desde'].' 00:00:00');
        }
        if (! empty($filtros['fecha_hasta'])) {
            $consulta->where('fecha_inicio', '<=', $filtros['fecha_hasta'].' 23:59:59');
        }
        if (trim($filtros['trabajador'] ?? '') !== '') {
            $terminos = preg_split('/\s+/u', trim($filtros['trabajador']), -1, PREG_SPLIT_NO_EMPTY);
            $consulta->whereHas('operador', function (Builder $operadores) use ($terminos): void {
                foreach ($terminos as $termino) {
                    $operadores->where(function (Builder $coincidencias) use ($termino): void {
                        $coincidencias->where('nombres', 'like', '%'.$termino.'%')
                            ->orWhere('apellidos', 'like', '%'.$termino.'%')
                            ->orWhere('dni', 'like', '%'.$termino.'%');
                    });
                }
            });
        }
        if (! empty($filtros['id_maquina'])) {
            $consulta->where('id_maquina', $filtros['id_maquina']);
        }
        if (! empty($filtros['estado'])) {
            $consulta->where('estado', $filtros['estado']);
        }
        if (! empty($filtros['revision'])) {
            $consulta->where('estado', 'Finalizado');
            if ($filtros['revision'] === 'Revisado') {
                $consulta->whereNotNull('fecha_revision');
            } else {
                $consulta->whereNull('fecha_revision');
            }
        }

        return $consulta->with(['operador', 'maquina'])->orderByDesc('fecha_inicio')->orderByDesc('id_registro')->paginate(15)->appends($filtros);
    }

    /** @return Collection<int, Maquina> */
    public function maquinasParaConsulta(User $usuario): Collection
    {
        return Maquina::query()->whereIn('id_maquina', $this->visiblesPara($usuario)->select('id_maquina'))
            ->orderBy('nombre')->orderBy('id_maquina')->get(['id_maquina', 'nombre', 'estado']);
    }

    public function obtener(User $usuario, int $id): RegistroAtsEpp
    {
        return $this->visiblesPara($usuario)->with(['operador', 'maquina', 'revisor', 'respuestas' => fn (HasMany $respuestas) => $respuestas->with('pregunta')->orderBy('id_respuesta')])->findOrFail($id);
    }

    public function enCurso(User $usuario): ?RegistroAtsEpp
    {
        return $usuario->registros()->where('estado', 'En curso')->orderBy('id_registro')->first();
    }

    public function prepararInicio(User $usuario): array
    {
        $preguntas = $this->preguntasEtapa('Inicial');

        return [
            'maquinas' => $usuario->maquinas()->where('estado', true)->orderBy('nombre')->orderBy('maquinas.id_maquina')->get(),
            'preguntas' => $preguntas,
            'versionPreguntas' => $this->versionPreguntas($preguntas),
        ];
    }

    /** @param array{id_maquina: int, captura_epp: string, firma_inicial: string, respuestas: array<int, string>, version_preguntas: string} $datos */
    public function iniciar(User $usuario, array $datos, ?array $captura = null): RegistroAtsEpp
    {
        $firma = $this->decodificarFirma($datos['firma_inicial']);
        $archivos = [];

        try {
            return DB::transaction(function () use ($usuario, $datos, $firma, $captura, &$archivos): RegistroAtsEpp {
                $operador = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                abort_unless($operador->estado && $operador->can('registros.crear'), 403);
                if ($this->enCurso($operador)) {
                    throw ValidationException::withMessages(['registro' => 'Ya tienes un registro en curso. Debes finalizarlo antes de iniciar otro.']);
                }
                $maquina = $operador->maquinas()->whereKey($datos['id_maquina'])->where('estado', true)->lockForUpdate()->first();
                if (! $maquina) {
                    throw ValidationException::withMessages(['id_maquina' => 'Selecciona una máquina activa para la que tengas autorización.']);
                }
                $preguntas = $this->validarRespuestas('Inicial', $datos);
                $fotoCapturada = $this->capturas->validar($operador, $captura, $datos['captura_epp']);
                $fotografia = 'registros/epp/'.basename($fotoCapturada['ruta']);
                $archivos[] = $fotografia;
                if (! Storage::disk('local')->copy($fotoCapturada['ruta'], $fotografia)) {
                    throw ValidationException::withMessages(['captura_epp' => 'No se pudo guardar la captura. Vuelve a intentarlo.']);
                }
                $rutaFirma = 'registros/firmas/'.Str::uuid().'.png';
                $archivos[] = $rutaFirma;
                if (! Storage::disk('local')->put($rutaFirma, $firma)) {
                    throw ValidationException::withMessages(['firma_inicial' => 'No se pudo guardar la firma. Inténtalo nuevamente.']);
                }
                $ahora = now();
                $registro = RegistroAtsEpp::create([
                    'id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey(),
                    'fotografia_epp' => $fotografia, 'fecha_fotografia' => Carbon::parse($fotoCapturada['fecha']),
                    'firma_inicial' => $rutaFirma, 'fecha_inicio' => $ahora, 'estado' => 'En curso',
                ]);
                $registro->respuestas()->createMany($preguntas->map(fn (PreguntaAts $pregunta): array => [
                    'id_pregunta' => $pregunta->getKey(), 'respuesta' => trim($datos['respuestas'][$pregunta->getKey()]),
                ])->all());

                return $registro;
            });
        } catch (Throwable $excepcion) {
            if ($archivos !== []) {
                Storage::disk('local')->delete($archivos);
            }
            throw $excepcion;
        }
    }

    public function prepararFinalizacion(User $usuario, int $id): array
    {
        $registro = $this->propio($usuario, $id);
        $preguntas = $this->preguntasEtapa('Final');

        return ['registro' => $registro, 'preguntas' => $preguntas, 'versionPreguntas' => $this->versionPreguntas($preguntas)];
    }

    public function finalizar(User $usuario, int $id, array $datos): RegistroAtsEpp
    {
        $firma = $this->decodificarFirma($datos['firma_final'], 'firma_final');
        $rutaFirma = null;
        try {
            return DB::transaction(function () use ($usuario, $id, $datos, $firma, &$rutaFirma): RegistroAtsEpp {
                $operador = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                $registro = $this->propio($operador, $id, true);
                if ($registro->estado !== 'En curso') {
                    throw ValidationException::withMessages(['registro' => 'Este registro ya está finalizado y no se puede modificar.']);
                }
                $preguntas = $this->validarRespuestas('Final', $datos);
                $rutaFirma = 'registros/firmas/'.Str::uuid().'.png';
                if (! Storage::disk('local')->put($rutaFirma, $firma)) {
                    throw ValidationException::withMessages(['firma_final' => 'No se pudo guardar la firma final. Inténtalo nuevamente.']);
                }
                $registro->respuestas()->createMany($preguntas->map(fn (PreguntaAts $pregunta): array => [
                    'id_pregunta' => $pregunta->getKey(), 'respuesta' => $datos['respuestas'][$pregunta->getKey()],
                ])->all());
                $registro->update(['estado' => 'Finalizado', 'fecha_finalizacion' => now(), 'firma_final' => $rutaFirma]);

                return $registro;
            });
        } catch (Throwable $excepcion) {
            if ($rutaFirma !== null) {
                Storage::disk('local')->delete($rutaFirma);
            }
            throw $excepcion;
        }
    }

    /** @param array{firma_revisor: string} $datos */
    public function revisar(User $usuario, int $id, array $datos): RegistroAtsEpp
    {
        abort_unless($usuario->estado && $usuario->can('registros.revisar'), 403);
        $firma = $this->decodificarFirma($datos['firma_revisor'], 'firma_revisor');
        $rutaFirma = null;

        try {
            return DB::transaction(function () use ($usuario, $id, $firma, &$rutaFirma): RegistroAtsEpp {
                $revisor = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                abort_unless($revisor->estado && $revisor->can('registros.revisar'), 403);
                $registro = $this->visiblesPara($revisor)->lockForUpdate()->findOrFail($id);
                if ($registro->id_operador === $revisor->getKey()) {
                    throw ValidationException::withMessages(['registro' => 'Otro supervisor debe revisar tu registro.']);
                }
                if ($registro->estado !== 'Finalizado' || ! $registro->firma_final || ! $registro->fecha_finalizacion) {
                    throw ValidationException::withMessages(['registro' => 'Solo se pueden revisar registros con la labor finalizada.']);
                }
                if ($registro->id_revisor !== null || $registro->fecha_revision !== null || $registro->firma_revisor !== null) {
                    throw ValidationException::withMessages(['registro' => 'Este registro ya fue revisado y su revisión no se puede modificar.']);
                }
                $rutaFirma = 'registros/firmas/'.Str::uuid().'.png';
                if (! Storage::disk('local')->put($rutaFirma, $firma)) {
                    throw ValidationException::withMessages(['firma_revisor' => 'No se pudo guardar la firma del supervisor. Inténtalo nuevamente.']);
                }
                $registro->update([
                    'id_revisor' => $revisor->getKey(),
                    'firma_revisor' => $rutaFirma,
                    'fecha_revision' => now(),
                ]);

                return $registro;
            });
        } catch (Throwable $excepcion) {
            if ($rutaFirma !== null) {
                Storage::disk('local')->delete($rutaFirma);
            }
            throw $excepcion;
        }
    }

    private function propio(User $usuario, int $id, bool $bloquear = false): RegistroAtsEpp
    {
        abort_unless($usuario->estado && $usuario->can('registros.crear'), 403);
        $consulta = $usuario->registros()->whereKey($id);
        if ($bloquear) {
            $consulta->lockForUpdate();
        }

        return $consulta->firstOrFail();
    }

    private function preguntasEtapa(string $etapa, bool $bloquear = false): Collection
    {
        $consulta = PreguntaAts::query()->where('estado', true)->where('etapa', $etapa)->orderBy('posicion')->orderBy('id_pregunta');
        if ($bloquear) {
            $consulta->lockForUpdate();
        }

        return $consulta->get();
    }

    private function validarRespuestas(string $etapa, array $datos): Collection
    {
        $preguntas = $this->preguntasEtapa($etapa, true);
        if ($preguntas->isEmpty()) {
            throw ValidationException::withMessages(['respuestas' => 'El administrador debe registrar las preguntas de esta etapa antes de continuar.']);
        }
        if (! hash_equals($this->versionPreguntas($preguntas), $datos['version_preguntas'])) {
            throw ValidationException::withMessages(['respuestas' => 'Las preguntas cambiaron. Recarga el formulario y responde la versión actual.']);
        }
        $esperadas = array_map('strval', $preguntas->modelKeys());
        $recibidas = array_map('strval', array_keys($datos['respuestas']));
        sort($esperadas);
        sort($recibidas);
        if ($esperadas !== $recibidas || array_filter($datos['respuestas'], fn (mixed $respuesta): bool => ! in_array($respuesta, ['Sí', 'No'], true))) {
            throw ValidationException::withMessages(['respuestas' => 'Marca Sí o No en todas las preguntas de esta etapa.']);
        }

        return $preguntas;
    }

    private function visiblesPara(User $usuario): Builder
    {
        abort_unless($usuario->estado && $usuario->can('registros.consultar'), 403);
        $consulta = RegistroAtsEpp::query();
        if (! $usuario->can('registros.revisar')) {
            $consulta->where('id_operador', $usuario->getKey());
        }

        return $consulta;
    }

    /** @param Collection<int, PreguntaAts> $preguntas */
    private function versionPreguntas(Collection $preguntas): string
    {
        return hash('sha256', json_encode($preguntas->map(fn (PreguntaAts $pregunta): array => [
            $pregunta->getKey(), $pregunta->pregunta, $pregunta->etapa, $pregunta->posicion,
        ])->all(), JSON_THROW_ON_ERROR));
    }

    private function decodificarFirma(string $valor, string $campo = 'firma_inicial'): string
    {
        $prefijo = 'data:image/png;base64,';
        $contenido = str_starts_with($valor, $prefijo) ? base64_decode(substr($valor, strlen($prefijo)), true) : false;
        $imagen = $contenido !== false ? @getimagesizefromstring($contenido) : false;
        if ($imagen === false || $imagen['mime'] !== 'image/png' || $imagen[0] < 100 || $imagen[1] < 40 || $imagen[0] > 2000 || $imagen[1] > 1000) {
            throw ValidationException::withMessages([$campo => 'Dibuja una firma válida antes de guardar el registro.']);
        }

        return $contenido;
    }
}
