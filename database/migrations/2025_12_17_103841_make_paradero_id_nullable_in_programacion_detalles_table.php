<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programacion_detalles', function (Blueprint $table) {
            // IMPORTANTE:
            // Para poder usar ->change() necesitas tener instalado "doctrine/dbal"
            // composer require doctrine/dbal
            $table->unsignedBigInteger('paradero_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programacion_detalles', function (Blueprint $table) {
            // Si quieres volver atrás (no lo recomiendo ya, pero lo dejamos por compatibilidad)
            $table->unsignedBigInteger('paradero_id')->nullable(false)->change();
        });
    }
};
