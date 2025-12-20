<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReporteApiController extends Controller
{
    /**
     * Verifica autenticación y permiso de reportes.
     */
    protected function ensureAuthorized(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'No autenticado.');
        }

        // Permitimos ver reportes si tiene el permiso o si es admin_general
        if (! $user->isAdminGeneral() && ! $user->hasPermissionTo('ver_reportes')) {
            abort(Response::HTTP_FORBIDDEN, 'No autorizado para ver reportes.');
        }
    }

    /**
     * GET /api/v1/reportes/resumen/paradero-horario
     * Stub: resumen Paradero x Horario (JSON).
     */
    public function resumenParaderoHorario(Request $request)
    {
        $this->ensureAuthorized($request);

        return response()->json([
            'status'  => 'ok',
            'type'    => 'resumen_paradero_horario',
            'filters' => $request->only(['fecha', 'area_id', 'horario_id']),
            'data'    => [], // aquí luego pondremos la matriz real
        ]);
    }

    /**
     * GET /api/v1/reportes/resumen/ruta-paradero
     * Stub: resumen Ruta x Paradero (JSON).
     */
    public function resumenRutaParadero(Request $request)
    {
        $this->ensureAuthorized($request);

        return response()->json([
            'status'  => 'ok',
            'type'    => 'resumen_ruta_paradero',
            'filters' => $request->only(['fecha', 'area_id', 'horario_id']),
            'data'    => [], // aquí luego pondremos la matriz real
        ]);
    }

    /**
     * GET /api/v1/reportes/resumen/paradero-horario/excel
     * De momento solo devuelve mensaje JSON (stub).
     */
    public function resumenParaderoHorarioExcel(Request $request)
    {
        $this->ensureAuthorized($request);

        return response()->json([
            'message' => 'Exportación Excel Paradero x Horario (API) aún no implementada.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * GET /api/v1/reportes/resumen/paradero-horario/pdf
     */
    public function resumenParaderoHorarioPdf(Request $request)
    {
        $this->ensureAuthorized($request);

        return response()->json([
            'message' => 'Exportación PDF Paradero x Horario (API) aún no implementada.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * GET /api/v1/reportes/resumen/ruta-paradero/excel
     */
    public function resumenRutaParaderoExcel(Request $request)
    {
        $this->ensureAuthorized($request);

        return response()->json([
            'message' => 'Exportación Excel Ruta x Paradero (API) aún no implementada.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * GET /api/v1/reportes/resumen/ruta-paradero/pdf
     */
    public function resumenRutaParaderoPdf(Request $request)
    {
        $this->ensureAuthorized($request);

        return response()->json([
            'message' => 'Exportación PDF Ruta x Paradero (API) aún no implementada.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }
}
