<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
         |------------------------------------------------------------
         | Middlewares de los grupos web / api (si quieres añadir)
         |------------------------------------------------------------
         |
         | Puedes usar:
         |   $middleware->web(append: [...]);
         |   $middleware->api(append: [...]);
         |
         | De momento no añadimos nada aquí.
         */

        /*
         |------------------------------------------------------------
         | ALIASES DE MIDDLEWARE
         |------------------------------------------------------------
         | Aquí van TODOS los alias que usas en las rutas:
         |   auth, guest, throttle, sucursal, permission, time.window, etc.
         */
        $middleware->alias([
            // === Laravel core ===
            'auth'             => \App\Http\Middleware\Authenticate::class,
            'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session'     => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers'    => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can'              => \Illuminate\Auth\Middleware\Authorize::class,
            'guest'            => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed'           => \App\Http\Middleware\ValidateSignature::class,
            'throttle'         => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified'         => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // === Middlewares personalizados ===
            'role'        => \App\Http\Middleware\RoleMiddleware::class,
            'permission'  => \App\Http\Middleware\PermissionMiddleware::class,
            'sucursal'    => \App\Http\Middleware\SucursalMiddleware::class,

            // 👇 ESTE ES EL QUE FALTABA
            'time.window' => \App\Http\Middleware\TimeWindowMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
