@extends('layouts.app')

@section('title', 'Auditoría')
@section('page_title', 'Auditoría de cambios')

@section('content')
<div class="mb-4 text-xs">
    <form method="GET" action="{{ route('audit.index') }}" class="flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Usuario ID</label>
            <input type="number" name="user_id" value="{{ request('user_id') }}"
                   class="rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037] w-28">
        </div>
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}"
                   class="rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}"
                   class="rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037]">
        </div>
        <div>
            <label class="block text-[11px] text-gray-600 mb-1">Acción contiene</label>
            <input type="text" name="accion" value="{{ request('accion') }}"
                   class="rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037] w-40">
        </div>
        <button type="submit"
                class="inline-flex justify-center rounded bg-gray-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800">
            Filtrar
        </button>
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto text-[11px]">
    <table class="min-w-full">
        <thead class="bg-gray-50 text-gray-600">
        <tr>
            <th class="px-2 py-1 text-left">Fecha</th>
            <th class="px-2 py-1 text-left">Usuario</th>
            <th class="px-2 py-1 text-left">Acción</th>
            <th class="px-2 py-1 text-left">Modelo</th>
            <th class="px-2 py-1 text-left">ID</th>
            <th class="px-2 py-1 text-right">Ver</th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="px-2 py-1">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                <td class="px-2 py-1">
                    {{ $log->user?->codigo ?? '-' }} - {{ $log->user?->nombre_completo ?? 'Sistema' }}
                </td>
                <td class="px-2 py-1">{{ $log->action }}</td>
                <td class="px-2 py-1">{{ $log->model_type }}</td>
                <td class="px-2 py-1">{{ $log->model_id }}</td>
                <td class="px-2 py-1 text-right">
                    <a href="{{ route('audit.show', $log) }}"
                       class="text-[11px] text-[#007037] hover:underline">
                        Detalle
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-3 py-4 text-center text-gray-500">
                    No hay registros de auditoría.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>
@endsection
