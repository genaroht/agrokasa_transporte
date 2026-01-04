<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Ruta HOME de la aplicación.
     * Aquí es donde RedirectIfAuthenticated manda a un usuario
     * que intenta entrar a /login estando ya logueado.
     */
    public const HOME = '/dashboard'; // 👈 IMPORTANTE: apunta al dashboard

    /**
     * Define tus rutas.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Rate limiting.
     */
    protected function configureRateLimiting(): void
    {
        // Límite general para la API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Límite estricto para login (evitar fuerza bruta)
        RateLimiter::for('login', function (Request $request) {
            $codigo = (string) $request->input('codigo');
            return Limit::perMinute(5)->by($codigo . '|' . $request->ip());
        });
    }
}
