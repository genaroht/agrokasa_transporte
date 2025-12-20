@extends('layouts.app')

@section('title', 'Configuración')
@section('page_title', 'Configuración Global')

@section('content')
<div class="bg-white rounded-lg shadow p-4 max-w-2xl">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Parámetros generales</h2>

    <form method="POST" action="{{ route('config.update') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                Nombre de la aplicación
            </label>
            <input type="text" name="app_name" value="{{ $appName }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                Zona horaria por defecto
            </label>
            <input type="text" name="app_timezone" value="{{ $appTimezone }}"
                   class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
            <p class="text-xs text-gray-500 mt-1">
                Ejemplo: <code>America/Lima</code>
            </p>
        </div>

        <button type="submit"
                class="inline-flex justify-center rounded bg-[#007037] px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
            Guardar (demo)
        </button>
    </form>

    <p class="text-xs text-gray-500 mt-4">
        Nota: esta vista es un stub. La persistencia real de parámetros globales se implementa en una tabla de configuración.
    </p>
</div>
@endsection
