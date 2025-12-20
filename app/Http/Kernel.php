<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Middlewares globales.
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * Grupos de middlewares "web" y "api".
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * Aliases de middlewares para usar en rutas.
     *
     * Aquí registramos:
     *  - auth, guest, throttle, etc. (Laravel)
     *  - role, permission, sucursal, time.window (tuyos)
     */
    protected $middlewareAliases = [
        // === Laravel "core" ===
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
        'time.window' => \App\Http\Middleware\TimeWindowMiddleware::class,
    ];
}
