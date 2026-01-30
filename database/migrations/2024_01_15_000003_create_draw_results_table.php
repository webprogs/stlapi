<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('draw_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('local_id');
            $table->date('draw_date');
            $table->string('draw_time', 10);
            $table->string('game_type', 10);
            $table->json('winning_numbers');
            $table->timestamp('device_created_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'local_id']);
            $table->index(['draw_date', 'draw_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_results');
    }
};
