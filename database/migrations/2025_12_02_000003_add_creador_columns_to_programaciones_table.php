<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programaciones', function (Blueprint $table) {
            // Campos de auditoría alineados con tu modelo Programacion

            if (!Schema::hasColumn('programaciones', 'creado_por')) {
                $table->foreignId('creado_por')
                    ->nullable()
                    ->after('total_personas')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('programaciones', 'actualizado_por')) {
                $table->foreignId('actualizado_por')
                    ->nullable()
                    ->after('creado_por')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('programaciones', function (Blueprint $table) {
            if (Schema::hasColumn('programaciones', 'actualizado_por')) {
                $table->dropForeign(['actualizado_por']);
                $table->dropColumn('actualizado_por');
            }

            if (Schema::hasColumn('programaciones', 'creado_por')) {
                $table->dropForeign(['creado_por']);
                $table->dropColumn('creado_por');
            }
        });
    }
};
