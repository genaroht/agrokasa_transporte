@extends('layouts.app')

@section('title','Auditoría del sistema')
@section('header','Auditoría y registro de cambios')

@section('content')
<div class="bg-white rounded-lg shadow p-4 mb-4 text-sm">

    {{-- FILTROS --}}
    <form method="GET"
          action="{{ route('audit.index') }}"
          class="grid grid-cols-1 md:grid-cols-5 gap-3">

        {{-- Usuario --}}
        <div>
            <label class="block mb-1 text-xs text-gray-600">Usuario</label>
            <select name="user_id" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todos</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>
                        {{ $u->nombre_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Acción --}}
        <div>
            <label class="block mb-1 text-xs text-gray-600">Acción</label>
            <select name="action" class="w-full border rounded px-2 py-1 text-xs">
                <option value="">Todas</option>
                @foreach($acciones as $accion)
                    <option value="{{ $accion }}" @selected(request('action') == $accion)>
                        {{ $accion }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Fecha desde --}}
        <div>
            <label class="block mb-1 text-xs text-gray-600">Fecha desde</label>
            <input type="date"
                   name="fecha_desde"
                   value="{{ request('fecha_desde') }}"
                   class="w-full border rounded px-2 py-1 text-xs">
        </div>

        {{-- Fecha hasta --}}
        <div>
            <label class="block mb-1 text-xs text-gray-600">Fecha hasta</label>
            <input type="date"
                   name="fecha_hasta"
                   value="{{ request('fecha_hasta') }}"
                   class="w-full border rounded px-2 py-1 text-xs">
        </div>

        {{-- Botones --}}
        <div class="flex items-end gap-2">
            <button type="submit"
                    class="px-3 py-1 bg-[var(--primary)] text-white rounded text-xs">
                Filtrar
            </button>

            <a href="{{ route('audit.index') }}"
               class="px-3 py-1 border rounded text-xs">
                Limpiar
            </a>
        </div>
    </form>
</div>

{{-- TABLA DE LOGS --}}
<div class="bg-white rounded-lg shadow p-4 text-xs overflow-x-auto">
    <table class="min-w-full border-collapse">
        <thead>
        <tr class="bg-gray-50 text-[11px] text-gray-600">
            <th class="border px-2 py-1 text-left">Fecha / hora</th>
            <th class="border px-2 py-1 text-left">Usuario</th>
            <th class="border px-2 py-1 text-left">Acción</th>
            <th class="border px-2 py-1 text-left">Tipo</th>
            <th class="border px-2 py-1 text-left">ID afectado</th>
            <th class="border px-2 py-1 text-left">IP</th>
            <th class="border px-2 py-1 text-left">Detalle</th>
        </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="border px-2 py-1 align-top">
                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                </td>
                <td class="border px-2 py-1 align-top">
                    {{ $log->user?->nombre_completo ?? 'N/D' }}
                    <div class="text-[10px] text-gray-400">
                        {{ $log->user?->codigo }}
                    </div>
                </td>
                <td class="border px-2 py-1 align-top">
                    {{ $log->action }}
                </td>
                <td class="border px-2 py-1 align-top">
                    {{ class_basename($log->auditable_type) }}
                </td>
                <td class="border px-2 py-1 align-top">
                    {{ $log->auditable_id }}
                </td>
                <td class="border px-2 py-1 align-top">
                    {{ $log->ip_address }}
                </td>
                <td class="border px-2 py-1 align-top">
                    <a href="{{ route('audit.show', $log) }}"
                       class="text-[11px] text-[var(--primary)] underline">
                        Ver detalle
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="border px-2 py-3 text-center text-gray-500">
                    No hay registros que coincidan con los filtros.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
