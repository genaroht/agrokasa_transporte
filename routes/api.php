<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Controladores API (versión JSON)
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\SucursalApiController;
use App\Http\Controllers\Api\AreaApiController;
use App\Http\Controllers\Api\HorarioApiController;
use App\Http\Controllers\Api\ParaderoApiController;
use App\Http\Controllers\Api\RutaApiController;
use App\Http\Controllers\Api\VehiculoApiController;
use App\Http\Controllers\Api\ProgramacionApiController;
use App\Http\Controllers\Api\TimeWindowApiController;
use App\Http\Controllers\Api\AuditLogApiController;
use App\Http\Controllers\Api\ReporteApiController;
use App\Http\Controllers\Api\ProgramacionRutaLoteController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Todas las rutas aquí se cargan con el grupo de middleware "api".
| Prefijo final: /api/v1/...
|
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTENTICACIÓN (API)
    |--------------------------------------------------------------------------
    |
    | Estas rutas serán consumidas por:
    | - App de escritorio Windows
    | - App móvil Android / iOS
    |
    | Autenticación con Laravel Sanctum.
    |
    */

    // Login (sin auth)
    Route::post('/auth/login', [AuthApiController::class, 'login'])
        ->name('api.auth.login');

    /*
    |--------------------------------------------------------------------------
    | RUTAS PROTEGIDAS POR SANCTUM
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        // Información del usuario autenticado
        Route::get('/auth/me', [AuthApiController::class, 'me'])
            ->name('api.auth.me');

        // Cerrar sesión (revocar token)
        Route::post('/auth/logout', [AuthApiController::class, 'logout'])
            ->name('api.auth.logout');

        /*
        |--------------------------------------------------------------------------
        | META / PING (health-check para apps)
        |--------------------------------------------------------------------------
        |
        | GET /api/v1/meta/ping
        | Verifica:
        |   - Token válido
        |   - Hora de servidor
        |   - Datos básicos de usuario y sucursal
        |
        */

        Route::get('/meta/ping', function (Request $request) {
            /** @var \App\Models\User $user */
            $user     = $request->user();
            $sucursal = $user?->sucursal;

            return response()->json([
                'ok'   => true,
                'user' => [
                    'id'              => $user->id,
                    'codigo'          => $user->codigo,
                    'nombre'          => $user->nombre,
                    'apellido'        => $user->apellido,
                    'nombre_completo' => $user->nombre_completo,
                    'sucursal_id'     => $user->sucursal_id,
                    'sucursal_nombre' => $sucursal?->nombre,
                    'roles'           => $user->roles()->pluck('slug'),
                ],
                'server_time'       => now()->toDateTimeString(),
                'timezone_app'      => config('app.timezone'),
                'timezone_sucursal' => $sucursal?->timezone,
            ]);
        })->name('api.meta.ping');

        /*
        |--------------------------------------------------------------------------
        | ENDPOINTS ESPECIALES RUTA/LOTE/COM Y ESTADOS TIME WINDOWS
        |--------------------------------------------------------------------------
        */

        // Programaciones - detalle por Rutas/Lotes/Comedor (matriz específica)
            Route::get('programaciones/{programacion}/rutas-lotes', [ProgramacionRutaLoteController::class, 'index']);

                Route::put('programaciones/{programacion}/rutas-lotes', [ProgramacionRutaLoteController::class, 'update'])
                    ->middleware('time.window');

                Route::get('reportes/ruta-lote-com', [ProgramacionRutaLoteController::class, 'reporteRutaLoteCom']);

                // 👉 Ventanas de tiempo activas (versión API)
                Route::get('time-windows/activos', [TimeWindowApiController::class, 'activos'])
                    ->name('api.timewindows.activos');

                // 👉 Auditoría (versión API)
                Route::get('audit-logs', [AuditLogApiController::class, 'index'])
                    ->name('api.audit.index');

        /*
        |--------------------------------------------------------------------------
        | RUTAS SOLO PARA ADMIN_GENERAL (gestión global)
        |--------------------------------------------------------------------------
        */

        Route::middleware('role:admin_general')->group(function () {

            // Usuarios (API resource JSON)
            Route::apiResource('usuarios', UserApiController::class, [
                'as' => 'api', // nombres tipo api.usuarios.index, ...
            ]);

            // Sucursales
            Route::apiResource('sucursales', SucursalApiController::class, [
                'as' => 'api',
            ]);
        });

        /*
        |--------------------------------------------------------------------------
        | RUTAS QUE DEPENDEN DE LA SUCURSAL DEL USUARIO
        |--------------------------------------------------------------------------
        |
        | Se valida sucursal mediante el middleware 'sucursal'.
        | Una vez pasada la validación, todos estos endpoints trabajan
        | sobre la sucursal actual del usuario.
        |
        */

        Route::middleware('sucursal')->group(function () {

            /*
            |--------------------------------------------------------------------------
            | CATÁLOGOS (Áreas, Horarios, Paraderos, Rutas, Vehículos)
            |--------------------------------------------------------------------------
            */

            Route::apiResource('catalogos/areas', AreaApiController::class, [
                'as' => 'api.catalogos',
            ])->middleware('permission:gestionar_catalogos');

            Route::apiResource('catalogos/horarios', HorarioApiController::class, [
                'as' => 'api.catalogos',
            ])->middleware('permission:gestionar_catalogos');

            Route::apiResource('catalogos/paraderos', ParaderoApiController::class, [
                'as' => 'api.catalogos',
            ])->middleware('permission:gestionar_catalogos');

            Route::apiResource('catalogos/rutas', RutaApiController::class, [
                'as' => 'api.catalogos',
            ])->middleware('permission:gestionar_catalogos');

            Route::apiResource('catalogos/vehiculos', VehiculoApiController::class, [
                'as' => 'api.catalogos',
            ])->middleware('permission:gestionar_catalogos');

            /*
            |--------------------------------------------------------------------------
            | PROGRAMACIONES DE PERSONAL
            |--------------------------------------------------------------------------
            */

            Route::prefix('programaciones')->name('api.programaciones.')->group(function () {

                Route::get('/', [ProgramacionApiController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:gestionar_programaciones|ver_reportes');

                Route::post('/', [ProgramacionApiController::class, 'store'])
                    ->name('store')
                    ->middleware(['permission:gestionar_programaciones', 'timewindow']);

                Route::get('/{programacion}', [ProgramacionApiController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:gestionar_programaciones|ver_reportes');

                Route::put('/{programacion}', [ProgramacionApiController::class, 'update'])
                    ->name('update')
                    ->middleware(['permission:gestionar_programaciones', 'timewindow']);

                Route::delete('/{programacion}', [ProgramacionApiController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware(['permission:gestionar_programaciones', 'timewindow']);

                Route::get('/{programacion}/matriz', [ProgramacionApiController::class, 'matriz'])
                    ->name('matriz')
                    ->middleware('permission:gestionar_programaciones|ver_reportes');

                Route::put('/{programacion}/matriz', [ProgramacionApiController::class, 'updateMatriz'])
                    ->name('matriz.update')
                    ->middleware(['permission:gestionar_programaciones', 'timewindow']);

                Route::get('/{programacion}/export/excel', [ProgramacionApiController::class, 'exportExcel'])
                    ->name('export.excel')
                    ->middleware('permission:ver_reportes');

                Route::get('/{programacion}/export/pdf', [ProgramacionApiController::class, 'exportPdf'])
                    ->name('export.pdf')
                    ->middleware('permission:ver_reportes');
            });

            /*
            |--------------------------------------------------------------------------
            | VENTANAS DE TIEMPO (MÓDULO ESPECÍFICO)
            |--------------------------------------------------------------------------
            */

            Route::prefix('ventanas-tiempo')->name('api.timewindows.')->group(function () {

                Route::get('/', [TimeWindowApiController::class, 'index'])
                    ->name('index')
                    ->middleware('permission:gestionar_timewindows');

                Route::post('/', [TimeWindowApiController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:gestionar_timewindows');

                Route::get('/{timewindow}', [TimeWindowApiController::class, 'show'])
                    ->name('show')
                    ->middleware('permission:gestionar_timewindows');

                Route::put('/{timewindow}', [TimeWindowApiController::class, 'update'])
                    ->name('update')
                    ->middleware('permission:gestionar_timewindows');

                Route::delete('/{timewindow}', [TimeWindowApiController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:gestionar_timewindows');

                Route::post('/{timewindow}/reopen', [TimeWindowApiController::class, 'reopen'])
                    ->name('reopen')
                    ->middleware('permission:gestionar_timewindows');

                Route::get('/mi-estado', [TimeWindowApiController::class, 'myWindows'])
                    ->name('mywindows');
            });

            /*
            |--------------------------------------------------------------------------
            | AUDITORÍA
            |--------------------------------------------------------------------------
            */

            Route::prefix('auditoria')->name('api.audit.')->middleware('permission:ver_auditoria')->group(function () {

                Route::get('/', [AuditLogApiController::class, 'index'])
                    ->name('index');

                Route::get('/{log}', [AuditLogApiController::class, 'show'])
                    ->name('show');
            });

            /*
            |--------------------------------------------------------------------------
            | REPORTES / RESÚMENES (MATRICES)
            |--------------------------------------------------------------------------
            */

            Route::prefix('reportes')->name('api.reportes.')->middleware('permission:ver_reportes')->group(function () {

                Route::get('/resumen/paradero-horario', [ReporteApiController::class, 'resumenParaderoHorario'])
                    ->name('resumen.paradero-horario');

                Route::get('/resumen/ruta-paradero', [ReporteApiController::class, 'resumenRutaParadero'])
                    ->name('resumen.ruta-paradero');

                Route::get('/resumen/paradero-horario/excel', [ReporteApiController::class, 'resumenParaderoHorarioExcel'])
                    ->name('resumen.paradero-horario.excel');

                Route::get('/resumen/paradero-horario/pdf', [ReporteApiController::class, 'resumenParaderoHorarioPdf'])
                    ->name('resumen.paradero-horario.pdf');

                Route::get('/resumen/ruta-paradero/excel', [ReporteApiController::class, 'resumenRutaParaderoExcel'])
                    ->name('resumen.ruta-paradero.excel');

                Route::get('/resumen/ruta-paradero/pdf', [ReporteApiController::class, 'resumenRutaParaderoPdf'])
                    ->name('resumen.ruta-paradero.pdf');
            });
        });
    });
});
