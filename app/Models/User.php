<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de usuario del sistema de transporte.
 *
 * Login por:
 *  - codigo (ej: U000123)
 *  - password (hash)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'codigo',
        'nombre',
        'apellido',
        'email',
        'sucursal_id',
        'area_id',        // área a la que pertenece
        'password',
        'activo',
        'last_login_at',
        // (si tuvieras un campo horario_id legacy podrías añadirlo aquí)
    ];

    /**
     * Atributos ocultos (no se devuelven en arrays/JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts (conversión de tipos).
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'activo'            => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    /**
     * Sucursal asociada al usuario (puede ser null para admin general).
     */
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Área a la que pertenece el usuario.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Horarios permitidos para el usuario (muchos a muchos).
     * Tabla pivot: horario_user (user_id, horario_id).
     */
    public function horarios()
    {
        return $this->belongsToMany(Horario::class, 'horario_user')
            ->withTimestamps();
    }

    /**
     * Roles del usuario.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Permisos agregados por los roles.
     */
    public function rolePermissions()
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | COMPATIBILIDAD TIPO "SPATIE"
    |--------------------------------------------------------------------------
    |
    | Estos métodos permiten que el resto del código use:
    |   - $user->syncRoles(...)
    |   - $user->assignRole(...)
    |   - $user->getRoleNames()
    |   - $user->getAllPermissions()
    | aunque tu implementación sea personalizada.
    |
    */

    /**
     * Sincroniza los roles del usuario.
     *
     * Acepta:
     *  - string (slug del rol)
     *  - Role
     *  - id (int)
     *  - array/Collection de cualquiera de los anteriores
     */
    public function syncRoles($roles)
    {
        $roles = collect(is_array($roles) || $roles instanceof \Traversable ? $roles : [$roles]);

        $roleIds = $roles->map(function ($role) {
            if ($role instanceof Role) {
                return $role->id;
            }

            if (is_numeric($role)) {
                return (int) $role;
            }

            // Asumimos string => slug
            return Role::where('slug', $role)->value('id');
        })->filter()->unique()->values()->all();

        $this->roles()->sync($roleIds);

        return $this;
    }

    /**
     * Asigna un rol adicional sin quitar los existentes.
     *
     * Acepta Role | id | slug.
     */
    public function assignRole($role)
    {
        $id = null;

        if ($role instanceof Role) {
            $id = $role->id;
        } elseif (is_numeric($role)) {
            $id = (int) $role;
        } else {
            $id = Role::where('slug', $role)->value('id');
        }

        if ($id) {
            $this->roles()->syncWithoutDetaching([$id]);
        }

        return $this;
    }

    /**
     * Obtiene una colección con los nombres de roles.
     * Similar a Spatie::getRoleNames().
     */
    public function getRoleNames()
    {
        // Usamos el campo "nombre" del rol (ej: "Admin General").
        return $this->roles()->pluck('nombre');
    }

    /**
     * Obtiene TODOS los permisos del usuario (agregados por sus roles).
     * Similar a Spatie::getAllPermissions().
     */
    public function getAllPermissions()
    {
        return $this->rolePermissions();
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS DE ROL
    |--------------------------------------------------------------------------
    */

    /**
     * ¿Es Administrador General?
     * (Rol con slug "admin_general")
     */
    public function isAdminGeneral(): bool
    {
        return $this->hasRole('admin_general');
    }

    /**
     * Verifica si el usuario tiene un rol por slug.
     *
     * Ejemplo:
     *   $user->hasRole('admin_sucursal');
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains(function (Role $role) use ($roleSlug) {
            return $role->slug === $roleSlug;
        });
    }

    /**
     * Verifica si el usuario tiene al menos uno de los roles indicados.
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        $roleSlugs = array_map('strval', $roleSlugs);

        return $this->roles->contains(function (Role $role) use ($roleSlugs) {
            return in_array($role->slug, $roleSlugs, true);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS DE PERMISOS
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si el usuario tiene un permiso por slug.
     *
     * Ejemplo:
     *   $user->hasPermissionTo('gestionar_programaciones');
     */
    public function hasPermissionTo(string $permissionSlug): bool
    {
        // Admin General tiene todo por definición
        if ($this->isAdminGeneral()) {
            return true;
        }

        return $this->rolePermissions()->contains(function (Permission $perm) use ($permissionSlug) {
            return $perm->slug === $permissionSlug;
        });
    }

    /**
     * Atajo para saber si puede gestionar catálogos.
     */
    public function canManageCatalogs(): bool
    {
        return $this->hasPermissionTo('manage_catalogs');
    }

    /*
    |--------------------------------------------------------------------------
    | ATRIBUTOS DERIVADOS / FORMATEADOS
    |--------------------------------------------------------------------------
    */

    /**
     * Nombre completo del usuario. $user->nombre_completo
     */
    public function getNombreCompletoAttribute(): string
    {
        return trim(($this->nombre ?? '') . ' ' . ($this->apellido ?? ''));
    }

    /**
     * Rol principal (el primero). $user->rol_principal
     */
    public function getRolPrincipalAttribute(): ?string
    {
        $role = $this->roles->first();
        return $role ? $role->nombre : null;
    }
}
