<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Listado de roles con filtros.
     */
    public function index(Request $request)
    {
        $query = Role::query();

        // Búsqueda por nombre o slug
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'ILIKE', '%' . $search . '%')
                  ->orWhere('slug', 'ILIKE', '%' . $search . '%');
            });
        }

        // Filtro por estado (activo / inactivo)
        if ($request->filled('estado')) {
            if ($request->input('estado') === 'activo') {
                $query->where('activo', true);
            } elseif ($request->input('estado') === 'inactivo') {
                $query->where('activo', false);
            }
        }

        $roles = $query
            ->orderBy('nombre')
            ->paginate(20)
            ->appends($request->query());

        return view('roles.index', compact('roles'));
    }

    /**
     * Formulario de creación de rol.
     */
    public function create()
    {
        $role = new Role();

        return view('roles.create', compact('role'));
    }

    /**
     * Guarda un nuevo rol.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:roles,nombre',
            'slug'        => 'required|string|max:100|unique:roles,slug',
            'descripcion' => 'nullable|string|max:255',
            'activo'      => 'sometimes|boolean',
        ]);

        $role = new Role();
        $role->nombre      = $data['nombre'];
        $role->slug        = $data['slug'];
        $role->descripcion = $data['descripcion'] ?? null;
        $role->activo      = $request->boolean('activo', true);
        $role->save();

        // Auditoría
        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'role_created',
            'auditable_type' => Role::class,
            'auditable_id'   => $role->id,
            'old_values'     => null,
            'new_values'     => [
                'nombre'      => $role->nombre,
                'slug'        => $role->slug,
                'descripcion' => $role->descripcion,
                'activo'      => $role->activo,
            ],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('roles.index')
            ->with('status', 'Rol creado correctamente.');
    }

    /**
     * Formulario de edición de rol.
     */
    public function edit(Role $role)
    {
        return view('roles.edit', compact('role'));
    }

    /**
     * Actualiza un rol existente.
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'nombre'      => 'required|string|max:100|unique:roles,nombre,' . $role->id,
            'slug'        => 'required|string|max:100|unique:roles,slug,' . $role->id,
            'descripcion' => 'nullable|string|max:255',
            'activo'      => 'sometimes|boolean',
        ]);

        $old = $role->only(['nombre', 'slug', 'descripcion', 'activo']);

        $role->nombre      = $data['nombre'];
        $role->slug        = $data['slug'];
        $role->descripcion = $data['descripcion'] ?? null;
        $role->activo      = $request->boolean('activo', false);
        $role->save();

        // Auditoría
        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'role_updated',
            'auditable_type' => Role::class,
            'auditable_id'   => $role->id,
            'old_values'     => $old,
            'new_values'     => $role->only(['nombre', 'slug', 'descripcion', 'activo']),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('roles.index')
            ->with('status', 'Rol actualizado correctamente.');
    }

    /**
     * Desactiva un rol (no lo borra físicamente).
     * Si quieres borrado real, aquí podrías usar $role->delete().
     */
    public function destroy(Request $request, Role $role)
    {
        $old = $role->only(['nombre', 'slug', 'descripcion', 'activo']);

        $role->activo = false;
        $role->save();

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'role_deactivated',
            'auditable_type' => Role::class,
            'auditable_id'   => $role->id,
            'old_values'     => $old,
            'new_values'     => ['activo' => false],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('roles.index')
            ->with('status', 'Rol desactivado correctamente.');
    }
}
