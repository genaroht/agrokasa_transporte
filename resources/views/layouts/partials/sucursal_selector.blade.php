@php
    use App\Models\Sucursal;

    $user = auth()->user();
    $esAdminGeneral = $user && method_exists($user, 'isAdminGeneral') && $user->isAdminGeneral();

    // Sucursales que puede ver en el combo:
    $sucursalesHeader = $esAdminGeneral
        ? Sucursal::where('activo', true)->orderBy('nombre')->get()
        : Sucursal::where('id', $user->sucursal_id)->get();

    // ID de sucursal activa
    $sucursalActivaId = session('sucursal_activa_id', $sucursalActiva->id ?? $user->sucursal_id);
@endphp

<form method="POST"
      action="{{ route('sucursales.cambiar') }}"
      id="formCambiarSucursal"
      class="inline-block">
    @csrf

    {{-- URL actual para volver a la misma vista después de cambiar --}}
    <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

    <select name="sucursal_id"
            class="border rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-[var(--primary)]"
            onchange="document.getElementById('formCambiarSucursal').submit();">
        @foreach($sucursalesHeader as $s)
            <option value="{{ $s->id }}" @selected((int)$sucursalActivaId === (int)$s->id)>
                {{ $s->nombre }}
            </option>
        @endforeach
    </select>
</form>
