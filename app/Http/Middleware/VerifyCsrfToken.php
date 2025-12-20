<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Rutas que NO requieren token CSRF.
     * Aquí podrías excluir endpoints API si los necesitas sin sesión.
     */
    protected $except = [
        // 'api/*',
    ];
}
