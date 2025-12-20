<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paradero;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParaderoApiController extends Controller
{
    /**
     * GET /api/v1/catalogos/paraderos
     * Lista de paraderos de la sucursal del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $query = Paradero::query()
            ->where('sucursal_id', $user->sucursal_id)
            ->orderBy('nombre');

        // Filtro: activo=true/false
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

        // Filtro: solo comedores
        if ($request->boolean('solo_comedores', false)) {
            $query->where('es_comedor', true);
        }

        // Búsqueda texto libre por código / nombre / zona / referencia
        if ($search = $request->input('search')) {
            $like = '%' . $search . '%';

            // En PostgreSQL usamos ilike para case-insensitive
            $query->where(function ($q) use ($like) {
                $q->where('codigo', 'ilike', $like)
                  ->orWhere('nombre', 'ilike', $like)
                  ->orWhere('zona', 'ilike', $like)
                  ->orWhere('referencia', 'ilike', $like);
            });
        }

        $paraderos = $query->get();

        return response()->json($paraderos);
    }

    /**
     * POST /api/v1/catalogos/paraderos
     * Crea un nuevo paradero en la sucursal del usuario.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        $data = $request->validate([
            'codigo'     => [
                'required',
                'string',
                'max:20',
                Rule::unique('paraderos', 'codigo')
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre'     => ['required', 'string', 'max:150'],
            'zona'       => ['nullable', 'string', 'max:150'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'es_comedor' => ['sometimes', 'boolean'],
            'activo'     => ['sometimes', 'boolean'],
        ]);

        $paradero = new Paradero();
        $paradero->sucursal_id = $user->sucursal_id;
        $paradero->codigo      = $data['codigo'];
        $paradero->nombre      = $data['nombre'];
        $paradero->zona        = $data['zona'] ?? null;
        $paradero->referencia  = $data['referencia'] ?? null;
        $paradero->es_comedor  = $data['es_comedor'] ?? false;
        $paradero->activo      = $data['activo'] ?? true;
        $paradero->save();

        return response()->json($paradero, 201);
    }

    /**
     * GET /api/v1/catalogos/paraderos/{paradero}
     * Devuelve un paradero específico.
     */
    public function show(Request $request, Paradero $paradero)
    {
        $this->ensureCanAccessParadero($request, $paradero);

        return response()->json($paradero);
    }

    /**
     * PUT/PATCH /api/v1/catalogos/paraderos/{paradero}
     * Actualiza un paradero existente.
     */
    public function update(Request $request, Paradero $paradero)
    {
        $this->ensureCanAccessParadero($request, $paradero);

        $user = $request->user();

        $data = $request->validate([
            'codigo'     => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('paraderos', 'codigo')
                    ->ignore($paradero->id)
                    ->where('sucursal_id', $user->sucursal_id),
            ],
            'nombre'     => ['sometimes', 'string', 'max:150'],
            'zona'       => ['sometimes', 'nullable', 'string', 'max:150'],
            'referencia' => ['sometimes', 'nullable', 'string', 'max:255'],
            'es_comedor' => ['sometimes', 'boolean'],
            'activo'     => ['sometimes', 'boolean'],
        ]);

        foreach (['codigo', 'nombre', 'zona', 'referencia', 'es_comedor', 'activo'] as $field) {
            if (array_key_exists($field, $data)) {
                $paradero->{$field} = $data[$field];
            }
        }

        $paradero->save();

        return response()->json($paradero);
    }

    /**
     * DELETE /api/v1/catalogos/paraderos/{paradero}
     * Elimina un paradero.
     * (Si prefieres solo desactivar, puedes cambiar a activo=false aquí).
     */
    public function destroy(Request $request, Paradero $paradero)
    {
        $this->ensureCanAccessParadero($request, $paradero);

        $paradero->delete();

        return response()->json([
            'message' => 'Paradero eliminado correctamente.',
        ]);
    }

    /**
     * Verifica que el usuario pueda acceder a este paradero.
     * Debe ser de su sucursal o ser admin_general.
     */
    protected function ensureCanAccessParadero(Request $request, Paradero $paradero): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! $user->isAdminGeneral() && $paradero->sucursal_id !== $user->sucursal_id) {
            abort(403, 'No autorizado para acceder a este paradero.');
        }
    }
}
