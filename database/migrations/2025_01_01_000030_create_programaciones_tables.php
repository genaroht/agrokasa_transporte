<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CABECERA PROGRAMACIÓN
        Schema::create('programaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('fecha');

            $table->foreignId('area_id')
                ->constrained('areas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('horario_id')
                ->constrained('horarios')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Estado: borrador, confirmado, cerrado
            $table->string('estado')->default('borrador');

            // Cache de total de personas
            $table->integer('total_personas')->default(0);

            // Responsable (usuario) opcional
            $table->foreignId('responsable_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->unique(['sucursal_id', 'fecha', 'area_id', 'horario_id'], 'programaciones_unicas');
        });

        // DETALLE MATRIZ PARADERO x RUTA
        Schema::create('programacion_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('programacion_id')
                ->constrained('programaciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('paradero_id')
                ->constrained('paraderos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('ruta_id')
                ->nullable()
                ->constrained('rutas')
                ->nullOnDelete();

            $table->integer('personas')->default(0);

            $table->timestamps();

            $table->unique(
                ['programacion_id', 'paradero_id', 'ruta_id'],
                'programacion_detalles_unicos'
            );
        });

        // TABLA RUTAS / LOTES / COMEDOR (detalle adicional)
        Schema::create('programacion_ruta_lote', function (Blueprint $table) {
            $table->id();

            $table->foreignId('programacion_id')
                ->constrained('programaciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('ruta_id')
                ->constrained('rutas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('lote')->nullable();
            $table->string('comedor')->nullable();
            $table->integer('personas')->default(0);
            $table->string('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programacion_ruta_lote');
        Schema::dropIfExists('programacion_detalles');
        Schema::dropIfExists('programaciones');
    }
};
