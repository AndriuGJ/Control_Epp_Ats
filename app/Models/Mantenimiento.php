<?php

namespace App\Models;

use Database\Factories\MantenimientoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_maquina', 'id_usuario', 'fecha_mantenimiento', 'minutos_uso_acumulados', 'observaciones'])]
class Mantenimiento extends Model
{
    /** @use HasFactory<MantenimientoFactory> */
    use HasFactory;

    protected $table = 'mantenimientos';

    protected $primaryKey = 'id_mantenimiento';

    protected function casts(): array
    {
        return [
            'fecha_mantenimiento' => 'immutable_datetime',
            'minutos_uso_acumulados' => 'integer',
        ];
    }

    public function maquina(): BelongsTo
    {
        return $this->belongsTo(Maquina::class, 'id_maquina');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
