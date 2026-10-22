<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CrearAdministrador extends Command
{
    protected $signature = 'app:crear-administrador';

    protected $description = 'Crea la primera cuenta administradora sin contraseñas predeterminadas.';

    public function handle(): int
    {
        $datos = [
            'nombres' => $this->ask('Nombres'), 'apellidos' => $this->ask('Apellidos'),
            'dni' => $this->ask('DNI (8 dígitos)'), 'correo' => mb_strtolower(trim((string) $this->ask('Correo electrónico'))),
            'contrasena' => $this->secret('Contraseña (mínimo 12 caracteres, letras y números)'),
            'contrasena_confirmation' => $this->secret('Confirma la contraseña'),
        ];
        $validacion = Validator::make($datos, [
            'nombres' => ['required', 'string', 'max:100'], 'apellidos' => ['required', 'string', 'max:100'],
            'dni' => ['required', 'digits:8', 'unique:usuarios,dni'], 'correo' => ['required', 'email', 'max:255', 'unique:usuarios,correo'],
            'contrasena' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
        if ($validacion->fails()) {
            foreach ($validacion->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $this->call('db:seed', ['--class' => RolesPermisosSeeder::class, '--force' => true]);
        unset($datos['contrasena_confirmation']);
        $usuario = User::create([...$datos, 'estado' => true]);
        $usuario->assignRole('administrador');
        $this->info('Cuenta administradora creada. Ya puedes ingresar con tu correo y contraseña.');

        return self::SUCCESS;
    }
}
