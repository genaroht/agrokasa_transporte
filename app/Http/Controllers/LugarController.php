<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Lugar;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class LugarController extends Controller
{
    public function index(Request $request)
    {
        $user             = $request->user();
        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        // --- API JSON ---
        if ($request->expectsJson() || $request->is('api/*')) {
            $query = Lugar::where('activo', true);

            if ($esAdminGral) {
                if ($request->filled('sucursal_id')) {
                    $query->where('sucursal_id', $request->input('sucursal_id'));
                } elseif ($contextSucursalId) {
                    $query->where('sucursal_id', $contextSucursalId);
                }
            } else {
                $query->where('sucursal_id', $contextSucursalId);
            }

            $lugares = $query->orderBy('nombre')->get();

            return response()->json($lugares->map(function (Lugar $lugar) {
                return [
                    'id'          => $lugar->id,
                    'nombre'      => $lugar->nombre,
                    'sucursal_id' => $lugar->sucursal_id,
                    'activo'      => (bool) $lugar->activo,
                ];
            }));
        }

        // --- Vista web ---
        $query = Lugar::query()->with('sucursal');

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

        $lugares = $query->orderBy('nombre')->paginate(25);

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        return view('catalogos.lugares.index', compact('lugares', 'sucursales'));
    }

    public function create(Request $request)
    {
        $user             = $request->user();
        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $lugar = new Lugar();
        $lugar->activo      = true;
        $lugar->sucursal_id = $contextSucursalId;

        return view('catalogos.lugares.create', compact('lugar', 'sucursales'));
    }

    public function store(Request $request)
    {
        $user             = $request->user();
        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $data = $request->validate([
            'nombre'      => 'required|string|max:150',
            'sucursal_id' => 'required|exists:sucursales,id',
            'activo'      => 'nullable|boolean',
        ]);

        if (!$esAdminGral) {
            // Forzamos sucursal desde el contexto del header
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : true;

        $lugar = Lugar::create($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'lugar_created',
            'auditable_type' => Lugar::class,
            'auditable_id'   => $lugar->id,
            'old_values'     => null,
            'new_values'     => $lugar->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.lugares.index')
            ->with('status', 'Lugar creado correctamente.');
    }

    public function edit(Request $request, Lugar $lugar)
    {
        $user             = $request->user();
        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $lugar->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede editar lugares de otra sucursal.');
        }

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        return view('catalogos.lugares.edit', compact('lugar', 'sucursales'));
    }

    public function update(Request $request, Lugar $lugar)
    {
        $user             = $request->user();
        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $lugar->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede editar lugares de otra sucursal.');
        }

        $data = $request->validate([
            'nombre'      => 'required|string|max:150',
            'sucursal_id' => 'required|exists:sucursales,id',
            'activo'      => 'nullable|boolean',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : false;

        $old = $lugar->getOriginal();
        $lugar->update($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'lugar_updated',
            'auditable_type' => Lugar::class,
            'auditable_id'   => $lugar->id,
            'old_values'     => $old,
            'new_values'     => $lugar->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.lugares.index')
            ->with('status', 'Lugar actualizado correctamente.');
    }

    public function destroy(Request $request, Lugar $lugar)
    {
        $user             = $request->user();
        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $lugar->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede desactivar lugares de otra sucursal.');
        }

        $old = $lugar->getOriginal();

        $lugar->activo = false;
        $lugar->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'lugar_deactivated',
            'auditable_type' => Lugar::class,
            'auditable_id'   => $lugar->id,
            'old_values'     => $old,
            'new_values'     => $lugar->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.lugares.index')
            ->with('status', 'Lugar desactivado correctamente.');
    }
}
