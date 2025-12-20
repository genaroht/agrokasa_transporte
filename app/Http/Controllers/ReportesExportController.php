<?php

namespace App\Http\Controllers;

use App\Exports\ResumenParaderoHorarioExport;
use App\Exports\ResumenRutaParaderoExport;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportesExportController extends Controller
{
    /**
     * Exporta a Excel el resumen Paradero x Horario.
     *
     * GET /reportes/paradero-horario/export/excel?fecha=YYYY-MM-DD&sucursal_id=X
     */
    public function paraderoHorarioExcel(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports')) {
            abort(403, 'No autorizado.');
        }

        $request->validate([
            'fecha'       => 'required|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $fecha = $request->input('fecha');
        if ($user->isAdminGeneral()) {
            $sucursalId = (int) ($request->input('sucursal_id') ?: Sucursal::where('activo', true)->value('id'));
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $sucursal = Sucursal::findOrFail($sucursalId);

        $fileName = sprintf(
            'resumen_paradero_horario_%s_%s.xlsx',
            $fecha,
            str_slug($sucursal->nombre ?? 'sucursal')
        );

        return Excel::download(new ResumenParaderoHorarioExport($fecha, $sucursalId), $fileName);
    }

    /**
     * Exporta a PDF el resumen Paradero x Horario.
     */
    public function paraderoHorarioPdf(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports')) {
            abort(403, 'No autorizado.');
        }

        $request->validate([
            'fecha'       => 'required|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $fecha = $request->input('fecha');
        if ($user->isAdminGeneral()) {
            $sucursalId = (int) ($request->input('sucursal_id') ?: Sucursal::where('activo', true)->value('id'));
        } else {
            $sucursalId = $user->sucursal_id;
        }

        // Reutilizamos el Export para construir la vista
        $export = new ResumenParaderoHorarioExport($fecha, $sucursalId);
        $view   = $export->view();

        $pdf = Pdf::loadHTML($view->render())->setPaper('A4', 'landscape');

        $sucursal = Sucursal::findOrFail($sucursalId);
        $fileName = sprintf(
            'resumen_paradero_horario_%s_%s.pdf',
            $fecha,
            str_slug($sucursal->nombre ?? 'sucursal')
        );

        return $pdf->download($fileName);
    }

    /**
     * Exporta a Excel el resumen Ruta x Paradero.
     */
    public function rutaParaderoExcel(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports')) {
            abort(403, 'No autorizado.');
        }

        $request->validate([
            'fecha'       => 'required|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $fecha = $request->input('fecha');
        if ($user->isAdminGeneral()) {
            $sucursalId = (int) ($request->input('sucursal_id') ?: Sucursal::where('activo', true)->value('id'));
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $sucursal = Sucursal::findOrFail($sucursalId);

        $fileName = sprintf(
            'resumen_ruta_paradero_%s_%s.xlsx',
            $fecha,
            str_slug($sucursal->nombre ?? 'sucursal')
        );

        return Excel::download(new ResumenRutaParaderoExport($fecha, $sucursalId), $fileName);
    }

    /**
     * Exporta a PDF el resumen Ruta x Paradero.
     */
    public function rutaParaderoPdf(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermissionTo('view_reports')) {
            abort(403, 'No autorizado.');
        }

        $request->validate([
            'fecha'       => 'required|date',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $fecha = $request->input('fecha');
        if ($user->isAdminGeneral()) {
            $sucursalId = (int) ($request->input('sucursal_id') ?: Sucursal::where('activo', true)->value('id'));
        } else {
            $sucursalId = $user->sucursal_id;
        }

        $export = new ResumenRutaParaderoExport($fecha, $sucursalId);
        $view   = $export->view();

        $pdf = Pdf::loadHTML($view->render())->setPaper('A4', 'landscape');

        $sucursal = Sucursal::findOrFail($sucursalId);
        $fileName = sprintf(
            'resumen_ruta_paradero_%s_%s.pdf',
            $fecha,
            str_slug($sucursal->nombre ?? 'sucursal')
        );

        return $pdf->download($fileName);
    }
}
