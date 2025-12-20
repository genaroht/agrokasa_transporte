<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Intencionalmente vacío.
        // Roles y permisos se crean con Spatie (migración 000005) y el seeder RolesAndPermissionsSeeder.
    }

    public function down(): void
    {
        // Nada que deshacer.
    }
};
