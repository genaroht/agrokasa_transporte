@extends('layouts.app')

@section('title','Recuperar contraseña')
@section('header','Recuperar contraseña')

@section('content')
<div class="max-w-lg mx-auto bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-[var(--primary)] mb-4">
        Recuperación de contraseña
    </h2>
    <p class="text-sm text-gray-700 mb-3">
        Por motivos de seguridad, la recuperación de contraseña se realiza actualmente
        a través del <strong>Administrador General</strong> o del <strong>Administrador de Sucursal</strong>.
    </p>
    <p class="text-sm text-gray-700 mb-3">
        Por favor, comunícate con tu administrador indicando tu
        <strong>código de usuario</strong> para que puedan restablecer tu contraseña.
    </p>
    <p class="text-xs text-gray-500">
        Más adelante se habilitará un proceso de recuperación automatizado (2FA, correo corporativo, etc.).
    </p>
</div>
@endsection
