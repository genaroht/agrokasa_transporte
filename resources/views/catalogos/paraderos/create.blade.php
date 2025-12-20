{{-- resources/views/catalogos/paraderos/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nuevo paradero')
@section('header', 'Nuevo paradero')

@section('content')
<div class="max-w-xl mx-auto bg-white shadow rounded-lg p-4 text-sm">

    <form method="POST" action="{{ route('catalogos.paraderos.store') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Sucursal
            </label>
            <select name="sucursal_id"
                    class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}"
                        {{ old('sucursal_id', $s->id) == $s->id ? 'selected' : '' }}>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Nombre del paradero
            </label>
            <input type="text"
                   name="nombre"
                   value="{{ old('nombre') }}"
                   class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Código (opcional)
            </label>
            <input type="text"
                   name="codigo"
                   value="{{ old('codigo') }}"
                   class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Lugar (agrupa varios paraderos)
            </label>
            <select name="lugar_id"
                    class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                <option value="">Seleccione un lugar…</option>
                @foreach($lugares as $lugar)
                    <option value="{{ $lugar->id }}"
                        {{ old('lugar_id') == $lugar->id ? 'selected' : '' }}>
                        {{ $lugar->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Referencia (opcional)
            </label>
            <input type="text"
                   name="direccion"
                   value="{{ old('direccion') }}"
                   placeholder="Ej: frente al mercado, esquina, etc."
                   class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" id="activo" name="activo" value="1"
                   class="rounded border-slate-300"
                   {{ old('activo', 1) ? 'checked' : '' }}>
            <label for="activo" class="text-xs text-slate-700">
                Paradero activo
            </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('catalogos.paraderos.index') }}"
               class="px-3 py-2 text-xs rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 text-xs font-semibold rounded-md bg-[var(--primary)] text-white hover:bg-emerald-700">
                Guardar paradero
            </button>
        </div>
    </form>
</div>
@endsection
