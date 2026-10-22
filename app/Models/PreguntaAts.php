<?php

namespace App\Models;

use Database\Factories\PreguntaAtsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['pregunta', 'etapa', 'posicion', 'estado'])]
class PreguntaAts extends Model
{
    /** @use HasFactory<PreguntaAtsFactory> */
    use HasFactory;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'preguntas_ats';

    protected $primaryKey = 'id_pregunta';

    protected function casts(): array
    {
        return ['estado' => 'boolean', 'posicion' => 'integer'];
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaAts::class, 'id_pregunta');
    }
}
