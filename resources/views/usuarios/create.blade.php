@extends('layouts.app')

@section('title','Nuevo usuario')
@section('header','Crear usuario')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm">
    <form method="POST"
          action="{{ route('usuarios.store') }}"
          class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf

        {{-- Código --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Código de usuario (login) *</label>
            <input type="text"
                   name="codigo"
                   value="{{ old('codigo', $usuario->codigo) }}"
                   class="w-full border rounded px-2 py-1 text-sm"
                   required>
        </div>

        {{-- Sucursal --}}
        <div>
            <label class="block mb-1 text-xs font-medium">
                Sucursal (opcional para Admin General)
            </label>
            <select name="sucursal_id"
                    class="w-full border rounded px-2 py-1 text-sm">
                <option value="">Ninguna (todas las sucursales)</option>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}"
                        @selected(old('sucursal_id', $usuario->sucursal_id) == $s->id)>
                        {{ $s->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Área --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Área</label>
            <select name="area_id"
                    class="w-full border rounded px-2 py-1 text-sm">
                <option value="">Sin área específica</option>
                @foreach($areas as $a)
                    <option value="{{ $a->id }}"
                        @selected(old('area_id', $usuario->area_id) == $a->id)>
                        {{ $a->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Email (opcional) --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Correo electrónico (opcional)</label>
            <input type="email"
                   name="email"
                   value="{{ old('email', $usuario->email) }}"
                   class="w-full border rounded px-2 py-1 text-sm">
        </div>

        {{-- Nombre --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Nombre *</label>
            <input type="text"
                   name="nombre"
                   value="{{ old('nombre', $usuario->nombre) }}"
                   class="w-full border rounded px-2 py-1 text-sm"
                   required>
        </div>

        {{-- Apellido --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Apellido *</label>
            <input type="text"
                   name="apellido"
                   value="{{ old('apellido', $usuario->apellido) }}"
                   class="w-full border rounded px-2 py-1 text-sm"
                   required>
        </div>

        {{-- Password --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Contraseña *</label>
            <input type="password"
                   name="password"
                   class="w-full border rounded px-2 py-1 text-sm"
                   required>
            <p class="mt-1 text-[11px] text-gray-500">
                Mínimo 8 caracteres, con mayúsculas, minúsculas y números.
            </p>
        </div>

        {{-- Confirm password --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Confirmar contraseña *</label>
            <input type="password"
                   name="password_confirmation"
                   class="w-full border rounded px-2 py-1 text-sm"
                   required>
        </div>

        {{-- Roles --}}
        <div class="md:col-span-2">
            <label class="block mb-1 text-xs font-medium">Roles *</label>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto border rounded px-2 py-2">
                @php
                    $oldRoles = collect(old('roles', []))->map(fn($r) => (int)$r);
                @endphp
                @foreach($roles as $rol)
                    <label class="inline-flex items-center text-xs border rounded px-2 py-1 bg-gray-50">
                        <input type="checkbox"
                               name="roles[]"
                               value="{{ $rol->id }}"
                               class="mr-2"
                               @checked($oldRoles->contains($rol->id))>
                        <span>
                            {{ $rol->nombre }}
                            <span class="text-[10px] text-gray-400">({{ $rol->slug }})</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-[11px] text-gray-500">
                Selecciona al menos un rol. El rol <strong>admin_general</strong> tiene acceso total.
            </p>
        </div>

        {{-- Activo --}}
        <div class="flex items-center mt-2">
            @php
                $activo = old('activo', $usuario->activo ?? true);
            @endphp
            <label class="inline-flex items-center">
                <input type="checkbox"
                       name="activo"
                       value="1"
                       class="border rounded"
                       @checked($activo)>
                <span class="ml-2 text-sm">Activo</span>
            </label>
        </div>

        {{-- Botones --}}
        <div class="md:col-span-2 flex justify-end gap-2 mt-4">
            <a href="{{ route('usuarios.index') }}"
               class="px-3 py-1 border rounded text-sm">
                Cancelar
            </a>
            <button type="submit"
                    class="px-3 py-1 bg-[var(--primary)] text-white rounded text-sm">
                Guardar usuario
            </button>
        </div>
    </form>
</div>
@endsection
