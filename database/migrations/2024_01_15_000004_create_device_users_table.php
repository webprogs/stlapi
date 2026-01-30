<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('local_id');
            $table->string('username', 50);
            $table->string('role', 20);
            $table->string('name', 100)->nullable();
            $table->timestamp('device_created_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_users');
    }
};
