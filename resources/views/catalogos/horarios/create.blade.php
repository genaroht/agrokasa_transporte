@extends('layouts.app')

@section('title', 'Nuevo horario')
@section('page_title', 'Crear horario')

@section('content')
<div class="bg-white rounded-lg shadow p-4 max-w-xl">
    <form method="POST" action="{{ route('catalogos.horarios.store') }}" class="space-y-3">
        @csrf

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                Sucursal (vacío = global)
            </label>
            <select name="sucursal_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="">Global</option>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}" @selected(old('sucursal_id', $horario->sucursal_id) == $s->id)>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Tipo de horario
                </label>
                <select name="tipo"
                        class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                    <option value="RECOJO" @selected(old('tipo', $horario->tipo) === 'RECOJO')>Recojo</option>
                    <option value="SALIDA" @selected(old('tipo', $horario->tipo) === 'SALIDA')>Salida</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                Nombre del horario
            </label>
            <input type="text" name="nombre" value="{{ old('nombre') }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
            <p class="mt-1 text-[11px] text-gray-500">
                Ejemplo: <strong>"Turno día 06:30 a 15:30"</strong>.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Hora inicio
                </label>
                <input type="time" name="hora" value="{{ old('hora') }}"
                       class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Hora fin
                </label>
                <input type="time" name="hora_fin" value="{{ old('hora_fin') }}"
                       class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <p class="mt-1 text-[11px] text-gray-500">
                    Se mostrará como <strong>DE hh:mm a hh:mm</strong>.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1" id="activo"
                   class="rounded border-gray-300" checked>
            <label for="activo" class="text-xs text-gray-700">Horario activo</label>
        </div>

        <button type="submit"
                class="inline-flex justify-center rounded bg-[#007037] px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
            Guardar horario
        </button>
    </form>
</div>
@endsection
