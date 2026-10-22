<?php

namespace App\Models;

use Database\Factories\MaquinaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'fotografia', 'estado'])]
class Maquina extends Model
{
    /** @use HasFactory<MaquinaFactory> */
    use HasFactory;

    protected $table = 'maquinas';

    protected $primaryKey = 'id_maquina';

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function operadores(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'usuarios_maquinas', 'id_maquina', 'id_usuario')->withTimestamps();
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroAtsEpp::class, 'id_maquina');
    }
}
