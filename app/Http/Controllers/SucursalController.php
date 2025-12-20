<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    /**
     * Lista de sucursales (solo ADMIN_GENERAL).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede gestionar sucursales.');
        }

        $query = Sucursal::query();

        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo') === '1');
        }

        $sucursales = $query
            ->orderBy('nombre')
            ->paginate(25);

        return view('sucursales.index', compact('sucursales'));
    }

    /**
     * Formulario de creación.
     */
    public function create(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede gestionar sucursales.');
        }

        $sucursal = new Sucursal();
        $sucursal->activo   = true;
        $sucursal->timezone = config('app.timezone', 'America/Lima');

        return view('sucursales.create', compact('sucursal'));
    }

    /**
     * Guarda una nueva sucursal.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede gestionar sucursales.');
        }

        $data = $request->validate([
            'codigo'    => 'required|string|max:20|unique:sucursales,codigo',
            'nombre'    => 'required|string|max:150',
            'direccion' => 'nullable|string|max:255',
            'timezone'  => 'required|string|max:100',
            'activo'    => 'nullable|boolean',
        ]);

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : true;

        $sucursal = Sucursal::create($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'sucursal_created',
            'auditable_type' => Sucursal::class,
            'auditable_id'   => $sucursal->id,
            'old_values'     => null,
            'new_values'     => $sucursal->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('sucursales.index')
            ->with('status', 'Sucursal creada correctamente.');
    }

    /**
     * Formulario de edición.
     */
    public function edit(Request $request, Sucursal $sucursal)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede gestionar sucursales.');
        }

        return view('sucursales.edit', compact('sucursal'));
    }

    /**
     * Actualiza una sucursal.
     */
    public function update(Request $request, Sucursal $sucursal)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede gestionar sucursales.');
        }

        $data = $request->validate([
            'codigo'    => 'required|string|max:20|unique:sucursales,codigo,' . $sucursal->id,
            'nombre'    => 'required|string|max:150',
            'direccion' => 'nullable|string|max:255',
            'timezone'  => 'required|string|max:100',
            'activo'    => 'nullable|boolean',
        ]);

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : false;

        $old = $sucursal->getOriginal();

        $sucursal->update($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'sucursal_updated',
            'auditable_type' => Sucursal::class,
            'auditable_id'   => $sucursal->id,
            'old_values'     => $old,
            'new_values'     => $sucursal->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('sucursales.index')
            ->with('status', 'Sucursal actualizada correctamente.');
    }

    /**
     * Desactiva una sucursal (NO se borra físicamente).
     */
    public function destroy(Request $request, Sucursal $sucursal)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede gestionar sucursales.');
        }

        $old = $sucursal->getOriginal();

        $sucursal->activo = false;
        $sucursal->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'sucursal_deactivated',
            'auditable_type' => Sucursal::class,
            'auditable_id'   => $sucursal->id,
            'old_values'     => $old,
            'new_values'     => $sucursal->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('sucursales.index')
            ->with('status', 'Sucursal desactivada correctamente.');
    }

    /**
     * Activa nuevamente una sucursal.
     */
    public function activar(Request $request, Sucursal $sucursal)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo el Administrador General puede gestionar sucursales.');
        }

        $old = $sucursal->getOriginal();

        $sucursal->activo = true;
        $sucursal->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'sucursal_activated',
            'auditable_type' => Sucursal::class,
            'auditable_id'   => $sucursal->id,
            'old_values'     => $old,
            'new_values'     => $sucursal->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('sucursales.index')
            ->with('status', 'Sucursal activada correctamente.');
    }

    /**
     * 🔹 Cambia la SUCURSAL ACTIVA para todo el sistema (selector del header).
     *    Ruta: sucursales.cambiar  (POST /cambiar-sucursal, por ejemplo)
     */
    public function cambiarSucursalActiva(Request $request)
            {
                $user = $request->user();

                // Solo Admin General puede cambiar de sucursal en el header
                if (! $user || ! method_exists($user, 'isAdminGeneral') || ! $user->isAdminGeneral()) {
                    abort(403, 'Solo el Administrador General puede cambiar de sucursal.');
                }

                $data = $request->validate([
                    'sucursal_id' => 'required|exists:sucursales,id',
                ]);

                $sucursal = Sucursal::where('activo', true)->findOrFail($data['sucursal_id']);

                // Guardamos en sesión -> el middleware leerá esto en cada request
                $request->session()->put('sucursal_activa_id', $sucursal->id);

                return back()->with('status', 'Sucursal cambiada a: ' . $sucursal->nombre);
            }

}
