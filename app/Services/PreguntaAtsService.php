<?php

namespace App\Services;

use App\Models\PreguntaAts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreguntaAtsService
{
    public function listar(): LengthAwarePaginator
    {
        return PreguntaAts::query()->orderBy('etapa')->orderBy('posicion')->orderBy('id_pregunta')->paginate(20);
    }

    /** @param array{pregunta: string, etapa: string, posicion: int, estado: bool} $datos */
    public function guardar(PreguntaAts $pregunta, array $datos): void
    {
        DB::transaction(function () use ($pregunta, $datos): void {
            if ($pregunta->exists) {
                $pregunta = PreguntaAts::query()->lockForUpdate()->findOrFail($pregunta->getKey());
            }
            $pregunta->fill($datos);
            if ($pregunta->exists && $pregunta->isDirty(['pregunta', 'etapa']) && $pregunta->respuestas()->exists()) {
                throw ValidationException::withMessages(['pregunta' => 'Esta pregunta ya tiene respuestas. Conserva su texto y etapa; puedes desactivarla y crear otra.']);
            }
            $pregunta->save();
        });
    }
}
