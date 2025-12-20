<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class VehiculoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Vehiculo::with('sucursal')->orderBy('placa');

        if (!$user->isAdminGeneral()) {
            $query->where('sucursal_id', $user->sucursal_id);
        }

        $vehiculos = $query->paginate(50);

        return view('catalogos.vehiculos.index', compact('vehiculos'));
    }

    public function create()
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.vehiculos.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id'   => ['required', 'exists:sucursales,id'],
            'placa'         => ['required', 'string', 'unique:vehiculos,placa'],
            'codigo_interno'=> ['nullable', 'string'],
            'capacidad'     => ['required', 'integer', 'min:0'],
            'activo'        => ['boolean'],
        ]);

        Vehiculo::create($data);

        return redirect()->route('catalogos.vehiculos.index')
            ->with('status', 'Vehículo creado correctamente.');
    }

    public function edit(Vehiculo $vehiculo)
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.vehiculos.edit', compact('vehiculo', 'sucursales'));
    }

    public function update(Request $request, Vehiculo $vehiculo)
    {
        $data = $request->validate([
            'sucursal_id'   => ['required', 'exists:sucursales,id'],
            'placa'         => ['required', 'string', 'unique:vehiculos,placa,' . $vehiculo->id],
            'codigo_interno'=> ['nullable', 'string'],
            'capacidad'     => ['required', 'integer', 'min:0'],
            'activo'        => ['boolean'],
        ]);

        $vehiculo->update($data);

        return redirect()->route('catalogos.vehiculos.index')
            ->with('status', 'Vehículo actualizado.');
    }

    public function destroy(Vehiculo $vehiculo)
    {
        $vehiculo->delete();

        return redirect()->route('catalogos.vehiculos.index')
            ->with('status', 'Vehículo eliminado.');
    }
}
