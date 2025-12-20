{{-- resources/views/programaciones/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'Matriz de programación')
@section('header', 'Programación de transporte - Matriz Paradero x Ruta')

@section('content')
@php
    $tipoLabel = $programacion->esSalida()
        ? 'Salida (retorno a la ciudad)'
        : 'Recojo (entrada al fundo)';

    $estado = $programacion->estado ?? 'borrador';
    $estadoLabel = ucfirst($estado);

    $user = auth()->user();
    $esAdminGral     = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();
    $esAdminSistema  = $user && method_exists($user, 'hasRole') && $user->hasRole('admin');

    // Puede ver / usar botones de Confirmar / Cerrar / Editar estado
    $puedeGestionar = $user && (
        (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('gestionar_programaciones'))
        || $esAdminGral
        || $esAdminSistema
    );

    // Solo estos pueden REABRIR a borrador
    $puedeReabrirEstado = $esAdminGral || $esAdminSistema;

    // JSON para JS
    $paraderosJson = $paraderos->map(function ($p) {
        return [
            'id'           => $p->id,
            'nombre'       => $p->nombre,
            'lugar_id'     => $p->lugar_id,
            'lugar_nombre' => $p->lugar->nombre ?? 'SIN LUGAR',
        ];
    })->values();

    $rutasJson = $rutas->map(function ($r) {
        return [
            'id'     => $r->id,
            'codigo' => $r->codigo,
            'nombre' => $r->nombre,
            'lotes'  => $r->lotes->map(function ($l) {
                return [
                    'id'        => $l->id,
                    'nombre'    => $l->nombre,
                    'comedores' => $l->comedores_list,
                ];
            })->values(),
        ];
    })->values();

    $detallesJson = $detalles->map(function ($d) {
        return [
            'id'          => $d->id,
            'paradero_id' => $d->paradero_id,
            'ruta_id'     => $d->ruta_id,
            'lote'        => $d->lote,
            'comedor'     => $d->comedor,
            'personas'    => $d->personas,
        ];
    })->values();
@endphp

@if(session('status'))
    <div class="mb-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-3 py-2 rounded">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-3 bg-red-50 border border-red-200 text-red-700 text-xs px-3 py-2 rounded">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- CABECERA DE PROGRAMACIÓN --}}
<div class="bg-white rounded-lg shadow p-4 mb-4 text-xs text-gray-700 space-y-3">
    {{-- Si es ADMIN_GENERAL, puede editar la cabecera --}}
    @if($esAdminGral)
        <form method="POST" action="{{ route('programaciones.update.cabecera', $programacion) }}"
              class="flex flex-wrap justify-between gap-3 items-end">
            @csrf
            @method('PUT')

            <div>
                <div class="text-[11px] text-gray-500 mb-0.5">Sucursal</div>
                <select name="sucursal_id"
                        class="border-gray-300 rounded px-2 py-1 text-xs min-w-[160px]">
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}"
                            @selected(old('sucursal_id', $programacion->sucursal_id) == $sucursal->id)>
                            {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="text-[11px] text-gray-500 mb-0.5">Fecha</div>
                <input type="date"
                       name="fecha"
                       value="{{ old('fecha', $programacion->fecha?->format('Y-m-d')) }}"
                       class="border-gray-300 rounded px-2 py-1 text-xs">
            </div>

            <div>
                <div class="text-[11px] text-gray-500 mb-0.5">Área</div>
                <select name="area_id"
                        class="border-gray-300 rounded px-2 py-1 text-xs min-w-[180px]">
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}"
                            @selected(old('area_id', $programacion->area_id) == $area->id)>
                            {{ $area->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="text-[11px] text-gray-500 mb-0.5">Horario</div>
                <select name="horario_id"
                        class="border-gray-300 rounded px-2 py-1 text-xs min-w-[200px]">
                    @foreach($horarios as $horario)
                        @php
                            $labelHorario = $horario->etiqueta_completa
                                ?? trim(($horario->nombre ? $horario->nombre.' ' : '').($horario->hora_formateada ?? ''));
                        @endphp
                        <option value="{{ $horario->id }}"
                            @selected(old('horario_id', $programacion->horario_id) == $horario->id)>
                            {{ $labelHorario !== '' ? $labelHorario : 'Horario '.$horario->id }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="text-[11px] text-gray-500 mb-0.5">Tipo</div>
                <select name="tipo"
                        class="border-gray-300 rounded px-2 py-1 text-xs">
                    <option value="recojo" @selected(old('tipo', $programacion->tipo) === 'recojo')>
                        Recojo
                    </option>
                    <option value="salida" @selected(old('tipo', $programacion->tipo) === 'salida')>
                        Salida
                    </option>
                </select>
            </div>

            <div>
                <div class="text-[11px] text-gray-500 mb-0.5">Estado actual</div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px]
                             {{ $estado === 'cerrado'
                                ? 'bg-gray-800 text-white'
                                : ($estado === 'confirmado'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-yellow-100 text-yellow-700') }}">
                    {{ strtoupper($estadoLabel) }}
                </span>
            </div>

            <div>
                <div class="text-[11px] text-gray-500 mb-0.5">Total personas</div>
                <div class="font-semibold text-[var(--primary)]">
                    {{ $programacion->total_personas ?? 0 }}
                </div>
            </div>

            <div class="w-full flex justify-end mt-2">
                <button type="submit"
                        class="px-3 py-1.5 rounded text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700">
                    Actualizar cabecera
                </button>
            </div>
        </form>
    @else
        {{-- Vista solo lectura para usuarios normales --}}
        <div class="flex flex-wrap justify-between gap-3">
            <div>
                <div class="text-[11px] text-gray-500">Sucursal</div>
                <div class="font-semibold">
                    {{ $programacion->sucursal->nombre ?? 'Sin sucursal' }}
                </div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500">Fecha</div>
                <div class="font-semibold">
                    {{ $programacion->fecha?->format('d/m/Y') }}
                </div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500">Área</div>
                <div class="font-semibold">
                    {{ $programacion->area->nombre ?? 'Sin área' }}
                </div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500">Horario</div>
                <div class="font-semibold">
                    @if($programacion->horario)
                        {{ $programacion->horario->nombre }}
                        @if($programacion->horario->hora_formateada)
                            ({{ $programacion->horario->hora_formateada }})
                        @endif
                    @else
                        Sin horario
                    @endif
                </div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500">Tipo</div>
                <div class="font-semibold">
                    {{ $tipoLabel }}
                </div>
            </div>
            <div>
                <div class="text-[11px] text-gray-500">Estado</div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px]
                             {{ $estado === 'cerrado'
                                ? 'bg-gray-800 text-white'
                                : ($estado === 'confirmado'
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-yellow-100 text-yellow-700') }}">
                    {{ strtoupper($estadoLabel) }}
                </span>
            </div>
            <div>
                <div class="text-[11px] text-gray-500">Total personas</div>
                <div class="font-semibold text-[var(--primary)]">
                    {{ $programacion->total_personas ?? 0 }}
                </div>
            </div>
        </div>
    @endif

    {{-- Fila de botones de navegación / estado --}}
    <div class="mt-2 flex flex-wrap justify-between gap-2">
        <div class="flex gap-2">
            <a href="{{ route('programaciones.index') }}"
               class="px-3 py-1.5 border rounded text-xs text-gray-700 hover:bg-gray-50">
                Volver al listado
            </a>
        </div>

        <div class="flex gap-2">
            @if($puedeGestionar)
                {{-- Acciones según ESTADO --}}
                @if($estado === 'borrador')
                    {{-- Confirmar --}}
                    <form method="POST" action="{{ route('programaciones.confirmar', $programacion) }}">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded text-xs font-semibold bg-sky-600 text-white hover:bg-sky-700">
                            Confirmar
                        </button>
                    </form>

                    {{-- Cerrar --}}
                    <form method="POST" action="{{ route('programaciones.cerrar', $programacion) }}"
                          onsubmit="return confirm('¿Cerrar definitivamente esta programación?');">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded text-xs font-semibold bg-gray-800 text-white hover:bg-black">
                            Cerrar
                        </button>
                    </form>
                @elseif($estado === 'confirmado')
                    {{-- Desde CONFIRMADO solo se puede CERRAR --}}
                    <form method="POST" action="{{ route('programaciones.cerrar', $programacion) }}"
                          onsubmit="return confirm('¿Cerrar definitivamente esta programación?');">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded text-xs font-semibold bg-gray-800 text-white hover:bg-black">
                            Cerrar
                        </button>
                    </form>
                @endif

                {{-- REABRIR a BORRADOR (Editar estado) SOLO admin_general / admin --}}
                @if(in_array($estado, ['confirmado','cerrado'], true) && $puedeReabrirEstado)
                    <form method="POST" action="{{ route('programaciones.reabrir', $programacion) }}"
                          onsubmit="return confirm('¿Cambiar estado a BORRADOR para poder editar la matriz?');">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 rounded text-xs font-semibold bg-amber-500 text-white hover:bg-amber-600">
                            Editar estado (Borrador)
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>

{{-- MATRIZ / LISTA DE DETALLES --}}
<div class="bg-white rounded-lg shadow p-4 text-xs">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-gray-700">
            @if($programacion->esSalida())
                Detalle de SALIDA: personas por Lugar, Paradero, Ruta, Lote y Comedor
            @else
                Detalle de RECOJO: personas por Lugar, Paradero, Ruta, Lote y Comedor
            @endif
        </h2>
        @if($soloLectura)
            <span class="text-[11px] text-gray-500 italic">
                Programación en estado {{ $estadoLabel }}. Solo lectura.
            </span>
        @endif
    </div>

    <form method="POST" action="{{ route('programaciones.update', $programacion) }}">
        @csrf
        @method('PUT')

        <div class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-2 py-2 text-center w-8">#</th>
                        <th class="px-2 py-2 text-left">Lugar</th>
                        <th class="px-2 py-2 text-left">Paradero</th>
                        <th class="px-2 py-2 text-left">Ruta</th>
                        <th class="px-2 py-2 text-left">Lote / Red</th>
                        <th class="px-2 py-2 text-left">Comedor</th>
                        <th class="px-2 py-2 text-center w-20">Personas</th>
                        <th class="px-2 py-2 text-center w-16">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-lineas-body" class="divide-y">
                    {{-- Filas generadas por JS --}}
                </tbody>
            </table>
        </div>

        @unless($soloLectura)
            <div class="mt-3 flex items-center justify-between">
                <button type="button" id="btnAgregarLinea"
                        class="inline-flex items-center px-3 py-1.5 rounded border border-gray-300 text-xs hover:bg-gray-50">
                    + Agregar fila
                </button>

                <button type="submit"
                        class="inline-flex items-center px-4 py-1.5 rounded bg-[var(--primary)] text-white text-xs font-semibold hover:bg-[var(--primary-dark)]">
                    Guardar matriz
                </button>
            </div>
        @endunless
    </form>

    <div class="mt-3 text-[11px] text-gray-500">
        <p>
            Cada fila representa un grupo de personas para una combinación
            <strong>Lugar → Paradero → Ruta → Lote/Red → Comedor</strong>.
            El total de personas de la programación se recalcula automáticamente
            al guardar la matriz.
        </p>
    </div>
</div>

{{-- JS para manejar filas dinámicas y cascada Ruta → Lote → Comedor --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const soloLectura = @json($soloLectura);
        const paraderos   = @json($paraderosJson);
        const rutas       = @json($rutasJson);
        const detalles    = @json($detallesJson);

        const tbody      = document.getElementById('tabla-lineas-body');
        const btnAgregar = document.getElementById('btnAgregarLinea');

        // Mapa de lugares a partir de paraderos
        const lugaresMap = {};
        paraderos.forEach(p => {
            const id = p.lugar_id || 0;
            if (!lugaresMap[id]) {
                lugaresMap[id] = {
                    id: id,
                    nombre: p.lugar_nombre || 'SIN LUGAR'
                };
            }
        });
        const lugares = Object.values(lugaresMap).sort((a, b) =>
            (a.nombre || '').localeCompare(b.nombre || '')
        );

        function getRutaById(rutaId) {
            if (!rutaId) return null;
            return rutas.find(r => String(r.id) === String(rutaId)) || null;
        }

        let lineaCounter = 0;

        function crearSelectLugar(selectedLugarId) {
            const sel = document.createElement('select');
            sel.className = 'border-gray-300 rounded px-2 py-1 text-xs w-full select-lugar';
            sel.dataset.role = 'lugar';

            const optAll = document.createElement('option');
            optAll.value = '';
            optAll.textContent = 'Seleccione…';
            sel.appendChild(optAll);

            lugares.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l.id;
                opt.textContent = l.nombre;
                if (String(l.id) === String(selectedLugarId ?? '')) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });

            return sel;
        }

        function crearSelectParadero(lineIndex, selectedParaderoId, selectedLugarId) {
            const sel = document.createElement('select');
            sel.name = `lineas[${lineIndex}][paradero_id]`;
            sel.className = 'border-gray-300 rounded px-2 py-1 text-xs w-full select-paradero';
            sel.dataset.role = 'paradero';

            const optEmpty = document.createElement('option');
            optEmpty.value = '';
            optEmpty.textContent = 'Seleccione…';
            sel.appendChild(optEmpty);

            paraderos.forEach(p => {
                const lugarId = p.lugar_id || 0;

                if (selectedLugarId && String(lugarId) !== String(selectedLugarId)) {
                    return;
                }

                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.nombre;
                if (String(p.id) === String(selectedParaderoId ?? '')) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });

            return sel;
        }

        function crearSelectRuta(lineIndex, selectedRutaId) {
            const sel = document.createElement('select');
            sel.name = `lineas[${lineIndex}][ruta_id]`;
            sel.className = 'border-gray-300 rounded px-2 py-1 text-xs w-full';
            sel.dataset.role = 'ruta';

            const optSin = document.createElement('option');
            optSin.value = '';
            optSin.textContent = 'Sin ruta';
            sel.appendChild(optSin);

            rutas.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = (`${r.codigo || ''} ${r.nombre || ''}`).trim() || `Ruta ${r.id}`;
                if (String(r.id) === String(selectedRutaId ?? '')) {
                    opt.selected = true;
                }
                sel.appendChild(opt);
            });

            return sel;
        }

        function crearSelectLote(lineIndex) {
            const sel = document.createElement('select');
            sel.name = `lineas[${lineIndex}][lote]`;
            sel.className = 'border-gray-300 rounded px-2 py-1 text-xs w-full';
            sel.dataset.role = 'lote';
            return sel;
        }

        function crearSelectComedor(lineIndex) {
            const sel = document.createElement('select');
            sel.name = `lineas[${lineIndex}][comedor]`;
            sel.className = 'border-gray-300 rounded px-2 py-1 text-xs w-full';
            sel.dataset.role = 'comedor';
            return sel;
        }

        function llenarLotes(selectElement, rutaId, selectedLoteNombre) {
            selectElement.innerHTML = '';

            const optEmpty = document.createElement('option');
            optEmpty.value = '';
            optEmpty.textContent = 'Seleccione…';
            selectElement.appendChild(optEmpty);

            if (!rutaId) {
                return;
            }

            const ruta = getRutaById(rutaId);
            if (!ruta || !Array.isArray(ruta.lotes) || ruta.lotes.length === 0) {
                const optNo = document.createElement('option');
                optNo.value = '';
                optNo.textContent = '(Sin lotes configurados)';
                selectElement.appendChild(optNo);
                return;
            }

            ruta.lotes.forEach(l => {
                const opt = document.createElement('option');
                opt.value = l.nombre;
                opt.textContent = l.nombre;
                if (selectedLoteNombre && selectedLoteNombre === l.nombre) {
                    opt.selected = true;
                }
                selectElement.appendChild(opt);
            });

            // Si el lote guardado no existe en la lista, lo agregamos
            if (selectedLoteNombre &&
                !Array.from(selectElement.options).some(o => o.value === selectedLoteNombre)) {
                const optExtra = document.createElement('option');
                optExtra.value = selectedLoteNombre;
                optExtra.textContent = selectedLoteNombre;
                optExtra.selected = true;
                selectElement.appendChild(optExtra);
            }
        }

        function llenarComedores(selectElement, rutaId, loteNombre, selectedComedor) {
            selectElement.innerHTML = '';

            const optEmpty = document.createElement('option');
            optEmpty.value = '';
            optEmpty.textContent = 'Seleccione…';
            selectElement.appendChild(optEmpty);

            if (!rutaId || !loteNombre) {
                return;
            }

            const ruta = getRutaById(rutaId);
            if (!ruta || !Array.isArray(ruta.lotes)) {
                return;
            }

            const lote = ruta.lotes.find(l => l.nombre === loteNombre);
            if (!lote || !Array.isArray(lote.comedores) || lote.comedores.length === 0) {
                const optNo = document.createElement('option');
                optNo.value = '';
                optNo.textContent = '(Sin comedores configurados)';
                selectElement.appendChild(optNo);
                return;
            }

            lote.comedores.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c;
                opt.textContent = c;
                if (selectedComedor && selectedComedor === c) {
                    opt.selected = true;
                }
                selectElement.appendChild(opt);
            });

            // Si el comedor guardado no está, lo agregamos
            if (selectedComedor &&
                !Array.from(selectElement.options).some(o => o.value === selectedComedor)) {
                const optExtra = document.createElement('option');
                optExtra.value = selectedComedor;
                optExtra.textContent = selectedComedor;
                optExtra.selected = true;
                selectElement.appendChild(optExtra);
            }
        }

        function crearInputNumero(name, value) {
            const input = document.createElement('input');
            input.type = 'number';
            input.name = name;
            input.min = '0';
            input.step = '1';
            input.value = value != null ? value : '';
            input.className = 'border-gray-300 rounded px-2 py-1 text-xs w-20 text-center';
            return input;
        }

        function crearHiddenId(lineIndex, id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `lineas[${lineIndex}][id]`;
            input.value = id ?? '';
            return input;
        }

        function agregarLinea(detalle) {
            lineaCounter++;
            const index = lineaCounter;

            const tr = document.createElement('tr');
            tr.dataset.index = index;

            let selectedParaderoId = detalle ? detalle.paradero_id : null;
            let selectedLugarId = '';
            if (selectedParaderoId) {
                const paradero = paraderos.find(p => String(p.id) === String(selectedParaderoId));
                if (paradero) {
                    selectedLugarId = paradero.lugar_id || '';
                }
            }

            const selectedRutaId   = detalle ? detalle.ruta_id : null;
            const selectedLote     = detalle ? (detalle.lote || '') : '';
            const selectedComedor  = detalle ? (detalle.comedor || '') : '';

            const tdNum = document.createElement('td');
            tdNum.className = 'px-2 py-2 text-center text-[11px] text-gray-500';
            tdNum.textContent = index;
            tr.appendChild(tdNum);

            const tdLugar = document.createElement('td');
            tdLugar.className = 'px-2 py-2';
            const selLugar = crearSelectLugar(selectedLugarId);
            tdLugar.appendChild(selLugar);
            tr.appendChild(tdLugar);

            const tdParadero = document.createElement('td');
            tdParadero.className = 'px-2 py-2';
            const selParadero = crearSelectParadero(index, selectedParaderoId, selectedLugarId);
            tdParadero.appendChild(selParadero);
            tr.appendChild(tdParadero);

            const tdRuta = document.createElement('td');
            tdRuta.className = 'px-2 py-2';
            const selRuta = crearSelectRuta(index, selectedRutaId);
            tdRuta.appendChild(selRuta);
            tr.appendChild(tdRuta);

            const tdLote = document.createElement('td');
            tdLote.className = 'px-2 py-2';
            const selLote = crearSelectLote(index);
            tdLote.appendChild(selLote);
            tr.appendChild(tdLote);

            const tdComedor = document.createElement('td');
            tdComedor.className = 'px-2 py-2';
            const selComedor = crearSelectComedor(index);
            tdComedor.appendChild(selComedor);
            tr.appendChild(tdComedor);

            const tdPersonas = document.createElement('td');
            tdPersonas.className = 'px-2 py-2 text-center';
            const inputPersonas = crearInputNumero(`lineas[${index}][personas]`, detalle ? detalle.personas : null);
            tdPersonas.appendChild(inputPersonas);
            tr.appendChild(tdPersonas);

            const tdAcciones = document.createElement('td');
            tdAcciones.className = 'px-2 py-2 text-center';

            const hiddenId = crearHiddenId(index, detalle ? detalle.id : null);
            tdAcciones.appendChild(hiddenId);

            if (!soloLectura) {
                const btnEliminar = document.createElement('button');
                btnEliminar.type = 'button';
                btnEliminar.className = 'px-2 py-1 border border-red-300 rounded text-[11px] text-red-600 hover:bg-red-50';
                btnEliminar.textContent = 'Quitar';
                btnEliminar.addEventListener('click', function () {
                    tr.remove();
                });
                tdAcciones.appendChild(btnEliminar);
            }

            tr.appendChild(tdAcciones);

            selLugar.addEventListener('change', function () {
                const nuevoLugarId = this.value || '';
                const seleccionadoParadero = selParadero.value || null;
                const nuevoSelParadero = crearSelectParadero(index, seleccionadoParadero, nuevoLugarId);
                tdParadero.innerHTML = '';
                tdParadero.appendChild(nuevoSelParadero);

                if (soloLectura) {
                    nuevoSelParadero.disabled = true;
                }
            });

            const rutaInicial = selRuta.value || selectedRutaId || null;
            llenarLotes(selLote, rutaInicial, selectedLote);
            llenarComedores(selComedor, rutaInicial, selectedLote, selectedComedor);

            selRuta.addEventListener('change', function () {
                const rutaId = this.value || null;
                llenarLotes(selLote, rutaId, null);
                llenarComedores(selComedor, rutaId, null, null);
            });

            selLote.addEventListener('change', function () {
                const rutaId     = selRuta.value || null;
                const loteNombre = this.value || null;
                llenarComedores(selComedor, rutaId, loteNombre, null);
            });

            if (soloLectura) {
                [selLugar, selParadero, selRuta, selLote, selComedor, inputPersonas].forEach(el => {
                    el.disabled = true;
                });
            }

            tbody.appendChild(tr);
        }

        if (detalles.length) {
            detalles.forEach(d => agregarLinea(d));
        } else {
            agregarLinea(null);
        }

        if (!soloLectura && btnAgregar) {
            btnAgregar.addEventListener('click', function () {
                agregarLinea(null);
            });
        }
    });
</script>
@endsection
