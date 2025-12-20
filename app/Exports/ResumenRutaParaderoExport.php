<?php

namespace App\Exports;

use App\Models\Paradero;
use App\Models\ProgramacionDetalle;
use App\Models\Ruta;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ResumenRutaParaderoExport implements FromView
{
    protected string $fecha;
    protected int $sucursalId;

    public function __construct(string $fecha, int $sucursalId)
    {
        $this->fecha = $fecha;
        $this->sucursalId = $sucursalId;
    }

    public function view(): View
    {
        $fechaCarbon = Carbon::parse($this->fecha);
        $sucursal    = Sucursal::findOrFail($this->sucursalId);

        $rutas = Ruta::where('sucursal_id', $this->sucursalId)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre']);

        $paraderos = Paradero::where('sucursal_id', $this->sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.ruta_id,
                programacion_detalles.paradero_id,
                SUM(programacion_detalles.personas) as total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $this->sucursalId)
            ->whereDate('programaciones.fecha', $fechaCarbon->toDateString())
            ->groupBy('programacion_detalles.ruta_id', 'programacion_detalles.paradero_id')
            ->get();

        $matriz = [];
        foreach ($rows as $r) {
            $rId = $r->ruta_id ?? 0;
            $pId = $r->paradero_id;
            if (!isset($matriz[$rId])) {
                $matriz[$rId] = [];
            }
            $matriz[$rId][$pId] = (int) $r->total;
        }

        return view('reportes.export_ruta_paradero', [
            'fecha'     => $fechaCarbon,
            'sucursal'  => $sucursal,
            'rutas'     => $rutas,
            'paraderos' => $paraderos,
            'matriz'    => $matriz,
        ]);
    }
}
