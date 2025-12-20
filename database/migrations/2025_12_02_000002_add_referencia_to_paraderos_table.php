<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paraderos', function (Blueprint $table) {
            // Solo la creamos si aún no existe, por si has tocado algo antes
            if (!Schema::hasColumn('paraderos', 'referencia')) {
                $table->string('referencia')
                      ->nullable()
                      ->after('direccion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paraderos', function (Blueprint $table) {
            if (Schema::hasColumn('paraderos', 'referencia')) {
                $table->dropColumn('referencia');
            }
        });
    }
};
