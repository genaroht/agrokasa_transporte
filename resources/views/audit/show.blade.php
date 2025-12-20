@extends('layouts.app')

@section('title', 'Detalle de auditoría')
@section('page_title', 'Detalle de auditoría')

@section('content')
<div class="bg-white rounded-lg shadow p-4 text-xs">
    <div class="mb-3">
        <div><span class="text-gray-500">Fecha:</span> {{ $log->created_at->format('d/m/Y H:i:s') }}</div>
        <div><span class="text-gray-500">Usuario:</span>
            {{ $log->user?->codigo ?? '-' }} - {{ $log->user?->nombre_completo ?? 'Sistema' }}
        </div>
        <div><span class="text-gray-500">Acción:</span> {{ $log->action }}</div>
        <div><span class="text-gray-500">Modelo:</span> {{ $log->model_type }} (ID: {{ $log->model_id }})</div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <h3 class="font-semibold text-gray-700 mb-2">Valores anteriores</h3>
            <pre class="bg-gray-50 border rounded p-2 overflow-auto max-h-64">
{{ json_encode($old, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
            </pre>
        </div>
        <div>
            <h3 class="font-semibold text-gray-700 mb-2">Valores nuevos</h3>
            <pre class="bg-gray-50 border rounded p-2 overflow-auto max-h-64">
{{ json_encode($new, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
            </pre>
        </div>
    </div>
</div>
@endsection
