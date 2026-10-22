<?php

namespace App\Services;

use App\Models\PreguntaAts;
use App\Models\RegistroAtsEpp;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistroAtsService
{
    public function listar(User $usuario): LengthAwarePaginator
    {
        return $this->visiblesPara($usuario)->with(['operador', 'maquina'])->orderByDesc('fecha_inicio')->orderByDesc('id_registro')->paginate(15);
    }

    public function obtener(User $usuario, int $id): RegistroAtsEpp
    {
        return $this->visiblesPara($usuario)->with(['operador', 'maquina', 'respuestas' => fn (HasMany $respuestas) => $respuestas->with('pregunta')->orderBy('id_respuesta')])->findOrFail($id);
    }

    public function enCurso(User $usuario): ?RegistroAtsEpp
    {
        return $usuario->registros()->where('estado', 'En curso')->orderBy('id_registro')->first();
    }

    public function prepararInicio(User $usuario): array
    {
        $preguntas = PreguntaAts::query()->where('estado', true)->where('etapa', 'Inicial')->orderBy('posicion')->orderBy('id_pregunta')->get();

        return [
            'maquinas' => $usuario->maquinas()->where('estado', true)->orderBy('nombre')->orderBy('maquinas.id_maquina')->get(),
            'preguntas' => $preguntas,
            'versionPreguntas' => $this->versionPreguntas($preguntas),
        ];
    }

    /** @param array{id_maquina: int, fotografia_epp: UploadedFile, firma_inicial: string, respuestas: array<int, string>, version_preguntas: string} $datos */
    public function iniciar(User $usuario, array $datos): RegistroAtsEpp
    {
        $firma = $this->decodificarFirma($datos['firma_inicial']);
        $archivos = [];

        try {
            return DB::transaction(function () use ($usuario, $datos, $firma, &$archivos): RegistroAtsEpp {
                $operador = User::query()->lockForUpdate()->findOrFail($usuario->getKey());
                abort_unless($operador->estado && $operador->can('registros.crear'), 403);
                if ($this->enCurso($operador)) {
                    throw ValidationException::withMessages(['registro' => 'Ya tienes un registro en curso. Debes finalizarlo antes de iniciar otro.']);
                }
                $maquina = $operador->maquinas()->whereKey($datos['id_maquina'])->where('estado', true)->lockForUpdate()->first();
                if (! $maquina) {
                    throw ValidationException::withMessages(['id_maquina' => 'Selecciona una máquina activa para la que tengas autorización.']);
                }
                $preguntas = PreguntaAts::query()->where('estado', true)->where('etapa', 'Inicial')->orderBy('posicion')->orderBy('id_pregunta')->lockForUpdate()->get();
                if ($preguntas->isEmpty()) {
                    throw ValidationException::withMessages(['respuestas' => 'El administrador debe registrar las preguntas iniciales antes de iniciar un ATS.']);
                }
                if (! hash_equals($this->versionPreguntas($preguntas), $datos['version_preguntas'])) {
                    throw ValidationException::withMessages(['respuestas' => 'Las preguntas cambiaron. Recarga el formulario y responde la versión actual.']);
                }
                $esperadas = array_map('strval', $preguntas->modelKeys());
                $recibidas = array_map('strval', array_keys($datos['respuestas']));
                sort($esperadas);
                sort($recibidas);
                if ($esperadas !== $recibidas) {
                    throw ValidationException::withMessages(['respuestas' => 'Responde todas las preguntas iniciales sin agregar preguntas de otra etapa.']);
                }

                $fotografia = $datos['fotografia_epp']->store('registros/epp', 'local');
                if ($fotografia === false) {
                    throw ValidationException::withMessages(['fotografia_epp' => 'No se pudo guardar la fotografía. Inténtalo nuevamente.']);
                }
                $archivos[] = $fotografia;
                $rutaFirma = 'registros/firmas/'.Str::uuid().'.png';
                $archivos[] = $rutaFirma;
                if (! Storage::disk('local')->put($rutaFirma, $firma)) {
                    throw ValidationException::withMessages(['firma_inicial' => 'No se pudo guardar la firma. Inténtalo nuevamente.']);
                }
                $ahora = now();
                $registro = RegistroAtsEpp::create([
                    'id_operador' => $operador->getKey(), 'id_maquina' => $maquina->getKey(),
                    'fotografia_epp' => $fotografia, 'fecha_fotografia' => $ahora,
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

    private function decodificarFirma(string $valor): string
    {
        $prefijo = 'data:image/png;base64,';
        $contenido = str_starts_with($valor, $prefijo) ? base64_decode(substr($valor, strlen($prefijo)), true) : false;
        $imagen = $contenido !== false ? @getimagesizefromstring($contenido) : false;
        if ($imagen === false || $imagen['mime'] !== 'image/png' || $imagen[0] < 100 || $imagen[1] < 40 || $imagen[0] > 2000 || $imagen[1] > 1000) {
            throw ValidationException::withMessages(['firma_inicial' => 'Dibuja una firma válida antes de guardar el registro.']);
        }

        return $contenido;
    }
}
