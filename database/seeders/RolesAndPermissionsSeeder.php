<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder para roles, permisos y usuario ADMIN.
 *
 * Usa:
 *  - Tabla roles:        id, nombre, slug, descripcion
 *  - Tabla permissions:  id, nombre, slug, descripcion
 *  - Tabla sucursales:   id, codigo, nombre, direccion, timezone, activo
 *  - Pivots:
 *      - role_user
 *      - permission_role
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // 0. Asegurar una sucursal principal
        // ---------------------------------------------------------------------
        $sucursalPrincipal = Sucursal::firstOrCreate(
            ['codigo' => 'SUCU-001'],
            [
                'nombre'    => 'Sucursal Principal',
                'direccion' => 'Carretera Panamericana Sur Km X',
                'timezone'  => 'America/Lima',
                'activo'    => true,
            ]
        );

        // ---------------------------------------------------------------------
        // 1. Permisos base del sistema
        // ---------------------------------------------------------------------
        $permisos = [
            [
                'slug'        => 'ver_dashboard',
                'nombre'      => 'Ver dashboard',
                'descripcion' => 'Puede ver el panel de indicadores generales.',
            ],
            [
                'slug'        => 'gestionar_catalogos',
                'nombre'      => 'Gestionar catálogos',
                'descripcion' => 'Puede crear/editar catálogos (áreas, horarios, paraderos, rutas, vehículos).',
            ],
            [
                'slug'        => 'gestionar_programaciones',
                'nombre'      => 'Gestionar programaciones',
                'descripcion' => 'Puede crear/editar/eliminar programaciones de transporte.',
            ],
            [
                'slug'        => 'gestionar_timewindows',
                'nombre'      => 'Gestionar ventanas de tiempo',
                'descripcion' => 'Puede definir y reabrir ventanas de tiempo.',
            ],
            [
                'slug'        => 'ver_reportes',
                'nombre'      => 'Ver reportes',
                'descripcion' => 'Puede acceder a reportes y exportaciones.',
            ],
            [
                'slug'        => 'ver_auditoria',
                'nombre'      => 'Ver auditoría',
                'descripcion' => 'Puede ver el log de auditoría.',
            ],
            [
                'slug'        => 'gestionar_usuarios',
                'nombre'      => 'Gestionar usuarios',
                'descripcion' => 'Puede crear/editar usuarios.',
            ],
            [
                'slug'        => 'gestionar_sucursales',
                'nombre'      => 'Gestionar sucursales',
                'descripcion' => 'Puede crear/editar sucursales.',
            ],
        ];

        foreach ($permisos as $permData) {
            Permission::firstOrCreate(
                ['slug' => $permData['slug']],
                [
                    'nombre'      => $permData['nombre'],
                    'descripcion' => $permData['descripcion'] ?? null,
                ]
            );
        }

        // ---------------------------------------------------------------------
        // 2. Roles del sistema y permisos asociados
        // ---------------------------------------------------------------------
        $rolesConfig = [
            'admin_general' => [
                'nombre'      => 'Administrador General',
                'descripcion' => 'Admin global, ve y gestiona todas las sucursales.',
                'permisos'    => collect($permisos)->pluck('slug')->all(), // TODOS los permisos
            ],
            'admin_sucursal' => [
                'nombre'      => 'Administrador de Sucursal',
                'descripcion' => 'Admin local, gestiona solo su sucursal.',
                'permisos'    => [
                    'ver_dashboard',
                    'gestionar_catalogos',
                    'gestionar_programaciones',
                    'gestionar_timewindows',
                    'ver_reportes',
                ],
            ],
            'operador' => [
                'nombre'      => 'Operador / Planificador',
                'descripcion' => 'Operador que arma programaciones dentro de ventanas de tiempo.',
                'permisos'    => [
                    'gestionar_programaciones',
                    'ver_reportes',
                ],
            ],
            'lectura' => [
                'nombre'      => 'Solo lectura (Jefes / RRHH)',
                'descripcion' => 'Puede ver reportes y dashboard, sin editar.',
                'permisos'    => [
                    'ver_dashboard',
                    'ver_reportes',
                ],
            ],
        ];

        foreach ($rolesConfig as $slug => $config) {
            $role = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'nombre'      => $config['nombre'],
                    'descripcion' => $config['descripcion'] ?? null,
                ]
            );

            $permIds = Permission::whereIn('slug', $config['permisos'])
                ->pluck('id')
                ->toArray();

            $role->permissions()->sync($permIds);
        }

        // ---------------------------------------------------------------------
        // 3. Usuario ADMIN por defecto
        // ---------------------------------------------------------------------
        $admin = User::firstOrCreate(
            ['codigo' => 'ADMIN'],
            [
                'nombre'      => 'Administrador',
                'apellido'    => 'General',
                'email'       => 'admin@agrokasa.test',
                'sucursal_id' => $sucursalPrincipal->id,  // <-- ahora usa el id REAL
                'password'    => Hash::make('Admin123*'), // cámbialo en producción
                'activo'      => true,
            ]
        );

        // Asignar rol "admin_general" usando el helper syncRoles de tu User.php
        $admin->syncRoles('admin_general');

        $this->command->info('Roles, permisos, sucursal principal y usuario ADMIN creados correctamente.');
    }
}
