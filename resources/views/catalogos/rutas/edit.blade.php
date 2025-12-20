@extends('layouts.app')

@section('title', 'Editar ruta')
@section('header', 'Editar ruta de transporte')

@section('content')
@php
    $user = auth()->user();
    $ruta->loadMissing('lotes');
    $lotesIniciales = old('lotes', $ruta->lotes->map(function ($l) {
        return [
            'nombre'    => $l->nombre,
            'comedores' => $l->comedores,
        ];
    })->values()->all());
@endphp

<div class="bg-white rounded-lg shadow p-4 text-sm space-y-4">
    <form method="POST" action="{{ route('catalogos.rutas.update', $ruta) }}">
        @csrf
        @method('PUT')

        {{-- Datos básicos --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            {{-- Sucursal --}}
            <div>
                <label class="block text-[11px] text-gray-600 mb-1">Sucursal</label>

                @if($user->isAdminGeneral())
                    <select name="sucursal_id"
                            class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037] text-xs">
                        @foreach($sucursales as $s)
                            <option value="{{ $s->id }}" @selected(old('sucursal_id', $ruta->sucursal_id) == $s->id)>
                                {{ $s->nombre }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <div class="text-xs font-semibold">
                        {{ $ruta->sucursal->nombre ?? ($user->sucursal->nombre ?? 'Sin sucursal') }}
                    </div>
                    <input type="hidden" name="sucursal_id"
                           value="{{ old('sucursal_id', $ruta->sucursal_id ?? $user->sucursal_id) }}">
                @endif

                @error('sucursal_id')
                    <div class="text-[11px] text-red-600 mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Código --}}
            <div>
                <label class="block text-[11px] text-gray-600 mb-1">Código</label>
                <input type="text" name="codigo"
                       value="{{ old('codigo', $ruta->codigo) }}"
                       class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037] text-xs" required>
                @error('codigo')
                    <div class="text-[11px] text-red-600 mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Nombre --}}
            <div class="md:col-span-2">
                <label class="block text-[11px] text-gray-600 mb-1">Nombre de la ruta</label>
                <input type="text" name="nombre"
                       value="{{ old('nombre', $ruta->nombre) }}"
                       class="w-full rounded border-gray-300 focus:border-[#007037] focus:ring-[#007037] text-xs" required>
                @error('nombre')
                    <div class="text-[11px] text-red-600 mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- Activo --}}
            <div class="flex items-center gap-2 mt-1">
                <input type="checkbox" name="activo" value="1" id="activo"
                       class="rounded border-gray-300"
                       @checked(old('activo', $ruta->activo))>
                <label for="activo" class="text-[11px] text-gray-700">
                    Ruta activa
                </label>
            </div>
        </div>

        {{-- Lotes / comedores --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700">
                    Lotes / Redes y Comedores de la ruta
                </h2>
                <button type="button" id="btnAgregarLote"
                        class="px-3 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-50">
                    + Agregar lote
                </button>
            </div>

            <p class="text-[11px] text-gray-500">
                Edita los <strong>lotes/redes</strong> y sus <strong>comedores</strong>.
                Los comedores se separan por coma.
            </p>

            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-2 py-2 w-10 text-center">#</th>
                            <th class="px-2 py-2 text-left">Lote / Red</th>
                            <th class="px-2 py-2 text-left">Comedores (separados por coma)</th>
                            <th class="px-2 py-2 w-16 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-lotes-body" class="divide-y">
                        {{-- filas por JS --}}
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Botones --}}
        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('catalogos.rutas.index') }}"
               class="px-3 py-1.5 border rounded text-xs">
                Cancelar
            </a>
            <button type="submit"
                    class="px-3 py-1.5 bg-[var(--primary)] text-white text-xs rounded hover:bg-[var(--primary-dark)]">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody    = document.getElementById('tabla-lotes-body');
        const btnAdd   = document.getElementById('btnAgregarLote');
        const lotesIni = @json($lotesIniciales);

        let counter = 0;

        function agregarFila(loteData = null) {
            counter++;
            const index = counter;

            const tr = document.createElement('tr');

            const tdNum = document.createElement('td');
            tdNum.className = 'px-2 py-2 text-center text-[11px] text-gray-500';
            tdNum.textContent = index;
            tr.appendChild(tdNum);

            const tdLote = document.createElement('td');
            tdLote.className = 'px-2 py-2';
            const inputLote = document.createElement('input');
            inputLote.type  = 'text';
            inputLote.name  = `lotes[${index}][nombre]`;
            inputLote.value = loteData && loteData.nombre ? loteData.nombre : '';
            inputLote.className = 'w-full border-gray-300 rounded px-2 py-1 text-xs';
            tdLote.appendChild(inputLote);
            tr.appendChild(tdLote);

            const tdComedores = document.createElement('td');
            tdComedores.className = 'px-2 py-2';
            const inputComedores = document.createElement('textarea');
            inputComedores.name  = `lotes[${index}][comedores]`;
            inputComedores.rows  = 1;
            inputComedores.className = 'w-full border-gray-300 rounded px-2 py-1 text-xs resize-y';
            inputComedores.value = loteData && loteData.comedores ? loteData.comedores : '';
            tdComedores.appendChild(inputComedores);
            tr.appendChild(tdComedores);

            const tdAcciones = document.createElement('td');
            tdAcciones.className = 'px-2 py-2 text-center';

            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.className = 'px-2 py-1 border border-red-300 rounded text-[11px] text-red-600 hover:bg-red-50';
            btnRemove.textContent = 'Quitar';
            btnRemove.addEventListener('click', function () {
                tr.remove();
            });

            tdAcciones.appendChild(btnRemove);
            tr.appendChild(tdAcciones);

            tbody.appendChild(tr);
        }

        if (Array.isArray(lotesIni) && lotesIni.length > 0) {
            lotesIni.forEach(function (l) {
                agregarFila(l);
            });
        } else {
            agregarFila(null);
        }

        btnAdd.addEventListener('click', function () {
            agregarFila(null);
        });
    });
</script>
@endsection
