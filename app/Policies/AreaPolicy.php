<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Area;
use Illuminate\Auth\Access\HandlesAuthorization;

class AreaPolicy
{
    use HandlesAuthorization;

    /**
     * Cualquier usuario autenticado puede ver el listado de áreas
     * (el filtrado por sucursal ya se maneja en consultas y middlewares).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Control central para crear/editar/eliminar áreas de catálogo.
     *
     * Lo usamos desde los controladores con:
     *   $this->authorize('manageCatalogs', Area::class);
     */
    public function manageCatalogs(User $user): bool
    {
        return $user->hasPermissionTo('manage_catalogs');
    }

    /**
     * Métodos estándar por si en algún momento quieres usarlos:
     */
    public function view(User $user, Area $area): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manageCatalogs($user);
    }

    public function update(User $user, Area $area): bool
    {
        return $this->manageCatalogs($user);
    }

    public function delete(User $user, Area $area): bool
    {
        return $this->manageCatalogs($user);
    }
}
