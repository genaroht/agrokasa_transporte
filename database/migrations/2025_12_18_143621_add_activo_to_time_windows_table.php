<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_windows', function (Blueprint $table) {
            // Solo si aún no existe la columna
            if (!Schema::hasColumn('time_windows', 'activo')) {
                $table->boolean('activo')
                    ->default(true)
                    ->after('hora_fin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_windows', function (Blueprint $table) {
            if (Schema::hasColumn('time_windows', 'activo')) {
                $table->dropColumn('activo');
            }
        });
    }
};
