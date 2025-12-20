<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehiculo;
use Illuminate\Auth\Access\HandlesAuthorization;

class VehiculoPolicy
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

    public function view(User $user, Vehiculo $vehiculo): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manageCatalogs($user);
    }

    public function update(User $user, Vehiculo $vehiculo): bool
    {
        return $this->manageCatalogs($user);
    }

    public function delete(User $user, Vehiculo $vehiculo): bool
    {
        return $this->manageCatalogs($user);
    }
}
