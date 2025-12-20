<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function index()
    {
        $horarios = Horario::with('sucursal')
            ->orderBy('hora')
            ->paginate(30);

        return view('catalogos.horarios.index', compact('horarios'));
    }

    public function create()
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.horarios.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'nombre'      => ['required', 'string'],
            'hora'        => ['required'],
            'activo'      => ['boolean'],
        ]);

        Horario::create($data);

        return redirect()->route('catalogos.horarios.index')
            ->with('status', 'Horario creado correctamente.');
    }

    public function edit(Horario $horario)
    {
        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('catalogos.horarios.edit', compact('horario', 'sucursales'));
    }

    public function update(Request $request, Horario $horario)
    {
        $data = $request->validate([
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'nombre'      => ['required', 'string'],
            'hora'        => ['required'],
            'activo'      => ['boolean'],
        ]);

        $horario->update($data);

        return redirect()->route('catalogos.horarios.index')
            ->with('status', 'Horario actualizado.');
    }

    public function destroy(Horario $horario)
    {
        $horario->delete();

        return redirect()->route('catalogos.horarios.index')
            ->with('status', 'Horario eliminado.');
    }
}
