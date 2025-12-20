<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Paradero;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParaderoPolicy
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

    public function view(User $user, Paradero $paradero): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manageCatalogs($user);
    }

    public function update(User $user, Paradero $paradero): bool
    {
        return $this->manageCatalogs($user);
    }

    public function delete(User $user, Paradero $paradero): bool
    {
        return $this->manageCatalogs($user);
    }
}
