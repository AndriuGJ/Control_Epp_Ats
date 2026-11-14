<?php

namespace App\Models;

use Database\Factories\MaquinaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'fotografia', 'estado', 'intervalo_mantenimiento_horas'])]
class Maquina extends Model
{
    /** @use HasFactory<MaquinaFactory> */
    use HasFactory;

    protected $table = 'maquinas';

    protected $primaryKey = 'id_maquina';

    protected function casts(): array
    {
        return ['estado' => 'boolean', 'intervalo_mantenimiento_horas' => 'integer'];
    }

    public function operadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuarios_maquinas', 'id_maquina', 'id_usuario')->withTimestamps();
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'id_maquina');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroAtsEpp::class, 'id_maquina');
    }
}
