<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla time_windows (ventanas de tiempo por sucursal/área/usuario/horario).
     */
    public function up(): void
    {
        Schema::create('time_windows', function (Blueprint $table) {
            $table->id();

            // Referencias (SIN foreign keys estrictas para evitar problemas de orden)
            $table->unsignedBigInteger('sucursal_id');      // sucursales.id
            $table->unsignedBigInteger('area_id')->nullable();      // areas.id (catálogo)
            $table->unsignedBigInteger('user_id')->nullable();      // users.id
            $table->unsignedBigInteger('role_id')->nullable();      // roles.id (Spatie)
            $table->unsignedBigInteger('horario_id')->nullable();   // horarios.id

            // Contexto de la ventana
            $table->date('fecha');           // fecha a la que aplica la ventana
            $table->time('hora_inicio');     // desde esta hora puede operar
            $table->time('hora_fin');        // hasta esta hora puede operar

            // Estado de la ventana:
            // - activo
            // - expirado
            // - reabierto
            $table->string('estado', 20)->default('activo');

            // Si la ventana fue reabierta, hasta cuándo sigue válida
            $table->timestamp('reabierto_hasta')->nullable();

            // Auditoría básica
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['sucursal_id', 'fecha']);
            $table->index(['area_id', 'horario_id']);
            $table->index(['user_id']);
            $table->index(['estado']);
        });
    }

    /**
     * Elimina la tabla time_windows.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_windows');
    }
};
