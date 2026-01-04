<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\User;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\ParaderoController;
use App\Http\Controllers\LugarController;
use App\Http\Controllers\RutaController;
use App\Http\Controllers\VehiculoController;
use App\Http\Controllers\ProgramacionController;
use App\Http\Controllers\TimeWindowController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ProgramacionExportController;

/*
|--------------------------------------------------------------------------
| Ruta raíz
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Si algún middleware viejo todavía usa /home, lo redirigimos al dashboard
Route::get('/home', function () {
    return redirect()->route('dashboard');
});


/*
|--------------------------------------------------------------------------
| Rutas de autenticación (guest)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'loginWeb'])
        ->name('login.attempt');

    Route::get('/olvide-mi-password', [AuthController::class, 'showForgotPasswordInfo'])
        ->name('password.forgot.info');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logoutWeb'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas protegidas (auth)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | Acceso:
    | - admin_general
    | - admin_sucursal
    | - operador      (ver_dashboard en mapping)
    | - lectura       (ver_dashboard en mapping)
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware(['sucursal', 'permission:ver_dashboard|gestionar_programaciones']);

    /*
    |--------------------------------------------------------------------------
    | Perfil / contraseña
    |--------------------------------------------------------------------------
    */

    Route::get('/perfil', [ProfileController::class, 'show'])
        ->name('profile.show');

    Route::get('/perfil/password', [AuthController::class, 'showChangePasswordForm'])
        ->name('password.change.form');

    Route::post('/perfil/password', [AuthController::class, 'changePassword'])
        ->name('password.change.update');

    /*
    |--------------------------------------------------------------------------
    | Impersonación (solo admin_general)
    |--------------------------------------------------------------------------
    */

    Route::post('/impersonate/{user}', function (Request $request, User $user) {
        if (! $request->user()->isAdminGeneral()) {
            abort(403, 'No autorizado para impersonar usuarios.');
        }

        $request->session()->put('impersonated_by', $request->user()->id);
        Auth::login($user);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Ahora estás impersonando a: ' . $user->nombre_completo);
    })->name('impersonate.start');

    Route::post('/impersonate/stop', function (Request $request) {
        $originalId = $request->session()->pull('impersonated_by');

        if ($originalId) {
            $original = User::find($originalId);
            if ($original) {
                Auth::login($original);
            }
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Has vuelto a tu usuario.');
    })->name('impersonate.stop');

    /*
    |--------------------------------------------------------------------------
    | Configuración / Seguridad / Usuarios / Sucursales (solo ADMIN_GENERAL)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin_general')->group(function () {

        // Configuración global
        Route::get('/configuracion', [ConfigController::class, 'index'])
            ->name('config.index');

        Route::post('/configuracion', [ConfigController::class, 'update'])
            ->name('config.update');

        // Usuarios
        Route::resource('usuarios', UserController::class)
            ->except(['show']);

        // Roles
        Route::resource('roles', RoleController::class)
            ->names('roles')
            ->except(['show']);

        // Sucursales
        Route::resource('sucursales', SucursalController::class)
            ->parameters(['sucursales' => 'sucursal'])
            ->except(['show']);

        Route::post('/sucursales/{sucursal}/activar', [SucursalController::class, 'activar'])
            ->name('sucursales.activar');

        // Cambiar sucursal ACTIVA (selector del header) – SOLO Admin General
        Route::post('/sucursales/cambiar', [SucursalController::class, 'cambiarSucursalActiva'])
            ->name('sucursales.cambiar');
    });

    /*
    |--------------------------------------------------------------------------
    | Grupo con contexto de sucursal
    |--------------------------------------------------------------------------
    */

    Route::middleware(['sucursal'])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Catálogos
        |--------------------------------------------------------------------------
        |
        | Acceso:
        | - admin_general         (pasa todo)
        | - admin_sucursal        (pasa por '*')
        | - operador              (gestionar_catalogos en mapping)
        | - lectura               NO pasa
        */

        Route::prefix('catalogos')->name('catalogos.')->group(function () {

            Route::resource('areas', AreaController::class)
                ->except(['show'])
                ->middleware('permission:gestionar_catalogos|gestionar_programaciones');

            Route::resource('horarios', HorarioController::class)
                ->except(['show'])
                ->middleware('permission:gestionar_catalogos|gestionar_programaciones');

            Route::resource('lugares', LugarController::class)
                ->parameters(['lugares' => 'lugar'])
                ->names('lugares')
                ->except(['show']); // Lugares accesible sin permiso específico

            Route::resource('paraderos', ParaderoController::class)
                ->except(['show'])
                ->middleware('permission:gestionar_catalogos|gestionar_programaciones');

            Route::resource('rutas', RutaController::class)
                ->except(['show'])
                ->middleware('permission:gestionar_catalogos|gestionar_programaciones');

            Route::resource('vehiculos', VehiculoController::class)
                ->except(['show'])
                ->middleware('permission:gestionar_catalogos|gestionar_programaciones');
        });

        /*
        |--------------------------------------------------------------------------
        | Programaciones
        |--------------------------------------------------------------------------
        |
        | - admin_general     → todo
        | - admin_sucursal    → todo en SU sucursal (por SucursalMiddleware)
        | - operador          → programar y ver
        | - lectura           → solo ver listados / reportes
        */

        Route::prefix('programaciones')->name('programaciones.')->group(function () {

            // 1) RUTAS DE REPORTES (solo ver Reportes/Resúmenes)
            Route::get('/resumen/paradero-horario', [ProgramacionController::class, 'resumenParaderoHorario'])
                ->name('resumen.paradero-horario')
                ->middleware('permission:ver_reportes');

            Route::get('/reporte-ruta-lote-com', [ProgramacionController::class, 'reporteRutaLoteCom'])
                ->name('reporte_ruta_lote_com')
                ->middleware('permission:ver_reportes');

            Route::get('/resumen/ruta-paradero', [ProgramacionController::class, 'resumenRutaParadero'])
                ->name('resumen.ruta-paradero')
                ->middleware('permission:ver_reportes');

            // 2) LISTADO
            Route::get('/', [ProgramacionController::class, 'index'])
                ->name('index')
                ->middleware('permission:gestionar_programaciones|ver_reportes');

            // 2B) CAPTURA RÁPIDA POR PARADERO (solo programar)
            Route::get('/captura-rapida', [ProgramacionController::class, 'capturaRapida'])
                ->name('captura-rapida')
                ->middleware('permission:gestionar_programaciones');

            Route::post('/captura-rapida/guardar', [ProgramacionController::class, 'guardarCapturaRapida'])
                ->name('captura-rapida.guardar')
                ->middleware(['permission:gestionar_programaciones', 'time.window']);

            // 3) CREAR PROGRAMACIÓN MANUAL
            Route::get('/crear', [ProgramacionController::class, 'create'])
                ->name('create')
                ->middleware('permission:gestionar_programaciones');

            Route::post('/', [ProgramacionController::class, 'store'])
                ->name('store')
                ->middleware(['permission:gestionar_programaciones', 'time.window']);

            Route::put('/{programacion}/cabecera', [ProgramacionController::class, 'actualizarCabecera'])
                ->name('update.cabecera')
                ->middleware(['role:admin_general', 'time.window']);

            // 4) RUTAS CON ID (editar/ver/cerrar/reabrir/eliminar)
            Route::get('/{programacion}/editar', [ProgramacionController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:gestionar_programaciones|ver_reportes');

            Route::put('/{programacion}', [ProgramacionController::class, 'update'])
                ->name('update')
                ->middleware(['permission:gestionar_programaciones', 'time.window']);

            Route::post('/{programacion}/confirmar', [ProgramacionController::class, 'confirmar'])
                ->name('confirmar')
                ->middleware(['permission:gestionar_programaciones', 'time.window']);

            Route::post('/{programacion}/cerrar', [ProgramacionController::class, 'cerrar'])
                ->name('cerrar')
                ->middleware(['permission:gestionar_programaciones', 'time.window']);

            Route::post('/{programacion}/reabrir', [ProgramacionController::class, 'reabrir'])
                ->name('reabrir')
                ->middleware(['permission:gestionar_programaciones', 'time.window']);


            // 5) VER PROGRAMACIÓN (detalle)
            Route::get('/{programacion}', [ProgramacionController::class, 'show'])
                ->name('show')
                ->middleware('permission:gestionar_programaciones|ver_reportes');

            // 6) ELIMINAR
            Route::delete('/{programacion}', [ProgramacionController::class, 'destroy'])
                ->name('destroy')
                ->middleware(['permission:gestionar_programaciones', 'time.window']);
        });

        /*
        |--------------------------------------------------------------------------
        | Ventanas de tiempo
        |--------------------------------------------------------------------------
        |
        | - admin_general / admin_sucursal → gestionan ventanas (por mapping '*')
        | - operador / lectura             → NO gestionan.
        */

        Route::prefix('ventanas-tiempo')->name('timewindows.')->group(function () {

            Route::get('/', [TimeWindowController::class, 'index'])
                ->name('index')
                ->middleware('permission:gestionar_timewindows');

            Route::get('/crear', [TimeWindowController::class, 'create'])
                ->name('create')
                ->middleware('permission:gestionar_timewindows');

            Route::post('/', [TimeWindowController::class, 'store'])
                ->name('store')
                ->middleware('permission:gestionar_timewindows');

            Route::get('/{timewindow}/editar', [TimeWindowController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:gestionar_timewindows');

            // PUT y PATCH para el formulario de edición
            Route::match(['put', 'patch'], '/{timewindow}', [TimeWindowController::class, 'update'])
                ->name('update')
                ->middleware('permission:gestionar_timewindows');

            // Activar / desactivar rápidamente desde el listado
            Route::post('/{timewindow}/toggle', [TimeWindowController::class, 'toggle'])
                ->name('toggle')
                ->middleware('permission:gestionar_timewindows');

            Route::delete('/{timewindow}', [TimeWindowController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:gestionar_timewindows');

            Route::post('/{timewindow}/reabrir', [TimeWindowController::class, 'reopen'])
                ->name('reopen')
                ->middleware('permission:gestionar_timewindows');

            // Estado de ventanas para el usuario actual (puede servir a operador también).
            Route::get('/mi-estado', [TimeWindowController::class, 'status'])
                ->name('status');
        });

        /*
        |--------------------------------------------------------------------------
        | Auditoría
        |--------------------------------------------------------------------------
        */

        Route::middleware('permission:ver_auditoria')->group(function () {
            Route::get('auditoria', [AuditLogController::class, 'index'])
                ->name('audit.index');

            Route::get('auditoria/{log}', [AuditLogController::class, 'show'])
                ->name('audit.show');
        });

        /*
        |--------------------------------------------------------------------------
        | Reportes
        |--------------------------------------------------------------------------
        |
        | Acceso:
        | - admin_general / admin_sucursal / operador / lectura
        |   (todos tienen ver_reportes en mapping salvo que cambies).
        */

        Route::prefix('reportes')
            ->name('reportes.')
            ->middleware('permission:ver_reportes')
            ->group(function () {

                Route::get('/', [ReporteController::class, 'index'])
                    ->name('index');

                // 1) Resumen Paradero x Horario
                Route::get('/resumen/paradero-horario/excel', [ReporteController::class, 'resumenParaderoHorarioExcel'])
                    ->name('resumen.paradero-horario.excel');

                Route::get('/resumen/paradero-horario/pdf', [ReporteController::class, 'resumenParaderoHorarioPdf'])
                    ->name('resumen.paradero-horario.pdf');

                // 2) Resumen Ruta x Paradero
                Route::get('/resumen/ruta-paradero/excel', [ReporteController::class, 'resumenRutaParaderoExcel'])
                    ->name('resumen.ruta-paradero.excel');

                Route::get('/resumen/ruta-paradero/pdf', [ReporteController::class, 'resumenRutaParaderoPdf'])
                    ->name('resumen.ruta-paradero.pdf');

                // 3) Rutas / Lotes / Comedores
                Route::get('/rutas-lotes/excel', [ReporteController::class, 'rutasLotesExcel'])
                    ->name('rutas-lotes.excel');

                Route::get('/rutas-lotes/pdf', [ReporteController::class, 'rutasLotesPdf'])
                    ->name('rutas-lotes.pdf');
            });
    });
});
