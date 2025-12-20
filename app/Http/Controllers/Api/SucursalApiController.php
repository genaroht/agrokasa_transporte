<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SucursalApiController extends Controller
{
    /**
     * GET /api/v1/sucursales
     * Lista paginada de sucursales (solo ADMIN_GENERAL).
     */
    public function index(Request $request)
    {
        $this->ensureAdminGeneral($request);

        $query = Sucursal::query()->orderBy('codigo');

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

        // Búsqueda por texto
        if ($search = $request->input('search')) {
            $like = '%' . $search . '%';

            // En PostgreSQL usamos ilike para ignorar mayúsculas/minúsculas
            $query->where(function ($q) use ($like) {
                $q->where('codigo', 'ilike', $like)
                  ->orWhere('nombre', 'ilike', $like)
                  ->orWhere('direccion', 'ilike', $like);
            });
        }

        $sucursales = $query->paginate(50);

        return response()->json($sucursales);
    }

    /**
     * POST /api/v1/sucursales
     * Crea una nueva sucursal.
     */
    public function store(Request $request)
    {
        $this->ensureAdminGeneral($request);

        $data = $request->validate([
            'codigo'    => ['required', 'string', 'max:20', 'unique:sucursales,codigo'],
            'nombre'    => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'timezone'  => ['nullable', 'string', 'max:100'],
            'activo'    => ['sometimes', 'boolean'],
        ]);

        $sucursal = new Sucursal();
        $sucursal->codigo    = $data['codigo'];
        $sucursal->nombre    = $data['nombre'];
        $sucursal->direccion = $data['direccion'] ?? null;
        $sucursal->timezone  = $data['timezone'] ?? config('app.timezone');
        $sucursal->activo    = $data['activo'] ?? true;
        $sucursal->save();

        return response()->json($sucursal, 201);
    }

    /**
     * GET /api/v1/sucursales/{sucursal}
     * Detalle de una sucursal.
     */
    public function show(Request $request, Sucursal $sucursale)
    {
        $this->ensureAdminGeneral($request);

        // El parámetro del resource se llama {sucursale} por convención de Laravel
        return response()->json($sucursale);
    }

    /**
     * PUT/PATCH /api/v1/sucursales/{sucursal}
     * Actualiza una sucursal.
     */
    public function update(Request $request, Sucursal $sucursale)
    {
        $this->ensureAdminGeneral($request);

        $data = $request->validate([
            'codigo'    => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('sucursales', 'codigo')->ignore($sucursale->id),
            ],
            'nombre'    => ['sometimes', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'timezone'  => ['nullable', 'string', 'max:100'],
            'activo'    => ['sometimes', 'boolean'],
        ]);

        foreach (['codigo', 'nombre', 'direccion', 'timezone', 'activo'] as $field) {
            if (array_key_exists($field, $data)) {
                $sucursale->{$field} = $data[$field];
            }
        }

        $sucursale->save();

        return response()->json($sucursale);
    }

    /**
     * DELETE /api/v1/sucursales/{sucursal}
     * Elimina una sucursal.
     * (Si prefieres desactivar, aquí podrías solo poner activo = false.)
     */
    public function destroy(Request $request, Sucursal $sucursale)
    {
        $this->ensureAdminGeneral($request);

        $sucursale->delete();

        return response()->json([
            'message' => 'Sucursal eliminada correctamente.',
        ]);
    }

    /**
     * Solo ADMIN_GENERAL puede usar estos endpoints.
     */
    protected function ensureAdminGeneral(Request $request): void
    {
        $user = $request->user();

        if (! $user || ! $user->isAdminGeneral()) {
            abort(403, 'Solo ADMIN_GENERAL puede administrar sucursales vía API.');
        }
    }
}
