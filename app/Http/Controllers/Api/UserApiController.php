<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    /**
     * GET /api/v1/usuarios
     * Lista de usuarios (solo ADMIN_GENERAL).
     */
    public function index(Request $request)
    {
        $this->ensureAdminGeneral($request);

        $query = User::query()
            ->with(['sucursal', 'roles'])
            ->orderBy('codigo');

        // Filtros opcionales
        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->integer('sucursal_id'));
        }

        if ($request->filled('activo')) {
            $activo = filter_var(
                $request->input('activo'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
            if (! is_null($activo)) {
                $query->where('activo', $activo);
            }
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';

                // En PostgreSQL puedes usar ilike para no distinguir mayúsculas
                $q->where('codigo', 'ilike', $like)
                    ->orWhere('nombre', 'ilike', $like)
                    ->orWhere('apellido', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like);
            });
        }

        $users = $query->paginate(50);

        return response()->json($users);
    }

    /**
     * POST /api/v1/usuarios
     * Crea un usuario nuevo.
     */
    public function store(Request $request)
    {
        $this->ensureAdminGeneral($request);

        $data = $request->validate([
            'codigo'      => ['required', 'string', 'max:20', 'unique:users,codigo'],
            'nombre'      => ['required', 'string', 'max:100'],
            'apellido'    => ['required', 'string', 'max:100'],
            'email'       => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
            'password'    => ['required', 'string', 'min:8'],
            'activo'      => ['sometimes', 'boolean'],
            // roles por SLUG: ["admin_general", "admin_sucursal", ...]
            'roles'       => ['nullable', 'array'],
            'roles.*'     => ['string', 'max:50'],
        ]);

        $user = new User();
        $user->codigo      = $data['codigo'];
        $user->nombre      = $data['nombre'];
        $user->apellido    = $data['apellido'];
        $user->email       = $data['email'] ?? null;
        $user->sucursal_id = $data['sucursal_id'] ?? null;
        $user->password    = Hash::make($data['password']);
        $user->activo      = $data['activo'] ?? true;
        $user->save();

        // Sincronizar roles si vienen en la petición
        if (! empty($data['roles'])) {
            $roleIds = Role::whereIn('slug', $data['roles'])->pluck('id')->all();
            $user->roles()->sync($roleIds);
        }

        $user->load(['sucursal', 'roles']);

        return response()->json($user, 201);
    }

    /**
     * GET /api/v1/usuarios/{usuario}
     * Muestra detalle de un usuario.
     */
    public function show(Request $request, User $usuario)
    {
        $this->ensureAdminGeneral($request);

        $usuario->load(['sucursal', 'roles']);

        return response()->json($usuario);
    }

    /**
     * PUT/PATCH /api/v1/usuarios/{usuario}
     * Actualiza un usuario.
     */
    public function update(Request $request, User $usuario)
    {
        $this->ensureAdminGeneral($request);

        $data = $request->validate([
            'codigo'      => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('users', 'codigo')->ignore($usuario->id),
            ],
            'nombre'      => ['sometimes', 'string', 'max:100'],
            'apellido'    => ['sometimes', 'string', 'max:100'],
            'email'       => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
            'password'    => ['nullable', 'string', 'min:8'],
            'activo'      => ['sometimes', 'boolean'],
            'roles'       => ['nullable', 'array'],
            'roles.*'     => ['string', 'max:50'],
        ]);

        foreach (['codigo', 'nombre', 'apellido', 'email', 'sucursal_id', 'activo'] as $field) {
            if (array_key_exists($field, $data)) {
                $usuario->{$field} = $data[$field];
            }
        }

        if (! empty($data['password'])) {
            $usuario->password = Hash::make($data['password']);
        }

        $usuario->save();

        if (array_key_exists('roles', $data)) {
            $roleSlugs = $data['roles'] ?? [];
            $roleIds   = Role::whereIn('slug', $roleSlugs)->pluck('id')->all();
            $usuario->roles()->sync($roleIds);
        }

        $usuario->load(['sucursal', 'roles']);

        return response()->json($usuario);
    }

    /**
     * DELETE /api/v1/usuarios/{usuario}
     * Elimina un usuario (o podrías cambiarlo por desactivar).
     */
    public function destroy(Request $request, User $usuario)
    {
        $this->ensureAdminGeneral($request);

        $usuario->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    /**
     * Solo ADMIN_GENERAL puede usar estos endpoints.
     */
    protected function ensureAdminGeneral(Request $request): void
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo ADMIN_GENERAL puede administrar usuarios vía API.');
        }
    }
}
