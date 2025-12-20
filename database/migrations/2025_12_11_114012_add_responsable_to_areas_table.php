<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_responsable_to_areas_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->string('responsable', 150)
                  ->nullable()
                  ->after('sucursal_id');
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('responsable');
        });
    }
};
