{{-- resources/views/programaciones/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nueva programación manual')
@section('header', 'Programación manual (matriz completa)')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // Sucursal de contexto desde el middleware o desde el usuario
    $sucursalContexto = request()->attributes->get('sucursalActual')
        ?? ($user->sucursal ?? null);

    $hoy = now()->toDateString();

    // Roles básicos
    $esAdminGral = method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
    $esAdmin     = method_exists($user, 'hasRole') && $user->hasRole('admin');

    // Si NO es admin_general ni admin, área fija
    $soloAreaFija = !($esAdminGral || $esAdmin);

    // Área preseleccionada (si viene de old o del usuario)
    $areaSeleccionada = old('area_id');
    if (!$areaSeleccionada && isset($user->area_id)) {
        $areaSeleccionada = $user->area_id;
    }

    // Tipo por defecto
    $tipoSeleccionado = old('tipo', \App\Models\Programacion::TIPO_RECOJO ?? 'recojo');
@endphp

@if(session('status'))
    <div class="mb-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-3 py-2 rounded">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-3 bg-red-50 border border-red-200 text-red-800 text-xs px-3 py-2 rounded">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="flex flex-col gap-4">

    {{-- CABECERA + BOTONES MANUAL / RÁPIDA --}}
    <div class="bg-white rounded-lg shadow p-4 text-xs flex flex-col gap-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="font-semibold text-sm">
                    Crear nueva programación manual
                </div>
                <div class="text-[11px] text-gray-500">
                    Aquí defines la <strong>Fecha / Tipo / Área / Horario</strong> de la programación.
                    Al guardar, se creará la programación y podrás editar la matriz completa
                    (Paradero x Ruta x Lote x Comedor) de forma manual.
                </div>
                <div class="mt-1 text-[11px] text-gray-500">
                    <span class="font-semibold">Sucursal:</span>
                    {{ $sucursalContexto->nombre ?? 'No definida' }}
                </div>
            </div>

            {{-- Botones de modo --}}
            <div class="flex gap-2 justify-end">
                {{-- Botón actual: Manual --}}
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded text-xs font-semibold
                           bg-[var(--primary)] text-white">
                    Programación manual
                </span>

                {{-- Link a Programación rápida --}}
                <a href="{{ route('programaciones.captura-rapida') }}"
                   class="inline-flex items-center px-3 py-1.5 rounded text-xs border border-gray-300
                          text-gray-700 bg-white hover:bg-gray-50">
                    Programación rápida
                </a>
            </div>
        </div>
    </div>

    {{-- FORMULARIO PARA CREAR PROGRAMACIÓN MANUAL --}}
    <div class="bg-white rounded-lg shadow p-4 text-xs">
        <form method="POST" action="{{ route('programaciones.store') }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-3 md:gap-4 items-end">
            @csrf

            {{-- Fecha --}}
            <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-600">
                    Fecha
                </label>
                <input type="date"
                       name="fecha"
                       value="{{ old('fecha', $hoy) }}"
                       class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
            </div>

            {{-- Tipo --}}
            <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-600">
                    Tipo
                </label>
                <select name="tipo"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
                    <option value="recojo" @selected($tipoSeleccionado === 'recojo')>
                        Recojo (entrada al fundo)
                    </option>
                    <option value="salida" @selected($tipoSeleccionado === 'salida')>
                        Salida (retorno a la ciudad)
                    </option>
                </select>
            </div>

            {{-- Área --}}
            <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-600">
                    Área
                </label>

                <select name="area_id"
                        @if($soloAreaFija) disabled @endif
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
                    @forelse($areas as $area)
                        <option value="{{ $area->id }}"
                            @selected((int)$areaSeleccionada === (int)$area->id)>
                            {{ $area->nombre }}
                        </option>
                    @empty
                        <option value="">Sin áreas disponibles</option>
                    @endforelse
                </select>

                @if($soloAreaFija && $areaSeleccionada)
                    {{-- Para que llegue el valor aunque el select esté disabled --}}
                    <input type="hidden" name="area_id" value="{{ $areaSeleccionada }}">
                @endif

                @if($soloAreaFija)
                    <div class="mt-1 text-[10px] text-gray-400">
                        (Solo puede programar en su propia área)
                    </div>
                @endif
            </div>

            {{-- Horario --}}
            <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-600">
                    Horario
                </label>
                <select name="horario_id"
                        class="w-full border border-gray-300 rounded px-2 py-1 text-xs bg-white">
                    @forelse($horarios as $h)
                        @php
                            $horaLabel = '';
                            if (!empty($h->hora)) {
                                try {
                                    $horaLabel = ' (' . \Carbon\Carbon::parse($h->hora)->format('H:i') . ')';
                                } catch (\Exception $e) {
                                    $horaLabel = '';
                                }
                            }
                        @endphp
                        <option value="{{ $h->id }}" @selected(old('horario_id') == $h->id)>
                            {{ $h->nombre }}{{ $horaLabel }}
                        </option>
                    @empty
                        <option value="">Sin horarios disponibles</option>
                    @endforelse
                </select>
            </div>

            {{-- Separador visual en pantallas grandes --}}
            <div class="md:col-span-4 border-t border-dashed border-gray-200 my-2"></div>

            {{-- Nota + Botones --}}
            <div class="md:col-span-3 text-[11px] text-gray-500">
                Al guardar, se creará una programación para la combinación
                <strong>Fecha / Tipo / Área / Horario</strong>. A continuación
                deberías ser redirigido a la matriz manual donde podrás registrar
                las personas por Ruta / Paradero / Lote / Comedor.
            </div>

            <div class="md:col-span-1 flex justify-end gap-2">
                <a href="{{ route('programaciones.index') }}"
                   class="px-3 py-1.5 rounded border border-gray-300 text-xs
                          text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-4 py-1.5 rounded bg-[var(--primary)] text-white text-xs font-semibold
                               hover:bg-emerald-700">
                    Crear programación
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
