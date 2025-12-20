<?php

namespace App\Http\Controllers;

use App\Models\Vehiculo;
use App\Models\Sucursal;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // API / JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            $query = Vehiculo::where('activo', true);

            if (!$user->isAdminGeneral()) {
                $query->where('sucursal_id', $user->sucursal_id);
            } else {
                if ($request->filled('sucursal_id')) {
                    $query->where('sucursal_id', $request->input('sucursal_id'));
                }
            }

            $vehiculos = $query->orderBy('placa')->get();

            return response()->json($vehiculos->map(function ($v) {
                return [
                    'id'                 => $v->id,
                    'placa'              => $v->placa,
                    'descripcion'        => $v->descripcion,
                    'capacidad_personas' => $v->capacidad_personas,
                    'tipo'               => $v->tipo,
                    'sucursal_id'        => $v->sucursal_id,
                    'activo'             => (bool)$v->activo,
                ];
            }));
        }

        // WEB
        $query = Vehiculo::query()->with('sucursal');

        if ($request->filled('sucursal_id')) {
            $query->where('sucursal_id', $request->input('sucursal_id'));
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo') === '1');
        }

        if (!$user->isAdminGeneral()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $vehiculos = $query->orderBy('placa')->paginate(25);

        $sucursales = $user->isAdminGeneral()
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $user->sucursal_id)->get();

        return view('catalogos.vehiculos.index', compact('vehiculos', 'sucursales'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Vehiculo::class);

        $sucursales = $user->isAdminGeneral()
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $user->sucursal_id)->get();

        $vehiculo = new Vehiculo();
        $vehiculo->activo = true;

        return view('catalogos.vehiculos.create', compact('vehiculo', 'sucursales'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Vehiculo::class);

        $data = $request->validate([
            'placa'              => 'required|string|max:20',
            'descripcion'        => 'nullable|string|max:255',
            'capacidad_personas' => 'required|integer|min:1|max:100',
            'tipo'               => 'nullable|string|max:50',
            'sucursal_id'        => 'required|exists:sucursales,id',
            'activo'             => 'nullable|boolean',
        ]);

        if (!$user->isAdminGeneral() && (int)$data['sucursal_id'] !== (int)$user->sucursal_id) {
            abort(403, 'No puede registrar vehículos para otra sucursal.');
        }

        $data['activo'] = $request->boolean('activo', true);

        $vehiculo = Vehiculo::create($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'vehiculo_created',
            'auditable_type' => Vehiculo::class,
            'auditable_id'   => $vehiculo->id,
            'old_values'     => null,
            'new_values'     => $vehiculo->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('catalogos.vehiculos.index')
            ->with('status', 'Vehículo creado correctamente.');
    }

    public function edit(Request $request, Vehiculo $vehiculo)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Vehiculo::class);

        if (!$user->isAdminGeneral() && $vehiculo->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No puede editar vehículos de otra sucursal.');
        }

        $sucursales = $user->isAdminGeneral()
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $user->sucursal_id)->get();

        return view('catalogos.vehiculos.edit', compact('vehiculo', 'sucursales'));
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Vehiculo::class);

        if (!$user->isAdminGeneral() && $vehiculo->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No puede editar vehículos de otra sucursal.');
        }

        $data = $request->validate([
            'placa'              => 'required|string|max:20',
            'descripcion'        => 'nullable|string|max:255',
            'capacidad_personas' => 'required|integer|min:1|max:100',
            'tipo'               => 'nullable|string|max:50',
            'sucursal_id'        => 'required|exists:sucursales,id',
            'activo'             => 'nullable|boolean',
        ]);

        if (!$user->isAdminGeneral() && (int)$data['sucursal_id'] !== (int)$user->sucursal_id) {
            abort(403, 'No puede mover el vehículo a otra sucursal.');
        }

        $data['activo'] = $request->boolean('activo', false);

        $old = $vehiculo->getOriginal();
        $vehiculo->update($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'vehiculo_updated',
            'auditable_type' => Vehiculo::class,
            'auditable_id'   => $vehiculo->id,
            'old_values'     => $old,
            'new_values'     => $vehiculo->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('catalogos.vehiculos.index')
            ->with('status', 'Vehículo actualizado correctamente.');
    }

    public function destroy(Request $request, Vehiculo $vehiculo)
    {
        $user = $request->user();
        $this->authorize('manageCatalogs', Vehiculo::class);

        if (!$user->isAdminGeneral() && $vehiculo->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No puede desactivar vehículos de otra sucursal.');
        }

        $old = $vehiculo->getOriginal();
        $vehiculo->activo = false;
        $vehiculo->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'vehiculo_deactivated',
            'auditable_type' => Vehiculo::class,
            'auditable_id'   => $vehiculo->id,
            'old_values'     => $old,
            'new_values'     => $vehiculo->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()
            ->route('catalogos.vehiculos.index')
            ->with('status', 'Vehículo desactivado correctamente.');
    }
}
