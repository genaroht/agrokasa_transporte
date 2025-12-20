<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            // Tipo de horario: SALIDA o RECOJO
            $table->string('tipo', 20)
                ->default('RECOJO')
                ->after('nombre');

            // Hora fin para mostrar "DE hh:mm a hh:mm"
            $table->time('hora_fin')
                ->nullable()
                ->after('hora');
        });
    }

    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn('hora_fin');
            $table->dropColumn('tipo');
        });
    }
};
