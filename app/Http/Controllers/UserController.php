<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sucursal;
use App\Models\Area;
use App\Models\Role;
use App\Models\Horario;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listado de usuarios con filtros.
     * Respeta la sucursal seleccionada en el header (session('sucursal_actual_id')).
     */
    public function index(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = $request->user();

        $query = User::with(['sucursal', 'roles']);

        // =========================
        // 1) CONTEXTO DE SUCURSAL
        // =========================
        $sucursalContextoId = $this->resolveSucursalIdForListing($request);

        if ($authUser && $authUser->isAdminGeneral()) {
            // Admin General:
            // - Si viene sucursal_id en el filtro, esa tiene prioridad.
            // - Si no viene filtro pero hay sucursal en el header, se usa esa.
            if ($request->filled('sucursal_id')) {
                $query->where('sucursal_id', (int) $request->input('sucursal_id'));
            } elseif ($sucursalContextoId) {
                $query->where('sucursal_id', $sucursalContextoId);
            }
        } elseif ($sucursalContextoId) {
            // No es Admin General: siempre su sucursal (o la del header).
            $query->where('sucursal_id', $sucursalContextoId);
        }

        // =========================
        // 2) FILTRO POR ROL (slug)
        // =========================
        if ($request->filled('rol_slug')) {
            $rolSlug = $request->input('rol_slug');

            $query->whereHas('roles', function ($q) use ($rolSlug) {
                $q->where('slug', $rolSlug);
            });
        }

        // =========================
        // 3) FILTRO POR ESTADO
        // =========================
        if ($request->filled('estado')) {
            if ($request->input('estado') === '1') {
                $query->where('activo', true);
            } elseif ($request->input('estado') === '0') {
                $query->where('activo', false);
            }
        }

        // =========================
        // 4) BÚSQUEDA GENERAL
        // =========================
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('codigo', 'ILIKE', '%' . $search . '%')
                    ->orWhere('nombre', 'ILIKE', '%' . $search . '%')
                    ->orWhere('apellido', 'ILIKE', '%' . $search . '%')
                    ->orWhere('email', 'ILIKE', '%' . $search . '%');
            });
        }

        $users = $query
            ->orderBy('codigo')
            ->paginate(20)
            ->appends($request->query());

        // Listas para filtros
        $sucursales = Sucursal::orderBy('nombre')->get();
        $roles      = Role::orderBy('nombre')->get();

        if ($request->wantsJson()) {
            return response()->json($users);
        }

        return view('usuarios.index', [
            'users'              => $users,
            'sucursales'         => $sucursales,
            'roles'              => $roles,
            'sucursalContextoId' => $sucursalContextoId,
            'authUser'           => $authUser,
        ]);
    }

    /**
     * Formulario de creación de usuario.
     */
    public function create()
    {
        $user = auth()->user();

        // SOLO roles activos se pueden asignar
        $roles = Role::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $sucursales = Sucursal::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $areas = Area::where('activo', true)
            ->when(!$user->isAdminGeneral(), function ($q) use ($user) {
                $q->whereNull('sucursal_id')
                    ->orWhere('sucursal_id', $user->sucursal_id);
            })
            ->orderBy('nombre')
            ->get();

        $horarios = Horario::where('activo', true)
            ->orderBy('hora')
            ->get();

        // Para el formulario create.blade.php
        $usuario = new User();

        return view('usuarios.create', compact(
            'roles',
            'sucursales',
            'areas',
            'horarios',
            'usuario'
        ));
    }

    /**
     * Guarda un usuario nuevo.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'       => 'required|string|max:20|unique:users,codigo',
            'nombre'       => 'required|string|max:100',
            'apellido'     => 'nullable|string|max:100',
            'email'        => 'nullable|email|unique:users,email',
            'sucursal_id'  => 'nullable|integer|exists:sucursales,id',
            'area_id'      => 'nullable|integer|exists:areas,id',
            'password'     => [
                'required',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'roles'        => 'required|array',
            'roles.*'      => 'integer|exists:roles,id',
            'horarios'     => 'nullable|array',
            'horarios.*'   => 'integer|exists:horarios,id',
            'activo'       => 'sometimes|boolean',
        ], [
            'password.regex' => 'La contraseña debe tener al menos una mayúscula, una minúscula y un número.',
        ]);

        $user = new User();
        $user->codigo      = $data['codigo'];
        $user->nombre      = $data['nombre'];
        $user->apellido    = $data['apellido'] ?? null;
        $user->email       = $data['email'] ?? null;
        $user->sucursal_id = $data['sucursal_id'] ?? null;
        $user->area_id     = $data['area_id'] ?? null;
        $user->password    = Hash::make($data['password']);
        $user->activo      = $request->boolean('activo', true);
        $user->save();

        // Sincronizar roles
        $user->roles()->sync($data['roles']);

        // Sincronizar horarios permitidos
        $user->horarios()->sync($data['horarios'] ?? []);

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'user_created',
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'old_values'     => null,
            'new_values'     => [
                'codigo'      => $user->codigo,
                'nombre'      => $user->nombre,
                'sucursal_id' => $user->sucursal_id,
                'area_id'     => $user->area_id,
                'activo'      => $user->activo,
            ],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    /**
     * Formulario de edición de usuario.
     */
    public function edit(User $usuario)
    {
        $sucursales = Sucursal::orderBy('nombre')->get();
        $areas      = Area::orderBy('nombre')->get();

        // Para edición mostramos todos los roles (activos e inactivos).
        $roles = Role::orderBy('nombre')->get();

        $horarios = Horario::where('activo', true)
            ->orderBy('hora')
            ->get();

        // IDs de horarios actualmente asignados al usuario
        $selectedHorarios = $usuario->horarios()
            ->pluck('horarios.id')
            ->toArray();

        return view('usuarios.edit', [
            'usuario'          => $usuario,
            'sucursales'       => $sucursales,
            'areas'            => $areas,
            'roles'            => $roles,
            'horarios'         => $horarios,
            'selectedHorarios' => $selectedHorarios,
        ]);
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(Request $request, User $usuario)
    {
        $data = $request->validate([
            'codigo'       => 'required|string|max:20|unique:users,codigo,' . $usuario->id,
            'nombre'       => 'required|string|max:100',
            'apellido'     => 'nullable|string|max:100',
            'email'        => 'nullable|email|unique:users,email,' . $usuario->id,
            'sucursal_id'  => 'nullable|integer|exists:sucursales,id',
            'area_id'      => 'nullable|integer|exists:areas,id',
            'password'     => [
                'nullable',
                'string',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'roles'        => 'required|array',
            'roles.*'      => 'integer|exists:roles,id',
            'horarios'     => 'nullable|array',
            'horarios.*'   => 'integer|exists:horarios,id',
            'activo'       => 'sometimes|boolean',
        ], [
            'password.regex' => 'La nueva contraseña debe tener al menos una mayúscula, una minúscula y un número.',
        ]);

        $old = $usuario->only([
            'codigo',
            'nombre',
            'apellido',
            'email',
            'sucursal_id',
            'area_id',
            'activo',
        ]);

        $usuario->codigo      = $data['codigo'];
        $usuario->nombre      = $data['nombre'];
        $usuario->apellido    = $data['apellido'] ?? null;
        $usuario->email       = $data['email'] ?? null;
        $usuario->sucursal_id = $data['sucursal_id'] ?? null;
        $usuario->area_id     = $data['area_id'] ?? null;
        $usuario->activo      = $request->boolean('activo', $usuario->activo);

        if (!empty($data['password'])) {
            $usuario->password = Hash::make($data['password']);
        }

        $usuario->save();

        // Actualizar roles
        $usuario->roles()->sync($data['roles']);

        // Actualizar horarios permitidos
        $usuario->horarios()->sync($data['horarios'] ?? []);

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'user_updated',
            'auditable_type' => User::class,
            'auditable_id'   => $usuario->id,
            'old_values'     => $old,
            'new_values'     => $usuario->only([
                'codigo',
                'nombre',
                'apellido',
                'email',
                'sucursal_id',
                'area_id',
                'activo',
            ]),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * Desactiva un usuario (no lo borra).
     */
    public function destroy(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return back()->withErrors('No puedes desactivar tu propio usuario.');
        }

        $old = $usuario->toArray();

        $usuario->activo = false;
        $usuario->save();

        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'user_deactivated',
            'auditable_type' => User::class,
            'auditable_id'   => $usuario->id,
            'old_values'     => $old,
            'new_values'     => ['activo' => false],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('status', 'Usuario desactivado.');
    }

    /**
     * Determina la sucursal que se debe usar para listados,
     * respetando la selección del header.
     *
     * - Para Admin General:
     *      Usa session('sucursal_actual_id') si existe; si no, opcional.
     * - Para otros usuarios:
     *      Siempre su propia sucursal_id.
     */
    protected function resolveSucursalIdForListing(Request $request): ?int
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if ($user->isAdminGeneral()) {
            $fromHeader = session('sucursal_actual_id');
            if ($fromHeader) {
                return (int) $fromHeader;
            }

            return $user->sucursal_id ?: null;
        }

        return $user->sucursal_id ?: null;
    }
}
