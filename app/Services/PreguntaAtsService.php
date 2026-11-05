<?php

namespace App\Services;

use App\Models\PreguntaAts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreguntaAtsService
{
    public function listar(): LengthAwarePaginator
    {
        return PreguntaAts::query()->orderBy('etapa')->orderBy('posicion')->orderBy('id_pregunta')->paginate(10);
    }

    /** @param array{pregunta: string, etapa: string, estado: bool} $datos */
    public function guardar(PreguntaAts $pregunta, array $datos): void
    {
        try {
            DB::transaction(function () use ($pregunta, $datos): void {
                $preguntas = PreguntaAts::query()->orderBy('id_pregunta')->lockForUpdate()->get();
                if ($pregunta->exists) {
                    $pregunta = $preguntas->find($pregunta->getKey()) ?? PreguntaAts::query()->findOrFail($pregunta->getKey());
                }
                $etapaAnterior = $pregunta->etapa;
                $siguientePosicion = (int) $preguntas->where('etapa', $datos['etapa'])->max('posicion') + 1;
                $pregunta->fill($datos);
                if ($pregunta->exists && $pregunta->isDirty(['pregunta', 'etapa']) && $pregunta->respuestas()->exists()) {
                    throw ValidationException::withMessages(['pregunta' => __('validation.preguntas.respondida')]);
                }
                if (! $pregunta->exists || $etapaAnterior !== $datos['etapa']) {
                    $pregunta->posicion = $siguientePosicion;
                }
                $pregunta->save();
            }, attempts: 5);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['pregunta' => __('validation.preguntas.cambio_concurrente')]);
        }
    }

    /** @return array{preguntas: Collection<int, PreguntaAts>, versionOrden: string} */
    public function paraOrdenar(string $etapa): array
    {
        $preguntas = PreguntaAts::query()->where('etapa', $etapa)->orderBy('posicion')->orderBy('id_pregunta')->get();

        return ['preguntas' => $preguntas, 'versionOrden' => $this->versionOrden($preguntas)];
    }

    /** @param list<int|string> $orden */
    public function guardarOrden(string $etapa, array $orden, string $versionOrden): void
    {
        try {
            DB::transaction(function () use ($etapa, $orden, $versionOrden): void {
                $preguntas = PreguntaAts::query()->orderBy('id_pregunta')->lockForUpdate()->get()
                    ->where('etapa', $etapa)->sortBy('posicion')->values();
                $identificadores = array_map('intval', $orden);
                $esperados = $preguntas->modelKeys();
                $recibidos = $identificadores;
                sort($esperados);
                sort($recibidos);

                if ($esperados !== $recibidos || ! hash_equals($this->versionOrden($preguntas), $versionOrden)) {
                    throw ValidationException::withMessages(['orden' => __('validation.preguntas.orden_desactualizado')]);
                }

                // Liberar las posiciones evita colisiones con el índice único de etapa y posición.
                $temporal = (int) $preguntas->max('posicion');
                foreach ($preguntas as $pregunta) {
                    $pregunta->update(['posicion' => ++$temporal]);
                }

                $porIdentificador = $preguntas->keyBy('id_pregunta');
                foreach ($identificadores as $indice => $identificador) {
                    $porIdentificador[$identificador]->update(['posicion' => $indice + 1]);
                }
            }, attempts: 5);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['orden' => __('validation.preguntas.orden_desactualizado')]);
        }
    }

    /** @param Collection<int, PreguntaAts> $preguntas */
    private function versionOrden(Collection $preguntas): string
    {
        return hash('sha256', json_encode($preguntas->map(fn (PreguntaAts $pregunta): array => [
            $pregunta->getKey(), $pregunta->pregunta, $pregunta->etapa, $pregunta->posicion, $pregunta->estado,
        ])->values()->all(), JSON_THROW_ON_ERROR));
    }
}
