<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ruta;
use Illuminate\Auth\Access\HandlesAuthorization;

class RutaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function manageCatalogs(User $user): bool
    {
        return $user->hasPermissionTo('manage_catalogs');
    }

    public function view(User $user, Ruta $ruta): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manageCatalogs($user);
    }

    public function update(User $user, Ruta $ruta): bool
    {
        return $this->manageCatalogs($user);
    }

    public function delete(User $user, Ruta $ruta): bool
    {
        return $this->manageCatalogs($user);
    }
}
