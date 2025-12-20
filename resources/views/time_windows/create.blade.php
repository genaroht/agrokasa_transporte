@extends('layouts.app')

@section('title','Nueva ventana de tiempo')
@section('header','Crear ventana de tiempo')

@section('content')
<div class="bg-white dark:bg-slate-950 rounded-lg shadow p-4 text-sm">
    <form method="POST"
          action="{{ route('timewindows.store') }}"
          class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf

        {{-- Sucursal --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Sucursal</label>
            <select name="sucursal_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}" @selected(old('sucursal_id') == $s->id)>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tipo: salida / recojo --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Tipo de ventana</label>
            <select name="tipo"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="salida" @selected(old('tipo', 'salida') === 'salida')>Salida</option>
                <option value="recojo" @selected(old('tipo') === 'recojo')>Recojo</option>
            </select>
        </div>

        {{-- Área --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Área (opcional)</label>
            <select name="area_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="">Cualquiera</option>
                @foreach($areas as $a)
                    <option value="{{ $a->id }}" @selected(old('area_id') == $a->id)>
                        {{ $a->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Horario --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Horario (opcional)</label>
            <select name="horario_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="">Cualquiera</option>
                @foreach($horarios as $h)
                    <option value="{{ $h->id }}" @selected(old('horario_id') == $h->id)>
                        {{ $h->hora }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Usuario --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Usuario (opcional)</label>
            <select name="user_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="">Cualquiera</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>
                        {{ $u->codigo }} - {{ $u->nombre_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Rol --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Rol (opcional)</label>
            <select name="role_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="">Cualquiera</option>
                @foreach($roles as $r)
                    <option value="{{ $r->id }}" @selected(old('role_id') == $r->id)>
                        {{ $r->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Fecha --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">
                Fecha de referencia (opcional)
            </label>
            <input type="date" name="fecha" value="{{ old('fecha') }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
            <p class="mt-1 text-[10px] text-gray-400">
                Solo se usa como referencia/orden. La ventana aplica todos los días mientras esté activa.
            </p>
        </div>

        {{-- Hora inicio (obligatoria) --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Hora inicio *</label>
            <input type="time" name="hora_inicio" value="{{ old('hora_inicio') }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]"
                   required>
        </div>

        {{-- Hora fin (obligatoria) --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Hora fin *</label>
            <input type="time" name="hora_fin" value="{{ old('hora_fin') }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]"
                   required>
        </div>

        {{-- Descripción --}}
        <div class="md:col-span-3">
            <label class="block text-[11px] text-gray-600 mb-1">Descripción</label>
            <input type="text" name="descripcion" value="{{ old('descripcion') }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>

        {{-- Estado inicial --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Estado inicial</label>
            <select name="estado"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="activo" @selected(old('estado', 'activo') === 'activo')>Activa</option>
                <option value="inactivo" @selected(old('estado') === 'inactivo')>Inactiva</option>
                <option value="reabierto" @selected(old('estado') === 'reabierto')>Reabierta</option>
            </select>
        </div>

        {{-- Reabierto hasta (solo si se usa estado = reabierto) --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">
                Reabierto hasta (opcional, solo si estado = reabierto)
            </label>
            <input type="datetime-local" name="reabierto_hasta"
                   value="{{ old('reabierto_hasta') }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>

        {{-- Botones --}}
        <div class="md:col-span-3 flex justify-end gap-2 mt-4">
            <a href="{{ route('timewindows.index') }}"
               class="px-3 py-1 border rounded text-xs">
                Cancelar
            </a>
            <button type="submit"
                    class="px-3 py-1 bg-[var(--primary)] text-white rounded text-xs">
                Guardar ventana
            </button>
        </div>
    </form>
</div>
@endsection
