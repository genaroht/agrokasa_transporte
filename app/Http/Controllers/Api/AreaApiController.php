<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AreaApiController extends Controller
{
    /**
     * GET /api/v1/catalogos/areas
     * Lista de áreas de la sucursal del usuario.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $query = Area::query()
            ->where('sucursal_id', $user->sucursal_id)
            ->orderBy('nombre');

        // Filtro por activo (true / false / "1" / "0")
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

        // Búsqueda texto libre: código / nombre
        if ($search = $request->input('search')) {
            $like = '%' . $search . '%';

            // En PostgreSQL usamos ilike para case-insensitive
            $query->where(function ($q) use ($like) {
                $q->where('codigo', 'ilike', $like)
                  ->orWhere('nombre', 'ilike', $like);
            });
        }

        // Puedes paginar o devolver todo; aquí devuelvo todo
        $areas = $query->get();

        return response()->json($areas);
    }

    /**
     * POST /api/v1/catalogos/areas
     * Crea un área nueva en la sucursal del usuario.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:20',
                Rule::unique('areas', 'codigo')
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre' => ['required', 'string', 'max:150'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $area = new Area();
        $area->sucursal_id = $user->sucursal_id;
        $area->codigo      = $data['codigo'];
        $area->nombre      = $data['nombre'];
        $area->activo      = $data['activo'] ?? true;
        $area->save();

        return response()->json($area, 201);
    }

    /**
     * GET /api/v1/catalogos/areas/{area}
     * Muestra una área específica.
     */
    public function show(Request $request, Area $area)
    {
        $this->ensureCanAccessArea($request, $area);

        return response()->json($area);
    }

    /**
     * PUT/PATCH /api/v1/catalogos/areas/{area}
     * Actualiza un área.
     */
    public function update(Request $request, Area $area)
    {
        $this->ensureCanAccessArea($request, $area);

        $user = $request->user();

        $data = $request->validate([
            'codigo' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('areas', 'codigo')
                    ->ignore($area->id)
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre' => ['sometimes', 'string', 'max:150'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        foreach (['codigo', 'nombre', 'activo'] as $field) {
            if (array_key_exists($field, $data)) {
                $area->{$field} = $data[$field];
            }
        }

        $area->save();

        return response()->json($area);
    }

    /**
     * DELETE /api/v1/catalogos/areas/{area}
     * Elimina un área.
     * (Si más adelante prefieres desactivar, aquí podrías hacer solo activo=false.)
     */
    public function destroy(Request $request, Area $area)
    {
        $this->ensureCanAccessArea($request, $area);

        $area->delete();

        return response()->json([
            'message' => 'Área eliminada correctamente.',
        ]);
    }

    /**
     * Verifica que el usuario pueda acceder al área (misma sucursal
     * o admin_general).
     */
    protected function ensureCanAccessArea(Request $request, Area $area): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->isAdminGeneral() && $area->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No autorizado para acceder a esta área.');
        }
    }
}
