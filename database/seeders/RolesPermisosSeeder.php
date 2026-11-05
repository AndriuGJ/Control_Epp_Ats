<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permisos = ['usuarios.gestionar', 'maquinas.gestionar', 'autorizaciones.gestionar', 'preguntas.gestionar', 'registros.crear', 'registros.consultar', 'registros.revisar', 'reportes.generar'];
        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }
        Role::findOrCreate('administrador', 'web')->syncPermissions($permisos);
        Role::findOrCreate('supervisor', 'web')->syncPermissions(['registros.consultar', 'registros.revisar', 'reportes.generar']);
        Role::findOrCreate('operador', 'web')->syncPermissions(['registros.crear', 'registros.consultar']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
