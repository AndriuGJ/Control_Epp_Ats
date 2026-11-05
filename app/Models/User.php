<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['nombres', 'apellidos', 'dni', 'correo', 'contrasena', 'estado'])]
#[Hidden(['contrasena', 'token_recuerdo'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $authPasswordName = 'contrasena';

    protected $rememberTokenName = 'token_recuerdo';

    protected $attributes = ['estado' => true];

    protected function casts(): array
    {
        return ['estado' => 'boolean', 'correo_verificado_en' => 'datetime', 'contrasena' => 'hashed'];
    }

    public function maquinas(): BelongsToMany
    {
        return $this->belongsToMany(Maquina::class, 'usuarios_maquinas', 'id_usuario', 'id_maquina')->withTimestamps();
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroAtsEpp::class, 'id_operador');
    }

    public function revisiones(): HasMany
    {
        return $this->hasMany(RegistroAtsEpp::class, 'id_revisor');
    }
}
