<?php

namespace App\Http\Controllers;

use App\Exports\ProgramacionMatrixExport;
use App\Models\Programacion;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ProgramacionExportController extends Controller
{
    /**
     * Valida que el usuario pueda exportar la programación indicada.
     */
    protected function authorizeExport(Request $request, Programacion $programacion): void
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'No autenticado.');
        }

        // Permisos tipo "slug" que definimos en RolesAndPermissionsSeeder
        if (
            !$user->hasPermissionTo('ver_reportes') &&
            !$user->hasPermissionTo('gestionar_programaciones')
        ) {
            abort(403, 'No autorizado para exportar.');
        }

        // Si no es admin_general, sólo puede exportar su propia sucursal
        if (method_exists($user, 'isAdminGeneral') && !$user->isAdminGeneral()) {
            if ($programacion->sucursal_id !== $user->sucursal_id) {
                abort(403, 'No puede exportar programaciones de otra sucursal.');
            }
        }
    }

    /**
     * Exporta la matriz Paradero x Ruta a Excel.
     */
    public function exportExcel(Request $request, Programacion $programacion)
    {
        $this->authorizeExport($request, $programacion);

        $fileName = sprintf(
            'programacion_%s_%s_%s.xlsx',
            $programacion->fecha->format('Ymd'),
            $programacion->area->codigo ?? $programacion->area->id,
            $programacion->horario->nombre
        );

        return Excel::download(new ProgramacionMatrixExport($programacion), $fileName);
    }

    /**
     * Exporta la misma matriz a PDF.
     */
    public function exportPdf(Request $request, Programacion $programacion)
    {
        $this->authorizeExport($request, $programacion);

        $programacion->load(['sucursal', 'area', 'horario', 'detalles.paradero', 'detalles.ruta']);

        $sucursal = $programacion->sucursal;
        $area     = $programacion->area;
        $horario  = $programacion->horario;

        $paraderos = $programacion->detalles
            ->pluck('paradero')
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        $rutas = $programacion->detalles
            ->pluck('ruta')
            ->unique('id')
            ->sortBy('codigo')
            ->values();

        $valores = [];
        foreach ($programacion->detalles as $det) {
            $p = $det->paradero_id;
            $r = $det->ruta_id ?? 0;
            if (!isset($valores[$p])) {
                $valores[$p] = [];
            }
            $valores[$p][$r] = ($valores[$p][$r] ?? 0) + $det->personas;
        }

        $pdf = Pdf::loadView('programaciones.export_matrix', compact(
            'programacion',
            'sucursal',
            'area',
            'horario',
            'paraderos',
            'rutas',
            'valores'
        ))->setPaper('A4', 'landscape');

        $fileName = sprintf(
            'programacion_%s_%s_%s.pdf',
            $programacion->fecha->format('Ymd'),
            $programacion->area->codigo ?? $programacion->area->id,
            $programacion->horario->nombre
        );

        return $pdf->download($fileName);
    }
}
