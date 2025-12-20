<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Horario;
use Illuminate\Auth\Access\HandlesAuthorization;

class HorarioPolicy
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

    public function view(User $user, Horario $horario): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manageCatalogs($user);
    }

    public function update(User $user, Horario $horario): bool
    {
        return $this->manageCatalogs($user);
    }

    public function delete(User $user, Horario $horario): bool
    {
        return $this->manageCatalogs($user);
    }
}
