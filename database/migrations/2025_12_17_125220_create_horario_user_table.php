<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si ya existe la tabla, no la volvemos a crear
        if (Schema::hasTable('horario_user')) {
            return;
        }

        Schema::create('horario_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('horario_id')
                ->constrained('horarios')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'horario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_user');
    }
};
