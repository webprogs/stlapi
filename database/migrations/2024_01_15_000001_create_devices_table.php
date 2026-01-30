<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 36)->unique();
            $table->string('api_key', 64)->unique();
            $table->string('device_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('api_key');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
