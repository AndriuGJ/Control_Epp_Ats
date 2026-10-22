<?php

namespace App\Models;

use Database\Factories\RegistroAtsEppFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id_operador', 'id_maquina', 'id_revisor', 'fotografia_epp', 'fecha_fotografia', 'firma_inicial', 'fecha_inicio', 'firma_final', 'fecha_finalizacion', 'estado', 'firma_revisor', 'fecha_revision'])]
class RegistroAtsEpp extends Model
{
    /** @use HasFactory<RegistroAtsEppFactory> */
    use HasFactory;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'registros_ats_epp';

    protected $primaryKey = 'id_registro';

    protected function casts(): array
    {
        return ['fecha_fotografia' => 'datetime', 'fecha_inicio' => 'datetime', 'fecha_finalizacion' => 'datetime', 'fecha_revision' => 'datetime'];
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_operador');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_revisor');
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'id_maquina');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaAts::class, 'id_registro');
    }
}
