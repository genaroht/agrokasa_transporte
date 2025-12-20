<?php

namespace App\Http\Controllers;

use App\Models\Ruta;
use App\Models\RutaLote;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class RutaController extends Controller
{
    public function index(Request $request)
    {
        $user               = $request->user();
        $sucursalActual     = $request->attributes->get('sucursalActual');
        $contextSucursalId  = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral        = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        // --- API / JSON ---
        if ($request->expectsJson() || $request->is('api/*')) {
            $query = Ruta::where('activo', true)
                ->with(['vehiculo', 'lotes']);

            if ($esAdminGral) {
                if ($request->filled('sucursal_id')) {
                    $query->where('sucursal_id', $request->input('sucursal_id'));
                } elseif ($contextSucursalId) {
                    $query->where('sucursal_id', $contextSucursalId);
                }
            } else {
                $query->where('sucursal_id', $contextSucursalId);
            }

            $rutas = $query->orderBy('codigo')->get();

            return response()->json($rutas->map(function (Ruta $r) {
                return [
                    'id'           => $r->id,
                    'codigo'       => $r->codigo,
                    'nombre'       => $r->nombre,
                    'sucursal_id'  => $r->sucursal_id,
                    'id_vehiculo'  => $r->id_vehiculo,
                    'vehiculo'     => $r->vehiculo ? [
                        'id'                 => $r->vehiculo->id,
                        'placa'              => $r->vehiculo->placa,
                        'capacidad_personas' => $r->vehiculo->capacidad_personas,
                    ] : null,
                    'activo'       => (bool) $r->activo,
                    // NUEVO: lotes y comedores de la ruta
                    'lotes'        => $r->lotes->map(function ($l) {
                        return [
                            'id'        => $l->id,
                            'nombre'    => $l->nombre,
                            'comedores' => $l->comedores_list,
                        ];
                    })->values(),
                ];
            }));
        }

        // --- Vista web ---
        $query = Ruta::query()->with(['sucursal', 'vehiculo']);

        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo') === '1');
        }

        if ($esAdminGral) {
            if ($contextSucursalId) {
                $query->where('sucursal_id', $contextSucursalId);
            }
        } else {
            $query->where('sucursal_id', $contextSucursalId);
        }

        $rutas = $query->orderBy('codigo')->paginate(25);

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        // Vehículos solo de la sucursal actual (para formularios/filtros)
        $vehiculos = Vehiculo::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->where('sucursal_id', $contextSucursalId);
            })
            ->orderBy('placa')
            ->get();

        return view('catalogos.rutas.index', compact('rutas', 'sucursales', 'vehiculos'));
    }

    public function create(Request $request)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Ruta::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $vehiculos = Vehiculo::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->where('sucursal_id', $contextSucursalId);
            })
            ->orderBy('placa')
            ->get();

        $ruta = new Ruta();
        $ruta->activo      = true;
        $ruta->sucursal_id = $contextSucursalId;

        return view('catalogos.rutas.create', compact('ruta', 'sucursales', 'vehiculos'));
    }

    public function store(Request $request)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Ruta::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $data = $request->validate([
            'codigo'             => 'required|string|max:20',
            'nombre'             => 'required|string|max:150',
            'sucursal_id'        => 'required|exists:sucursales,id',
            'id_vehiculo'        => 'nullable|exists:vehiculos,id',
            'activo'             => 'nullable|boolean',
            // NUEVO: lotes y comedores
            'lotes'              => 'nullable|array',
            'lotes.*.nombre'     => 'nullable|string|max:100',
            'lotes.*.comedores'  => 'nullable|string|max:1000',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : true;

        $lotesInput = $data['lotes'] ?? [];
        unset($data['lotes']);

        $ruta = Ruta::create($data);

        // Guardar lotes/comedores
        foreach ($lotesInput as $loteData) {
            $nombreLote = trim($loteData['nombre'] ?? '');
            if ($nombreLote === '') {
                continue;
            }

            RutaLote::create([
                'ruta_id'   => $ruta->id,
                'nombre'    => $nombreLote,
                'comedores' => $loteData['comedores'] ?? null,
            ]);
        }

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'ruta_created',
            'auditable_type' => Ruta::class,
            'auditable_id'   => $ruta->id,
            'old_values'     => null,
            'new_values'     => $ruta->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.rutas.index')
            ->with('status', 'Ruta creada correctamente.');
    }

    public function edit(Request $request, Ruta $ruta)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Ruta::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $ruta->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede editar rutas de otra sucursal.');
        }

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $vehiculos = Vehiculo::where('activo', true)
            ->when($contextSucursalId, function ($q) use ($contextSucursalId) {
                $q->where('sucursal_id', $contextSucursalId);
            })
            ->orderBy('placa')
            ->get();

        $ruta->load('lotes');

        return view('catalogos.rutas.edit', compact('ruta', 'sucursales', 'vehiculos'));
    }

    public function update(Request $request, Ruta $ruta)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Ruta::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $ruta->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede editar rutas de otra sucursal.');
        }

        $data = $request->validate([
            'codigo'             => 'required|string|max:20',
            'nombre'             => 'required|string|max:150',
            'sucursal_id'        => 'required|exists:sucursales,id',
            'id_vehiculo'        => 'nullable|exists:vehiculos,id',
            'activo'             => 'nullable|boolean',
            // NUEVO: lotes y comedores
            'lotes'              => 'nullable|array',
            'lotes.*.nombre'     => 'nullable|string|max:100',
            'lotes.*.comedores'  => 'nullable|string|max:1000',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : false;

        $lotesInput = $data['lotes'] ?? [];
        unset($data['lotes']);

        $old = $ruta->getOriginal();
        $ruta->update($data);

        // Actualizar lotes: por simplicidad, eliminar todos y recrear
        RutaLote::where('ruta_id', $ruta->id)->delete();

        foreach ($lotesInput as $loteData) {
            $nombreLote = trim($loteData['nombre'] ?? '');
            if ($nombreLote === '') {
                continue;
            }

            RutaLote::create([
                'ruta_id'   => $ruta->id,
                'nombre'    => $nombreLote,
                'comedores' => $loteData['comedores'] ?? null,
            ]);
        }

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'ruta_updated',
            'auditable_type' => Ruta::class,
            'auditable_id'   => $ruta->id,
            'old_values'     => $old,
            'new_values'     => $ruta->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.rutas.index')
            ->with('status', 'Ruta actualizada correctamente.');
    }

    public function destroy(Request $request, Ruta $ruta)
    {
        $user              = $request->user();
        $this->authorize('manageCatalogs', Ruta::class);

        $sucursalActual    = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral       = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $ruta->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede desactivar rutas de otra sucursal.');
        }

        $old = $ruta->getOriginal();
        $ruta->activo = false;
        $ruta->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'ruta_deactivated',
            'auditable_type' => Ruta::class,
            'auditable_id'   => $ruta->id,
            'old_values'     => $old,
            'new_values'     => $ruta->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.rutas.index')
            ->with('status', 'Ruta desactivada correctamente.');
    }
}
