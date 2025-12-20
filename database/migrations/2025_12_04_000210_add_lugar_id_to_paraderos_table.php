<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paraderos', function (Blueprint $table) {
            // El lugar que agrupa varios paraderos (Barranca, Supe, etc.)
            $table->foreignId('lugar_id')
                ->nullable()
                ->after('sucursal_id')
                ->constrained('lugares')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('paraderos', function (Blueprint $table) {
            $table->dropForeign(['lugar_id']);
            $table->dropColumn('lugar_id');
        });
    }
};
