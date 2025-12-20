<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla users alineada con el modelo User y los seeders.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Código interno de colaborador (login)
            $table->string('codigo')->unique();  // ej: U000123

            // Datos de identidad
            $table->string('nombre');            // nombre(s)
            $table->string('apellido');          // apellidos
            $table->string('email')->nullable()->unique(); // opcional

            // Relación con sucursal (puede ser null para admin global)
            $table->unsignedBigInteger('sucursal_id')->nullable();

            // Autenticación
            $table->string('password');

            // Estado del usuario
            $table->boolean('activo')->default(true);

            // Timestamps auxiliares
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();

            // Token de "recuérdame" para sesión web
            $table->rememberToken();

            $table->timestamps();

            // Clave foránea a sucursales (sin cascade delete agresivo)
            $table->foreign('sucursal_id')
                ->references('id')->on('sucursales')
                ->onDelete('set null');
        });
    }

    /**
     * Elimina la tabla users.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
