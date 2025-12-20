<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lugares', function (Blueprint $table) {
            $table->id();

            // Lugar puede ser global (null) o asociado a una sucursal
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursales')
                ->nullOnDelete();

            // Ej: Barranca, Supe, Paramonga, etc.
            $table->string('nombre');

            $table->boolean('activo')->default(true);

            $table->timestamps();

            // No repetir nombres de lugar dentro de la misma sucursal
            $table->unique(['sucursal_id', 'nombre'], 'lugares_unicos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lugares');
    }
};
