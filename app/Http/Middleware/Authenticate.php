<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * A dónde redirigir si no está autenticado (solo para peticiones web).
     */
    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            // Asegúrate de tener esta ruta definida
            return route('login');
        }

        return null;
    }
}
