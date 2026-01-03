@extends('layouts.app')

@section('title','Editar usuario')
@section('header','Editar usuario')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm">
    <form method="POST"
          action="{{ route('usuarios.update', $usuario) }}"
          class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        @method('PUT')

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
            <label class="block mb-1 text-xs font-medium">Sucursal (opcional)</label>
            <select name="sucursal_id" class="w-full border rounded px-2 py-1 text-sm">
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

        {{-- Horarios (múltiples) --}}
        <div>
            <label class="block mb-1 text-xs font-medium">
                Horarios autorizados (puede elegir varios)
            </label>
            @php
                $horariosSeleccionados = collect(old('horarios', $selectedHorarios ?? []))
                    ->map(fn($v) => (int) $v);
            @endphp
            <select name="horarios[]" multiple
                    class="w-full border rounded px-2 py-1 text-sm h-28">
                @foreach($horarios as $h)
                    <option value="{{ $h->id }}"
                        @selected($horariosSeleccionados->contains($h->id))>
                        {{ $h->nombre }}
                        @if(!empty($h->hora))
                            ({{ \Carbon\Carbon::parse($h->hora)->format('H:i') }})
                        @endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-[11px] text-gray-500">
                Mantén presionada la tecla Ctrl (o Cmd en Mac) para seleccionar varios horarios.
            </p>
        </div>

        {{-- Email --}}
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

        {{-- Nueva contraseña --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Nueva contraseña (opcional)</label>
            <input type="password"
                   name="password"
                   class="w-full border rounded px-2 py-1 text-sm">
            <p class="text-[11px] text-gray-500 mt-1">
                Déjalo en blanco para mantener la contraseña actual.
            </p>
        </div>

        {{-- Confirmación nueva contraseña --}}
        <div>
            <label class="block mb-1 text-xs font-medium">Confirmar nueva contraseña</label>
            <input type="password"
                   name="password_confirmation"
                   class="w-full border rounded px-2 py-1 text-sm">
        </div>

        {{-- Roles --}}
        <div class="md:col-span-2">
            <label class="block mb-1 text-xs font-medium">Roles</label>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto border rounded px-2 py-2">
                @php
                    $rolesSeleccionados = collect(
                        old('roles', $usuario->roles->pluck('id')->toArray())
                    )->map(fn($v) => (int) $v);
                @endphp
                @foreach($roles as $rol)
                    <label class="inline-flex items-center text-xs border rounded px-2 py-1 bg-gray-50">
                        <input type="checkbox"
                               name="roles[]"
                               value="{{ $rol->id }}"
                               class="mr-2"
                               @checked($rolesSeleccionados->contains($rol->id))>
                        <span>
                            {{ $rol->nombre }}
                            <span class="text-[10px] text-gray-400">({{ $rol->slug }})</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Activo --}}
        <div class="flex items-center mt-2">
            <label class="inline-flex items-center">
                <input type="checkbox"
                       name="activo"
                       value="1"
                       class="border rounded"
                       @checked(old('activo', $usuario->activo) == true)>
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
                Actualizar usuario
            </button>
        </div>
    </form>
</div>
@endsection
