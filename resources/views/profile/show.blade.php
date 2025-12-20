@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('page_title', 'Mi Perfil')

@section('content')
<div class="grid gap-4 md:grid-cols-2">
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Datos del usuario</h2>
        <dl class="text-sm">
            <div class="flex justify-between py-1 border-b">
                <dt class="text-gray-500">Código</dt>
                <dd class="font-mono">{{ $user->codigo }}</dd>
            </div>
            <div class="flex justify-between py-1 border-b">
                <dt class="text-gray-500">Nombre completo</dt>
                <dd>{{ $user->nombre_completo }}</dd>
            </div>
            <div class="flex justify-between py-1 border-b">
                <dt class="text-gray-500">Sucursal</dt>
                <dd>{{ optional($user->sucursal)->nombre ?? 'N/A' }}</dd>
            </div>
            <div class="flex justify-between py-1 border-b">
                <dt class="text-gray-500">Roles</dt>
                <dd>{{ $user->getRoleNames()->implode(', ') ?: 'Sin rol' }}</dd>
            </div>
            <div class="flex justify-between py-1">
                <dt class="text-gray-500">Estado</dt>
                <dd>{{ $user->activo ? 'Activo' : 'Inactivo' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Cambiar contraseña</h2>
        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Contraseña actual
                </label>
                <input type="password" name="password_actual"
                       class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Nueva contraseña
                </label>
                <input type="password" name="password_nuevo"
                       class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Confirmar nueva contraseña
                </label>
                <input type="password" name="password_nuevo_confirmation"
                       class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]" required>
            </div>
            <button type="submit"
                    class="inline-flex justify-center rounded bg-[#007037] px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                Guardar contraseña
            </button>
        </form>
    </div>
</div>
@endsection
