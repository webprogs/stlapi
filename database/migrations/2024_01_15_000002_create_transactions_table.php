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
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('local_id');
            $table->string('transaction_id', 50);
            $table->unsignedInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->json('numbers');
            $table->string('game_type', 10);
            $table->date('draw_date');
            $table->string('draw_time', 10);
            $table->string('payment_method', 50)->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('device_created_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'local_id']);
            $table->index('transaction_id');
            $table->index('draw_date');
            $table->index('game_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
