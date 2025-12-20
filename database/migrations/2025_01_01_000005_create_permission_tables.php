<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea tablas de roles, permisos y sus pivots.
     */
    public function up(): void
    {
        // ===== ROLES =====
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                 // Nombre visible (ej: "Administrador General")
            $table->string('slug')->unique();         // Identificador interno (ej: "admin_general")
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // ===== PERMISOS =====
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                 // Ej: "Gestionar catálogos"
            $table->string('slug')->unique();         // Ej: "gestionar_catalogos"
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // ===== PIVOT role_user =====
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade');
        });

        // ===== PIVOT permission_role =====
        Schema::create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->primary(['permission_id', 'role_id']);

            $table->foreign('permission_id')
                ->references('id')->on('permissions')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade');
        });
    }

    /**
     * Revierte todo.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
