<?php

namespace App\Models;

use Database\Factories\ValidacionEppFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_operador', 'id_maquina', 'id_supervisor', 'token_captura', 'fotografia', 'fecha_fotografia', 'estado', 'fecha_revision', 'vence_en', 'motivo_rechazo', 'usada_en'])]
class ValidacionEpp extends Model
{
    /** @use HasFactory<ValidacionEppFactory> */
    use HasFactory;

    public const PENDIENTE = 'Pendiente';

    public const APROBADA = 'Aprobada';

    public const RECHAZADA = 'Rechazada';

    protected $table = 'validaciones_epp';

    protected $primaryKey = 'id_validacion_epp';

    protected function casts(): array
    {
        return [
            'fecha_fotografia' => 'datetime',
            'fecha_revision' => 'datetime',
            'vence_en' => 'datetime',
            'usada_en' => 'datetime',
        ];
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_operador');
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'id_maquina');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_supervisor');
    }

    public function estadoActual(): string
    {
        if ($this->usada_en) {
            return 'Utilizada';
        }

        if ($this->estado !== self::RECHAZADA && $this->vence_en->lte(now())) {
            return 'Vencida';
        }

        return $this->estado;
    }
}
