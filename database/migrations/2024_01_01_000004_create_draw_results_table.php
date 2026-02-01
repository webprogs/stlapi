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
            $table->date('draw_date');
            $table->enum('draw_time', ['11AM', '4PM', '9PM']);
            $table->enum('game_type', ['SWER2', 'SWER3', 'SWER4']);
            $table->json('winning_numbers');
            $table->foreignId('set_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['draw_date', 'draw_time', 'game_type']);
            $table->index('draw_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('draw_results');
    }
};
