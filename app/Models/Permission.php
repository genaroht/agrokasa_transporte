<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
    ];

    /**
     * Roles que tienen este permiso.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }
}
