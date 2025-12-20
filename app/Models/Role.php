<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Role
 *
 * Campos típicos esperados:
 *  - id
 *  - nombre (string)
 *  - slug   (string, único, ej: "admin_general", "admin_sucursal", etc.)
 *  - descripcion (nullable)
 *  - activo (boolean)
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Usuarios que tienen este rol.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Permisos asociados al rol.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope para filtrar solo roles activos.
     *
     * Ej: Role::activos()->get()
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
