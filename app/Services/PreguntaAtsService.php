<?php

namespace App\Services;

use App\Models\PreguntaAts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreguntaAtsService
{
    /** @return array<string, int> */
    public function siguientesPosiciones(): array
    {
        $maximos = PreguntaAts::query()->selectRaw('etapa, MAX(posicion) as ultima')->groupBy('etapa')->pluck('ultima', 'etapa');

        return ['Inicial' => (int) ($maximos['Inicial'] ?? 0) + 1, 'Final' => (int) ($maximos['Final'] ?? 0) + 1];
    }

    public function listar(): LengthAwarePaginator
    {
        return PreguntaAts::query()->orderBy('etapa')->orderBy('posicion')->orderBy('id_pregunta')->paginate(10);
    }

    /** @param array{pregunta: string, etapa: string, posicion: int, estado: bool} $datos */
    public function guardar(PreguntaAts $pregunta, array $datos): void
    {
        try {
            DB::transaction(function () use ($pregunta, $datos): void {
                $preguntas = PreguntaAts::query()->orderBy('id_pregunta')->lockForUpdate()->get();
                if ($pregunta->exists) {
                    $pregunta = $preguntas->find($pregunta->getKey()) ?? PreguntaAts::query()->findOrFail($pregunta->getKey());
                }
                $etapaAnterior = $pregunta->etapa;
                $posicionAnterior = $pregunta->posicion;
                $pregunta->fill($datos);
                if ($pregunta->exists && $pregunta->isDirty(['pregunta', 'etapa']) && $pregunta->respuestas()->exists()) {
                    throw ValidationException::withMessages(['pregunta' => 'Esta pregunta ya tiene respuestas. Conserva su texto y etapa; puedes desactivarla y crear otra.']);
                }
                $ocupante = $preguntas->first(fn (PreguntaAts $otra): bool => $otra->getKey() !== $pregunta->getKey()
                    && $otra->etapa === $datos['etapa'] && $otra->posicion === (int) $datos['posicion']);
                if ($ocupante) {
                    if (! $pregunta->exists || $etapaAnterior !== $datos['etapa']) {
                        throw ValidationException::withMessages(['posicion' => 'Esta posición ya está ocupada en la etapa seleccionada. Elige una posición libre.']);
                    }
                    DB::table('preguntas_ats')->where('id_pregunta', $pregunta->getKey())->update(['posicion' => 0]);
                    $ocupante->update(['posicion' => $posicionAnterior]);
                }
                $pregunta->save();
            }, attempts: 5);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['posicion' => 'La posición acaba de ser ocupada. Recarga el formulario y vuelve a intentarlo.']);
        }
    }
}
