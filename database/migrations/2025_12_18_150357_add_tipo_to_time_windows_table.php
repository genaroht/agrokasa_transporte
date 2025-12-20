<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_windows', function (Blueprint $table) {
            // string simple para evitar líos con ENUM en Postgres
            $table->string('tipo', 20)
                  ->default('salida')
                  ->after('sucursal_id');
        });
    }

    public function down(): void
    {
        Schema::table('time_windows', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
