<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministradorSeeder extends Seeder
{

    public function run(): void
    {
        // Llamara a roles y permisos
        $this->call(RolesPermisosSeeder::class);


        $usuario = User::firstOrCreate(
            ['correo' => 'admincontrol@gmail.com'],
            [
                'nombres' => 'Admin',
                'apellidos' => 'Sistema',
                'dni' => '12345678',
                'contrasena' => Hash::make('admin123++--++--++'),
                'estado' => true,
            ]
        );

        //Asignamos rol
        if (!$usuario->hasRole('administrador')) {
            $usuario->assignRole('administrador');
        }
    }
}
