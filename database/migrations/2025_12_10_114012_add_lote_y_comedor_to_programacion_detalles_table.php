<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega las columnas lote y comedor a programacion_detalles.
     */
    public function up(): void
    {
        Schema::table('programacion_detalles', function (Blueprint $table) {
            // OJO: estás en PostgreSQL, aquí NO usamos ->after()
            $table->string('lote', 100)->nullable();
            $table->string('comedor', 100)->nullable();
        });
    }

    /**
     * Revierte los cambios.
     */
    public function down(): void
    {
        Schema::table('programacion_detalles', function (Blueprint $table) {
            $table->dropColumn(['lote', 'comedor']);
        });
    }
};
