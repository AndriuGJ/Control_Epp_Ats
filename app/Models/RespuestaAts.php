<?php

namespace App\Models;

use Database\Factories\RespuestaAtsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_registro', 'id_pregunta', 'respuesta'])]
class RespuestaAts extends Model
{
    /** @use HasFactory<RespuestaAtsFactory> */
    use HasFactory;

    protected $table = 'respuestas_ats';

    protected $primaryKey = 'id_respuesta';

    protected function casts(): array
    {
        return [];
    }

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroAtsEpp::class, 'id_registro');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(PreguntaAts::class, 'id_pregunta');
    }
}
