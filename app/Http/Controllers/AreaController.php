<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Sucursal;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        /** @var \App\Models\Sucursal|null $sucursalActual */
        $sucursalActual = $request->attributes->get('sucursalActual');

        /*
        |--------------------------------------------------------------------------
        | API / JSON
        |--------------------------------------------------------------------------
        | Admin general: ve todas las áreas activas.
        | Resto: solo globales (sucursal_id NULL) + su sucursal.
        */
        if ($request->expectsJson() || $request->is('api/*')) {
            $query = Area::where('activo', true);

            if (!$user->isAdminGeneral()) {
                $query->where(function ($q) use ($user) {
                    $q->whereNull('sucursal_id')
                      ->orWhere('sucursal_id', $user->sucursal_id);
                });
            }

            $areas = $query->orderBy('nombre')->get();

            return response()->json($areas->map(function (Area $a) {
                return [
                    'id'          => $a->id,
                    'nombre'      => $a->nombre,
                    'codigo'      => $a->codigo,
                    'sucursal_id' => $a->sucursal_id,
                    'activo'      => (bool) $a->activo,
                    'responsable' => $a->responsable,
                ];
            }));
        }

        /*
        |--------------------------------------------------------------------------
        | VISTA WEB
        |--------------------------------------------------------------------------
        | El contexto de sucursal viene del middleware SucursalMiddleware:
        |   - $request->attributes->get('sucursalActual')
        |   - variable compartida $sucursalActual en las vistas.
        */
        $query = Area::query()->with('sucursal');

        // Filtro por estado (activo / inactivo)
        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo') === '1');
        }

        // Lógica por rol
        if ($user->isAdminGeneral()) {
            // Admin general: GLOBAL + sucursal seleccionada en el header
            if ($sucursalActual) {
                $query->where(function ($q) use ($sucursalActual) {
                    $q->whereNull('sucursal_id')
                      ->orWhere('sucursal_id', $sucursalActual->id);
                });
            }
        } else {
            // Otros usuarios: GLOBAL + su sucursal
            $sucursalId = $sucursalActual?->id ?? $user->sucursal_id;

            $query->where(function ($q) use ($sucursalId) {
                $q->whereNull('sucursal_id');
                if ($sucursalId) {
                    $q->orWhere('sucursal_id', $sucursalId);
                }
            });
        }

        $areas = $query->orderBy('nombre')->paginate(25);

        return view('catalogos.areas.index', compact('areas'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Area::class); // Policy opcional

        // Sucursales disponibles para asignar el área
        $sucursales = $user->isAdminGeneral()
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $user->sucursal_id)->get();

        $area = new Area();
        $area->activo       = true;
        $area->responsable  = null;

        return view('catalogos.areas.create', compact('area', 'sucursales'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Area::class);

        $data = $request->validate([
            'nombre'      => 'required|string|max:150',
            'codigo'      => 'nullable|string|max:50',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'responsable' => 'nullable|string|max:150', // nombre del jefe
            'activo'      => 'nullable|boolean',
        ]);

        if (!$user->isAdminGeneral()) {
            // Solo puede crear áreas globales (sucursal_id NULL) o de su sucursal
            if (!empty($data['sucursal_id']) && (int)$data['sucursal_id'] !== (int)$user->sucursal_id) {
                abort(403, 'No puede crear áreas para otra sucursal.');
            }
        }

        $data['activo'] = isset($data['activo']) ? (bool)$data['activo'] : true;

        $area = Area::create($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'area_created',
            'auditable_type' => Area::class,
            'auditable_id'   => $area->id,
            'old_values'     => null,
            'new_values'     => $area->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('catalogos.areas.index')
            ->with('status', 'Área creada correctamente.');
    }

    public function edit(Request $request, Area $area)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Area::class);

        if (!$user->isAdminGeneral()) {
            if ($area->sucursal_id && $area->sucursal_id !== $user->sucursal_id) {
                abort(403, 'No puede editar áreas de otra sucursal.');
            }
        }

        $sucursales = $user->isAdminGeneral()
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $user->sucursal_id)->get();

        return view('catalogos.areas.edit', compact('area', 'sucursales'));
    }

    public function update(Request $request, Area $area)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Area::class);

        if (!$user->isAdminGeneral()) {
            if ($area->sucursal_id && $area->sucursal_id !== $user->sucursal_id) {
                abort(403, 'No puede editar áreas de otra sucursal.');
            }
        }

        $data = $request->validate([
            'nombre'      => 'required|string|max:150',
            'codigo'      => 'nullable|string|max:50',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'responsable' => 'nullable|string|max:150',
            'activo'      => 'nullable|boolean',
        ]);

        if (!$user->isAdminGeneral()) {
            if (!empty($data['sucursal_id']) && (int)$data['sucursal_id'] !== (int)$user->sucursal_id) {
                abort(403, 'No puede mover el área a otra sucursal.');
            }
        }

        $data['activo'] = isset($data['activo']) ? (bool)$data['activo'] : false;

        $old = $area->getOriginal();
        $area->update($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'area_updated',
            'auditable_type' => Area::class,
            'auditable_id'   => $area->id,
            'old_values'     => $old,
            'new_values'     => $area->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('catalogos.areas.index')
            ->with('status', 'Área actualizada correctamente.');
    }

    public function destroy(Request $request, Area $area)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Area::class);

        if (!$user->isAdminGeneral()) {
            if ($area->sucursal_id && $area->sucursal_id !== $user->sucursal_id) {
                abort(403, 'No puede desactivar áreas de otra sucursal.');
            }
        }

        $old = $area->getOriginal();

        // Desactivamos en lugar de borrar físico
        $area->activo = false;
        $area->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'area_deactivated',
            'auditable_type' => Area::class,
            'auditable_id'   => $area->id,
            'old_values'     => $old,
            'new_values'     => $area->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('catalogos.areas.index')
            ->with('status', 'Área desactivada correctamente.');
    }
}
