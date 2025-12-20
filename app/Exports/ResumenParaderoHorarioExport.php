<?php

namespace App\Exports;

use App\Models\Horario;
use App\Models\Paradero;
use App\Models\ProgramacionDetalle;
use App\Models\Sucursal;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ResumenParaderoHorarioExport implements FromView
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

        $paraderos = Paradero::where('sucursal_id', $this->sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $horarios = Horario::where(function ($q) {
                $q->whereNull('sucursal_id')
                  ->orWhere('sucursal_id', $this->sucursalId);
            })
            ->where('activo', true)
            ->orderBy('hora')
            ->get(['id', 'nombre', 'hora']);

        $rows = ProgramacionDetalle::selectRaw('
                programacion_detalles.paradero_id,
                programaciones.horario_id,
                SUM(programacion_detalles.personas) as total
            ')
            ->join('programaciones', 'programacion_detalles.programacion_id', '=', 'programaciones.id')
            ->where('programaciones.sucursal_id', $this->sucursalId)
            ->whereDate('programaciones.fecha', $fechaCarbon->toDateString())
            ->groupBy('programacion_detalles.paradero_id', 'programaciones.horario_id')
            ->get();

        $matriz = [];
        foreach ($rows as $r) {
            $p = $r->paradero_id;
            $h = $r->horario_id;
            if (!isset($matriz[$p])) {
                $matriz[$p] = [];
            }
            $matriz[$p][$h] = (int) $r->total;
        }

        return view('reportes.export_paradero_horario', [
            'fecha'     => $fechaCarbon,
            'sucursal'  => $sucursal,
            'paraderos' => $paraderos,
            'horarios'  => $horarios,
            'matriz'    => $matriz,
        ]);
    }
}
