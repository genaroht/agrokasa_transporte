{{-- resources/views/sucursales/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nueva sucursal')
@section('header', 'Nueva sucursal')

@section('content')
<div class="max-w-lg mx-auto bg-white shadow rounded-lg p-4 text-sm">

    <form method="POST" action="{{ route('sucursales.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Código
            </label>
            <input type="text" name="codigo"
                   value="{{ old('codigo', $sucursal->codigo) }}"
                   class="w-full border rounded-md px-3 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            <p class="text-[11px] text-slate-400 mt-1">
                Código interno de la sucursal (ej: SP01, NORTE01).
            </p>
            @error('codigo')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Nombre de la sucursal
            </label>
            <input type="text" name="nombre"
                   value="{{ old('nombre', $sucursal->nombre) }}"
                   class="w-full border rounded-md px-3 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            @error('nombre')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Dirección (opcional)
            </label>
            <input type="text" name="direccion"
                   value="{{ old('direccion', $sucursal->direccion) }}"
                   class="w-full border rounded-md px-3 py-2 text-sm
                          focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            @error('direccion')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Zona horaria
            </label>
            <select name="timezone"
                    class="w-full border rounded-md px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                @php
                    $timezones = [
                        'America/Lima'     => 'America/Lima (Perú)',
                        'America/Bogota'   => 'America/Bogota',
                        'America/Santiago' => 'America/Santiago',
                    ];
                @endphp

                @foreach($timezones as $tzValue => $tzLabel)
                    <option value="{{ $tzValue }}"
                        @selected(old('timezone', $sucursal->timezone) === $tzValue)>
                        {{ $tzLabel }}
                    </option>
                @endforeach
            </select>
            @error('timezone')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1" id="activo"
                   class="rounded border-slate-300 text-[var(--primary)] focus:ring-[var(--primary)]"
                   @checked(old('activo', $sucursal->activo))>
            <label for="activo" class="text-xs text-slate-700">
                Sucursal activa
            </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('sucursales.index') }}"
               class="px-3 py-1 rounded border border-slate-300 text-xs text-slate-700 hover:bg-slate-50">
                Cancelar
            </a>

            <button type="submit"
                    class="px-4 py-1 rounded bg-[var(--primary)] text-white text-xs font-semibold hover:bg-emerald-700">
                Guardar sucursal
            </button>
        </div>
    </form>
</div>
@endsection
