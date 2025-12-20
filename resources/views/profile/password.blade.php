@extends('layouts.app')

@section('title','Cambiar contraseña')
@section('header','Cambiar contraseña')

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-[var(--primary)] mb-4">
        Cambio de contraseña
    </h2>

    <form method="POST" action="{{ route('password.change.post') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">
                Contraseña actual
            </label>
            <input type="password" name="password_actual" required
                   class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">
                Nueva contraseña
            </label>
            <input type="password" name="password_nueva" required
                   class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
            <p class="text-xs text-gray-500 mt-1">
                Mínimo 8 caracteres, con al menos una mayúscula, una minúscula y un número.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">
                Confirmar nueva contraseña
            </label>
            <input type="password" name="password_nueva_confirmation" required
                   class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--primary)]">
        </div>

        <button type="submit"
                class="w-full bg-[var(--primary)] text-white py-2 rounded hover:bg-emerald-700 transition">
            Guardar cambios
        </button>
    </form>
</div>
@endsection
