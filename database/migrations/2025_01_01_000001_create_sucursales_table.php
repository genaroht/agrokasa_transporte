<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla sucursales alineada con los seeders y el modelo.
     */
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();

            // Código interno de sucursal
            $table->string('codigo')->unique();  // ej: SUCU-001

            // Datos descriptivos
            $table->string('nombre');            // ej: Sucursal Principal
            $table->string('direccion')->nullable(); // dirección física
            $table->string('timezone')->default('America/Lima'); // zona horaria de la sucursal

            // Estado
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla sucursales.
     */
    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
