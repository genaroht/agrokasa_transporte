{{-- resources/views/catalogos/lugares/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar lugar')
@section('header', 'Editar lugar')

@section('content')
<div class="max-w-md mx-auto bg-white shadow rounded-lg p-4 text-sm">

    <form method="POST" action="{{ route('catalogos.lugares.update', $lugar) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Sucursal
            </label>
            <select name="sucursal_id"
                    class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}"
                        @selected(old('sucursal_id', $lugar->sucursal_id) == $s->id)>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
            @error('sucursal_id')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">
                Nombre del lugar
            </label>
            <input type="text" name="nombre"
                   value="{{ old('nombre', $lugar->nombre) }}"
                   class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            @error('nombre')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" value="1" id="activo"
                   class="rounded border-slate-300 text-[var(--primary)] focus:ring-[var(--primary)]"
                   @checked(old('activo', $lugar->activo))>
            <label for="activo" class="text-xs text-slate-700">Lugar activo</label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('catalogos.lugares.index') }}"
               class="px-3 py-1 rounded border border-slate-300 text-xs text-slate-700 hover:bg-slate-50">
                Cancelar
            </a>

            <button type="submit"
                    class="px-4 py-1 rounded bg-[var(--primary)] text-white text-xs font-semibold hover:bg-emerald-700">
                Guardar cambios
            </button>
        </div>
    </form>
</div>
@endsection
