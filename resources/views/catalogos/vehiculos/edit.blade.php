@extends('layouts.app')

@section('title', 'Editar vehículo')
@section('page_title', 'Editar vehículo')

@section('content')
<div class="bg-white rounded-lg shadow p-4 max-w-xl">
    <form method="POST" action="{{ route('catalogos.vehiculos.update', $vehiculo) }}" class="space-y-3">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Sucursal</label>
            <select name="sucursal_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}" @selected(old('sucursal_id', $vehiculo->sucursal_id) == $s->id)>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Placa</label>
            <input type="text" name="placa" value="{{ old('placa', $vehiculo->placa) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Código interno</label>
            <input type="text" name="codigo_interno" value="{{ old('codigo_interno', $vehiculo->codigo_interno) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Capacidad</label>
            <input type="number" name="capacidad" value="{{ old('capacidad', $vehiculo->capacidad) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" min="0" required>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1" id="activo"
                   class="rounded border-gray-300" @checked(old('activo', $vehiculo->activo))>
            <label for="activo" class="text-xs text-gray-700">Vehículo activo</label>
        </div>

        <button type="submit"
                class="inline-flex justify-center rounded bg-[#007037] px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
            Guardar cambios
        </button>
    </form>
</div>
@endsection
