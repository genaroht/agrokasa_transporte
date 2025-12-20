<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ÁREAS
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // HORARIOS
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            // null = horario global, o asociado a sucursal
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursales')
                ->nullOnDelete();
            $table->string('nombre');   // "Salida 14:00"
            $table->time('hora');       // 14:00:00
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // PARADEROS
        Schema::create('paraderos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('nombre');
            $table->string('codigo')->nullable();
            $table->string('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // RUTAS
        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('codigo');     // R1, R2, etc.
            $table->string('nombre');     // Descripción
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // VEHÍCULOS
        Schema::create('vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('placa')->unique();
            $table->string('codigo_interno')->nullable();
            $table->integer('capacidad')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehiculos');
        Schema::dropIfExists('rutas');
        Schema::dropIfExists('paraderos');
        Schema::dropIfExists('horarios');
        Schema::dropIfExists('areas');
    }
};
