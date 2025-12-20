<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ReporteController extends Controller
{
    /**
     * Timestamp para nombres de archivo.
     */
    protected function timestamp(): string
    {
        return now()->format('Ymd_His');
    }

    /**
     * Extrae la View desde la respuesta del ProgramacionController.
     */
    protected function extractView($response): View
    {
        if ($response instanceof View) {
            return $response;
        }

        if ($response instanceof \Illuminate\Http\Response && $response->original instanceof View) {
            return $response->original;
        }

        if (method_exists($response, 'getOriginalContent')) {
            $original = $response->getOriginalContent();
            if ($original instanceof View) {
                return $original;
            }
        }

        if ($response instanceof \Illuminate\Contracts\View\View) {
            return $response;
        }

        throw new RuntimeException('No se pudo extraer la vista desde la respuesta del controlador de Programaciones.');
    }

    /* ============================================================
     * 1) RESUMEN PARADERO x HORARIO
     *    - Excel: archivo .xls (HTML)
     *    - “PDF”: vista lista para imprimir (Ctrl+P → Guardar como PDF)
     * ============================================================
     */

    public function resumenParaderoHorarioExcel(
        Request $request,
        ProgramacionController $programacionController
    ) {
        $viewResponse = $programacionController->resumenParaderoHorario($request);
        $baseView     = $this->extractView($viewResponse);
        $data         = $baseView->getData();

        $fileName = 'resumen_paradero_horario_' . $this->timestamp() . '.xls';

        // Vista SOLO TABLA (sin layout), sin script de impresión
        $html = view('reportes.resumen_paradero_horario_export', $data + [
            'print' => false,
        ])->render();

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function resumenParaderoHorarioPdf(
        Request $request,
        ProgramacionController $programacionController
    ) {
        $viewResponse = $programacionController->resumenParaderoHorario($request);
        $baseView     = $this->extractView($viewResponse);
        $data         = $baseView->getData();

        // Devuelve HTML solo con la tabla y auto-lanza la impresión
        // (el usuario elige “Guardar como PDF” en el navegador).
        return view('reportes.resumen_paradero_horario_export', $data + [
            'print' => true,
        ]);
    }

    /* ============================================================
     * 2) RESUMEN RUTA x PARADERO
     * ============================================================
     */

    public function resumenRutaParaderoExcel(
        Request $request,
        ProgramacionController $programacionController
    ) {
        $viewResponse = $programacionController->resumenRutaParadero($request);
        $baseView     = $this->extractView($viewResponse);
        $data         = $baseView->getData();

        $fileName = 'resumen_ruta_paradero_' . $this->timestamp() . '.xls';

        $html = view('reportes.resumen_ruta_paradero_export', $data + [
            'print' => false,
        ])->render();

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function resumenRutaParaderoPdf(
        Request $request,
        ProgramacionController $programacionController
    ) {
        $viewResponse = $programacionController->resumenRutaParadero($request);
        $baseView     = $this->extractView($viewResponse);
        $data         = $baseView->getData();

        return view('reportes.resumen_ruta_paradero_export', $data + [
            'print' => true,
        ]);
    }

    /* ============================================================
     * 3) RUTA / LOTE / COMEDOR
     * ============================================================
     */

    public function rutasLotesExcel(
        Request $request,
        ProgramacionController $programacionController
    ) {
        $viewResponse = $programacionController->reporteRutaLoteCom($request);
        $baseView     = $this->extractView($viewResponse);
        $data         = $baseView->getData();

        $fileName = 'rutas_lotes_' . $this->timestamp() . '.xls';

        $html = view('reportes.reporte_ruta_lote_com', $data + [
            'print' => false,
        ])->render();

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function rutasLotesPdf(
        Request $request,
        ProgramacionController $programacionController
    ) {
        $viewResponse = $programacionController->reporteRutaLoteCom($request);
        $baseView     = $this->extractView($viewResponse);
        $data         = $baseView->getData();

        return view('reportes.reporte_ruta_lote_com', $data + [
            'print' => true,
        ]);
    }
}
