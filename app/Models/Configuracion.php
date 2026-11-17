<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre_empresa', 'area', 'logo', 'avisos_epp', 'servidor_smtp', 'puerto_smtp', 'usuario_smtp', 'contrasena_smtp'])]
#[Hidden(['contrasena_smtp'])]
class Configuracion extends Model
{
    protected $table = 'configuracion';

    protected function casts(): array
    {
        return ['avisos_epp' => 'boolean', 'puerto_smtp' => 'integer', 'contrasena_smtp' => 'encrypted'];
    }

    public function tieneContrasenaSmtp(): bool
    {
        return filled($this->getRawOriginal('contrasena_smtp'));
    }
}
