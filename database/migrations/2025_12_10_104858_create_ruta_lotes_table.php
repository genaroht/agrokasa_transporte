<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruta_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruta_id')
                ->constrained('rutas')
                ->cascadeOnDelete();
            $table->string('nombre', 100);   // Lote / Red
            $table->text('comedores')->nullable(); // "Comedor 1, Comedor 2"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ruta_lotes');
    }
};
