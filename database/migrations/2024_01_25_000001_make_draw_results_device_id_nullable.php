<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing foreign key and unique constraint
        Schema::table('draw_results', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropUnique(['device_id', 'local_id']);
        });

        // Use raw SQL to modify the column to be nullable
        DB::statement('ALTER TABLE draw_results MODIFY device_id BIGINT UNSIGNED NULL');

        // Re-add foreign key with nullable support and add new unique constraint
        Schema::table('draw_results', function (Blueprint $table) {
            $table->foreign('device_id')
                ->references('id')
                ->on('devices')
                ->onDelete('cascade');

            // Add composite index for finding results by date/time/game
            $table->index(['draw_date', 'draw_time', 'game_type'], 'draw_results_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('draw_results', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropIndex('draw_results_lookup');
        });

        // Delete any admin-created results (device_id = null) before making column non-nullable
        DB::table('draw_results')->whereNull('device_id')->delete();

        // Revert to non-nullable
        DB::statement('ALTER TABLE draw_results MODIFY device_id BIGINT UNSIGNED NOT NULL');

        Schema::table('draw_results', function (Blueprint $table) {
            $table->foreign('device_id')
                ->references('id')
                ->on('devices')
                ->onDelete('cascade');

            $table->unique(['device_id', 'local_id']);
        });
    }
};
