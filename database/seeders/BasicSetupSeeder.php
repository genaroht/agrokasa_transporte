<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Horario;
use App\Models\Paradero;
use App\Models\Ruta;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class BasicSetupSeeder extends Seeder
{
    /**
     * Seed inicial de catálogos básicos.
     * IMPORTANTE: ESTE SEEDER NO CREA USERS.
     */
    public function run(): void
    {
        // Usamos la primera sucursal existente (creada por RolesAndPermissionsSeeder)
        $sucursal = Sucursal::first();

        // Si por alguna razón no hay, creamos una por defecto
        if (!$sucursal) {
            $sucursal = Sucursal::create([
                'codigo'    => 'SUCU-001',
                'nombre'    => 'Sucursal Principal',
                'direccion' => 'Carretera Panamericana Sur Km X',
                'timezone'  => config('app.timezone', 'America/Lima'),
                'activa'    => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ÁREAS BÁSICAS
        |--------------------------------------------------------------------------
        */
        $areas = [
            ['codigo' => 'AP_SAN',  'nombre' => 'Aplicación Sanidad'],
            ['codigo' => 'CS_ARA',  'nombre' => 'Cosecha Arándano'],
            ['codigo' => 'CS_PAL',  'nombre' => 'Cosecha Palto'],
            ['codigo' => 'AC_ARA',  'nombre' => 'Acopio Arándano'],
            ['codigo' => 'AC_PAL',  'nombre' => 'Acopio Palto'],
            ['codigo' => 'FT_PAL',  'nombre' => 'Labores Palto Fundo 1 y 2'],
            ['codigo' => 'CAL_LAB', 'nombre' => 'Calidad de Labores'],
            ['codigo' => 'CAL_COS', 'nombre' => 'Calidad de Cosecha'],
            ['codigo' => 'FERT',    'nombre' => 'Fertirriego'],
            ['codigo' => 'OFIC',    'nombre' => 'Oficina'],
        ];

        foreach ($areas as $a) {
            Area::firstOrCreate(
                [
                    'codigo'      => $a['codigo'],
                    'sucursal_id' => $sucursal->id,
                ],
                [
                    'nombre' => $a['nombre'],
                    'activo' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HORARIOS BÁSICOS
        |--------------------------------------------------------------------------
        */
        $horarios = [
            ['nombre' => 'Salida 13:00', 'hora' => '13:00:00'],
            ['nombre' => 'Salida 14:00', 'hora' => '14:00:00'],
            ['nombre' => 'Salida 15:30', 'hora' => '15:30:00'],
            ['nombre' => 'Salida 16:30', 'hora' => '16:30:00'],
            ['nombre' => 'Salida 17:00', 'hora' => '17:00:00'],
            ['nombre' => 'Salida 23:00', 'hora' => '23:00:00'],
            ['nombre' => 'Salida 02:00', 'hora' => '02:00:00'],
        ];

        foreach ($horarios as $h) {
            Horario::firstOrCreate(
                [
                    'sucursal_id' => $sucursal->id,
                    'hora'        => $h['hora'],
                ],
                [
                    'nombre' => $h['nombre'],
                    'activo' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PARADEROS BÁSICOS
        |--------------------------------------------------------------------------
        */
        $paraderos = [
            'Mercado Nuevo Amanecer',
            'Argos',
            'Los Pinos',
            'La Florida',
            'Virgen del Rosario',
            'Río Seco / Campiña',
        ];

        foreach ($paraderos as $nombreParadero) {
            Paradero::firstOrCreate(
                [
                    'sucursal_id' => $sucursal->id,
                    'nombre'      => $nombreParadero,
                ],
                [
                    'codigo'    => null,
                    'direccion' => null,
                    'activo'    => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | RUTAS BÁSICAS
        |--------------------------------------------------------------------------
        */
        $rutas = [
            ['codigo' => 'R1', 'nombre' => 'Ruta 1 - Principal'],
            ['codigo' => 'R2', 'nombre' => 'Ruta 2 - Norte'],
            ['codigo' => 'R3', 'nombre' => 'Ruta 3 - Sur'],
        ];

        foreach ($rutas as $r) {
            Ruta::firstOrCreate(
                [
                    'sucursal_id' => $sucursal->id,
                    'codigo'      => $r['codigo'],
                ],
                [
                    'nombre' => $r['nombre'],
                    'activo' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VEHÍCULOS (ejemplo)
        |--------------------------------------------------------------------------
        */
        $vehiculos = [
            ['placa' => 'ABC-123', 'codigo_interno' => 'BUS-01', 'capacidad' => 45],
            ['placa' => 'DEF-456', 'codigo_interno' => 'BUS-02', 'capacidad' => 45],
        ];

        foreach ($vehiculos as $v) {
            Vehiculo::firstOrCreate(
                [
                    'sucursal_id' => $sucursal->id,
                    'placa'       => $v['placa'],
                ],
                [
                    'codigo_interno' => $v['codigo_interno'],
                    'capacidad'      => $v['capacidad'],
                    'activo'         => true,
                ]
            );
        }

        $this->command?->info('Catálogos básicos creados correctamente (sin usuarios).');
    }
}
