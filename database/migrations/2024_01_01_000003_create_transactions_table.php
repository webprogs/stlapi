<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('local_user_id')->nullable();
            $table->enum('game_type', ['SWER2', 'SWER3', 'SWER4']);
            $table->json('numbers');
            $table->decimal('amount', 10, 2);
            $table->enum('draw_time', ['11AM', '4PM', '9PM']);
            $table->date('draw_date');
            $table->enum('status', ['pending', 'won', 'lost', 'claimed'])->default('pending');
            $table->decimal('win_amount', 10, 2)->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('local_created_at');
            $table->timestamps();

            $table->index(['draw_date', 'draw_time', 'game_type']);
            $table->index(['device_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
