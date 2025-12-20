@extends('layouts.app')

@section('title', 'Editar ventana de tiempo')
@section('header', 'Editar ventana de tiempo')

@section('content')
<div class="bg-white dark:bg-slate-950 rounded-lg shadow p-4 text-sm max-w-5xl">
    <form method="POST"
          action="{{ route('timewindows.update', $timewindow) }}"
          class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        @method('PUT')

        {{-- Sucursal --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Sucursal</label>
            <select name="sucursal_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}" @selected(old('sucursal_id', $timewindow->sucursal_id) == $s->id)>
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
                @php
                    $tipoActual = old('tipo', $timewindow->tipo ?? 'salida');
                @endphp
                <option value="salida" @selected($tipoActual === 'salida')>Salida</option>
                <option value="recojo" @selected($tipoActual === 'recojo')>Recojo</option>
            </select>
        </div>

        {{-- Área --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Área (opcional)</label>
            <select name="area_id"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
                <option value="">Cualquiera</option>
                @foreach($areas as $a)
                    <option value="{{ $a->id }}" @selected(old('area_id', $timewindow->area_id) == $a->id)>
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
                    <option value="{{ $h->id }}" @selected(old('horario_id', $timewindow->horario_id) == $h->id)>
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
                    <option value="{{ $u->id }}" @selected(old('user_id', $timewindow->user_id) == $u->id)>
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
                    <option value="{{ $r->id }}" @selected(old('role_id', $timewindow->role_id) == $r->id)>
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
            <input type="date" name="fecha"
                   value="{{ old('fecha', optional($timewindow->fecha)->toDateString()) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>

        {{-- Hora inicio --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Hora de inicio *</label>
            <input type="time" name="hora_inicio"
                   value="{{ old('hora_inicio', $timewindow->hora_inicio) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
        </div>

        {{-- Hora fin --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Hora de fin *</label>
            <input type="time" name="hora_fin"
                   value="{{ old('hora_fin', $timewindow->hora_fin) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
        </div>

        {{-- Descripción --}}
        <div class="md:col-span-3">
            <label class="block text-[11px] text-gray-600 mb-1">Descripción</label>
            <input type="text" name="descripcion"
                   value="{{ old('descripcion', $timewindow->descripcion) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>

        {{-- Estado --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Estado</label>
            @php
                $estadoActual = old('estado', $timewindow->estado ?? 'activo');
            @endphp
            <select name="estado"
                    class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
                <option value="activo"   @selected($estadoActual === 'activo')>Activa</option>
                <option value="inactivo" @selected($estadoActual === 'inactivo')>Inactiva</option>
                <option value="reabierto" @selected($estadoActual === 'reabierto')>Reabierta</option>
                <option value="expirado" @selected($estadoActual === 'expirado')>Expirada</option>
            </select>
        </div>

        {{-- Reabierto hasta --}}
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">
                Reabierto hasta (opcional, solo si estado = reabierto)
            </label>
            <input type="datetime-local" name="reabierto_hasta"
                   value="{{ old('reabierto_hasta', optional($timewindow->reabierto_hasta)->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>

        {{-- Botones --}}
        <div class="md:col-span-3 flex justify-end gap-2 mt-4">
            <a href="{{ route('timewindows.index') }}"
               class="px-3 py-1 border rounded text-xs">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex justify-center rounded bg-[#007037] px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                Guardar cambios
            </button>
        </div>
    </form>
</div>
@endsection
