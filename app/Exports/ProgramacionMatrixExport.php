<?php

namespace App\Exports;

use App\Models\Programacion;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProgramacionMatrixExport implements FromView
{
    protected Programacion $programacion;

    public function __construct(Programacion $programacion)
    {
        $this->programacion = $programacion;
    }

    /**
     * Genera la vista que será convertida a Excel.
     * Aquí armamos la misma matriz Paradero x Ruta con totales.
     */
    public function view(): View
    {
        $programacion = $this->programacion->load(['sucursal', 'area', 'horario', 'detalles.paradero', 'detalles.ruta']);

        $sucursal = $programacion->sucursal;
        $area     = $programacion->area;
        $horario  = $programacion->horario;

        // Rutas y paraderos implicados en esta programación
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

        // Matriz valores[paradero_id][ruta_id] = personas
        $valores = [];
        foreach ($programacion->detalles as $det) {
            $p = $det->paradero_id;
            $r = $det->ruta_id ?? 0;
            if (!isset($valores[$p])) {
                $valores[$p] = [];
            }
            $valores[$p][$r] = ($valores[$p][$r] ?? 0) + $det->personas;
        }

        return view('programaciones.export_matrix', compact(
            'programacion',
            'sucursal',
            'area',
            'horario',
            'paraderos',
            'rutas',
            'valores'
        ));
    }
}
