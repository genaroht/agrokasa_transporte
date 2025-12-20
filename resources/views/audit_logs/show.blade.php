@extends('layouts.app')

@section('title','Detalle de auditoría')
@section('header','Detalle de registro de auditoría')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-sm mb-4">
    {{-- IMPORTANTE: aquí usamos audit.index, NO auditoria.index --}}
    <a href="{{ route('audit.index') }}" class="px-3 py-1 border rounded text-xs">
        ← Volver al listado
    </a>
</div>

<div class="bg-white rounded-lg shadow p-4 text-[12px] md:text-sm space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <div class="text-xs text-gray-500">Fecha/Hora</div>
            <div class="font-semibold">
                {{ $log->created_at->format('d/m/Y H:i:s') }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Usuario</div>
            <div class="font-semibold">
                @if($log->user)
                    {{ $log->user->nombre_completo ?? $log->user->codigo }}
                @else
                    —
                @endif
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500">Acción</div>
            <div class="font-semibold">
                {{ $log->action }}
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500">IP / User Agent</div>
            <div class="font-mono text-[11px] break-all">
                {{ $log->ip_address ?? '—' }}<br>
                <span class="text-gray-500">
                    {{ $log->user_agent ?? '' }}
                </span>
            </div>
        </div>
    </div>

    <div class="border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <div class="font-semibold mb-1">Valores anteriores</div>
            <pre class="bg-gray-900 text-gray-100 text-[11px] rounded p-3 overflow-auto">
{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
            </pre>
        </div>

        <div>
            <div class="font-semibold mb-1">Valores nuevos</div>
            <pre class="bg-gray-900 text-gray-100 text-[11px] rounded p-3 overflow-auto">
{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
            </pre>
        </div>
    </div>

    @if($log->auditable_type && $log->auditable_id)
        <div class="border-t pt-4">
            <div class="text-xs text-gray-500 mb-1">Entidad afectada</div>
            <div class="text-xs">
                {{ $log->auditable_type }} (ID: {{ $log->auditable_id }})
            </div>
        </div>
    @endif
</div>
@endsection
