<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Horario;
use App\Models\Paradero;
use App\Models\Ruta;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class CatalogosController extends Controller
{
    protected function sucursalId(Request $request): int
    {
        $user = $request->user();

        return (int) ($request->input('sucursal_id') ?? $user->sucursal_id);
    }

    public function areas(Request $request)
    {
        $sucursalId = $this->sucursalId($request);

        $areas = Area::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json($areas);
    }

    public function horarios(Request $request)
    {
        $sucursalId = $this->sucursalId($request);

        $horarios = Horario::where(function ($q) use ($sucursalId) {
                $q->whereNull('sucursal_id')->orWhere('sucursal_id', $sucursalId);
            })
            ->where('activo', true)
            ->orderBy('hora')
            ->get();

        return response()->json($horarios);
    }

    public function paraderos(Request $request)
    {
        $sucursalId = $this->sucursalId($request);

        $paraderos = Paradero::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json($paraderos);
    }

    public function rutas(Request $request)
    {
        $sucursalId = $this->sucursalId($request);

        $rutas = Ruta::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();

        return response()->json($rutas);
    }

    public function vehiculos(Request $request)
    {
        $sucursalId = $this->sucursalId($request);

        $vehiculos = Vehiculo::where('sucursal_id', $sucursalId)
            ->where('activo', true)
            ->orderBy('placa')
            ->get();

        return response()->json($vehiculos);
    }
}
