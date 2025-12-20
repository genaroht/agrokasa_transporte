{{-- resources/views/catalogos/areas/create.blade.php --}}
@extends('layouts.app')

@section('title','Nueva área')
@section('header','Crear área de trabajo')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm">
    <form method="POST"
          action="{{ route('catalogos.areas.store') }}"
          class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf

        <div>
            <label class="block mb-1">Nombre *</label>
            <input type="text" name="nombre" value="{{ old('nombre', $area->nombre) }}"
                   class="w-full border rounded px-2 py-1" required>
        </div>

        <div>
            <label class="block mb-1">Código (opcional)</label>
            <input type="text" name="codigo" value="{{ old('codigo', $area->codigo) }}"
                   class="w-full border rounded px-2 py-1">
        </div>

        <div>
            <label class="block mb-1">Sucursal (opcional)</label>
            <select name="sucursal_id" class="w-full border rounded px-2 py-1">
                <option value="">Global (todas las sucursales)</option>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}" @selected(old('sucursal_id', $area->sucursal_id) == $s->id)>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block mb-1">Responsable (nombre del jefe)</label>
            <input type="text" name="responsable" value="{{ old('responsable', $area->responsable) }}"
                   class="w-full border rounded px-2 py-1" placeholder="Ej: Ing. Juan Pérez">
        </div>

        <div class="flex items-center">
            <label class="inline-flex items-center mt-6">
                <input type="checkbox" name="activo" value="1"
                       class="border rounded"
                       @checked(old('activo', $area->activo) == true)>
                <span class="ml-2 text-sm">Activo</span>
            </label>
        </div>

        <div class="md:col-span-2 flex justify-end gap-2 mt-4">
            <a href="{{ route('catalogos.areas.index') }}" class="px-3 py-1 border rounded">
                Cancelar
            </a>
            <button type="submit" class="px-3 py-1 bg-[var(--primary)] text-white rounded">
                Guardar área
            </button>
        </div>
    </form>
</div>
@endsection
