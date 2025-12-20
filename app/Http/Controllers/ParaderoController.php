<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Lugar;
use App\Models\Paradero;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class ParaderoController extends Controller
{
    public function index(Request $request)
    {
        $user             = $request->user();
        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        /*
         * --- API / JSON (para app móvil, etc.) ---
         */
        if ($request->expectsJson() || $request->is('api/*')) {
            $query = Paradero::with('lugar')
                ->where('activo', true);

            if ($esAdminGral) {
                if ($request->filled('sucursal_id')) {
                    $query->where('sucursal_id', $request->input('sucursal_id'));
                } elseif ($contextSucursalId) {
                    $query->where('sucursal_id', $contextSucursalId);
                }
            } else {
                $query->where('sucursal_id', $contextSucursalId);
            }

            // Ordenar por lugar y luego por nombre
            $paraderos = $query
                ->orderByRaw('COALESCE(lugar_id, 0)')
                ->orderBy('nombre')
                ->get();

            return response()->json($paraderos->map(function (Paradero $p) {
                return [
                    'id'           => $p->id,
                    'nombre'       => $p->nombre,
                    'codigo'       => $p->codigo,
                    'lugar_id'     => $p->lugar_id,
                    'lugar'        => $p->lugar->nombre ?? null, // nombre del lugar
                    'referencia'   => $p->direccion,             // referencia opcional
                    'sucursal_id'  => $p->sucursal_id,
                    'activo'       => (bool) $p->activo,
                    'etiqueta'     => $p->etiqueta_completa,
                ];
            }));
        }

        /*
         * --- Vista web ---
         */
        $query = Paradero::query()
            ->with(['sucursal', 'lugar']);

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

        $paraderos = $query
            ->orderByRaw('COALESCE(lugar_id, 0)')
            ->orderBy('nombre')
            ->paginate(25);

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        return view('catalogos.paraderos.index', compact('paraderos', 'sucursales'));
    }

    public function create(Request $request)
    {
        $user             = $request->user();
        $this->authorize('manageCatalogs', Paradero::class);

        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $paradero = new Paradero();
        $paradero->activo      = true;
        $paradero->sucursal_id = $contextSucursalId;

        // Lugares activos para la sucursal del contexto
        $lugaresQuery = Lugar::where('activo', true)->orderBy('nombre');

        if ($contextSucursalId) {
            $lugaresQuery->where('sucursal_id', $contextSucursalId);
        }

        $lugares = $lugaresQuery->get();

        return view('catalogos.paraderos.create', compact('paradero', 'sucursales', 'lugares'));
    }

    public function store(Request $request)
    {
        $user             = $request->user();
        $this->authorize('manageCatalogs', Paradero::class);

        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        $data = $request->validate([
            'nombre'      => 'required|string|max:150',
            'codigo'      => 'nullable|string|max:50',
            'lugar_id'    => 'required|exists:lugares,id',
            'direccion'   => 'nullable|string|max:255', // referencia opcional
            'sucursal_id' => 'required|exists:sucursales,id',
            'activo'      => 'nullable|boolean',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : true;

        $paradero = Paradero::create($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'paradero_created',
            'auditable_type' => Paradero::class,
            'auditable_id'   => $paradero->id,
            'old_values'     => null,
            'new_values'     => $paradero->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.paraderos.index')
            ->with('status', 'Paradero creado correctamente.');
    }

    public function edit(Request $request, Paradero $paradero)
    {
        $user             = $request->user();
        $this->authorize('manageCatalogs', Paradero::class);

        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $paradero->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede editar paraderos de otra sucursal.');
        }

        $sucursales = $esAdminGral
            ? Sucursal::where('activo', true)->orderBy('nombre')->get()
            : Sucursal::where('id', $contextSucursalId)->get();

        $lugaresQuery = Lugar::where('activo', true)->orderBy('nombre');

        if ($contextSucursalId) {
            $lugaresQuery->where('sucursal_id', $contextSucursalId);
        }

        $lugares = $lugaresQuery->get();

        return view('catalogos.paraderos.edit', compact('paradero', 'sucursales', 'lugares'));
    }

    public function update(Request $request, Paradero $paradero)
    {
        $user             = $request->user();
        $this->authorize('manageCatalogs', Paradero::class);

        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $paradero->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede editar paraderos de otra sucursal.');
        }

        $data = $request->validate([
            'nombre'      => 'required|string|max:150',
            'codigo'      => 'nullable|string|max:50',
            'lugar_id'    => 'required|exists:lugares,id',
            'direccion'   => 'nullable|string|max:255', // referencia opcional
            'sucursal_id' => 'required|exists:sucursales,id',
            'activo'      => 'nullable|boolean',
        ]);

        if (!$esAdminGral) {
            $data['sucursal_id'] = $contextSucursalId;
        }

        $data['activo'] = isset($data['activo']) ? (bool) $data['activo'] : false;

        $old = $paradero->getOriginal();

        $paradero->update($data);

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'paradero_updated',
            'auditable_type' => Paradero::class,
            'auditable_id'   => $paradero->id,
            'old_values'     => $old,
            'new_values'     => $paradero->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.paraderos.index')
            ->with('status', 'Paradero actualizado correctamente.');
    }

    public function destroy(Request $request, Paradero $paradero)
    {
        $user             = $request->user();
        $this->authorize('manageCatalogs', Paradero::class);

        $sucursalActual   = $request->attributes->get('sucursalActual');
        $contextSucursalId = $sucursalActual?->id ?? $user->sucursal_id;
        $esAdminGral      = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

        if (!$esAdminGral && (int) $paradero->sucursal_id !== (int) $contextSucursalId) {
            abort(403, 'No puede desactivar paraderos de otra sucursal.');
        }

        $old = $paradero->getOriginal();

        $paradero->activo = false;
        $paradero->save();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'paradero_deactivated',
            'auditable_type' => Paradero::class,
            'auditable_id'   => $paradero->id,
            'old_values'     => $old,
            'new_values'     => $paradero->toArray(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return redirect()->route('catalogos.paraderos.index')
            ->with('status', 'Paradero desactivado correctamente.');
    }
}
