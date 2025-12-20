@extends('layouts.app')

@section('title','Nuevo rol')
@section('header','Crear rol')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm">
    <form method="POST" action="{{ route('roles.store') }}"
          class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf

        <div>
            <label class="block mb-1 text-xs text-gray-600">Nombre del rol *</label>
            <input type="text" name="nombre"
                   value="{{ old('nombre') }}"
                   class="w-full border rounded px-2 py-1 text-sm" required>
            <p class="text-[11px] text-gray-500 mt-1">
                Ejemplo: Administrador general, Programador de rutas, etc.
            </p>
        </div>

        <div>
            <label class="block mb-1 text-xs text-gray-600">Slug (interno) *</label>
            <input type="text" name="slug"
                   value="{{ old('slug') }}"
                   class="w-full border rounded px-2 py-1 text-sm" required>
            <p class="text-[11px] text-gray-500 mt-1">
                Texto sin espacios, usado en código. Ej: <code>admin_general</code>, <code>coordinador_transporte</code>.
            </p>
        </div>

        <div class="md:col-span-2">
            <label class="block mb-1 text-xs text-gray-600">Descripción (opcional)</label>
            <textarea name="descripcion" rows="2"
                      class="w-full border rounded px-2 py-1 text-sm"
                      placeholder="Describe para qué se usa este rol">{{ old('descripcion') }}</textarea>
        </div>

        <div class="flex items-center">
            <label class="inline-flex items-center mt-2">
                <input type="checkbox" name="activo" value="1"
                       class="border rounded"
                       @checked(old('activo', 1))>
                <span class="ml-2 text-sm">Activo</span>
            </label>
        </div>

        <div class="md:col-span-2 flex justify-end gap-2 mt-4">
            <a href="{{ route('roles.index') }}" class="px-3 py-1 border rounded text-xs">
                Cancelar
            </a>
            <button type="submit"
                    class="px-3 py-1 bg-[var(--primary)] text-white rounded text-xs">
                Guardar rol
            </button>
        </div>
    </form>
</div>
@endsection
