<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna "activo" a la tabla roles.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Booleano, por defecto true, NO nulo
            // Ajusta el "after" según las columnas que tengas
            $table->boolean('activo')
                ->default(true)
                ->after('slug'); // si no tienes "slug", quita el ->after(...)
        });
    }

    /**
     * Revierte el cambio.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
