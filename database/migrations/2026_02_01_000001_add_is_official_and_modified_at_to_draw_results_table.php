<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draw_results', function (Blueprint $table) {
            $table->boolean('is_official')->default(false)->after('set_by');
            $table->datetime('modified_at')->nullable()->after('is_official');
        });
    }

    public function down(): void
    {
        Schema::table('draw_results', function (Blueprint $table) {
            $table->dropColumn(['is_official', 'modified_at']);
        });
    }
};
