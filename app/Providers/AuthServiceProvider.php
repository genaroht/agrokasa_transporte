<?php

namespace App\Providers;

use App\Models\Area;
use App\Models\Paradero;
use App\Models\Horario;
use App\Models\Ruta;
use App\Models\Vehiculo;
use App\Models\User;
use App\Policies\AreaPolicy;
use App\Policies\ParaderoPolicy;
use App\Policies\HorarioPolicy;
use App\Policies\RutaPolicy;
use App\Policies\VehiculoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Las policies para la aplicación.
     *
     * Aquí mapeamos cada modelo a su Policy correspondiente.
     */
    protected $policies = [
        Area::class     => AreaPolicy::class,
        Paradero::class => ParaderoPolicy::class,
        Horario::class  => HorarioPolicy::class,
        Ruta::class     => RutaPolicy::class,
        Vehiculo::class => VehiculoPolicy::class,
        // Más adelante puedes agregar:
        // User::class      => UserPolicy::class,
        // Sucursal::class  => SucursalPolicy::class,
        // Programacion::class => ProgramacionPolicy::class,
        // etc.
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * Gate global:
         * Si el usuario es ADMIN GENERAL, se le permite cualquier acción
         * sin tener que definirlo explícitamente en cada Policy ni Gate.
         *
         * Esto impacta en:
         *  - @can('lo_que_sea')
         *  - Gate::allows('lo_que_sea')
         *  - middleware('can:lo_que_sea') si lo usas
         */
        Gate::before(function (?User $user, string $ability = null) {
            if (!$user) {
                return null;
            }

            if (method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral()) {
                return true;
            }

            return null; // deja que las policies normales decidan
        });
    }
}
