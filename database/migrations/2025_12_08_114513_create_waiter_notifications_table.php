<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('waiter_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waiter_id')->constrained('staff')->onDelete('cascade');
            $table->string('type'); // 'payment_recorded', 'reconciliation_verified', etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data (order_ids, amounts, etc.)
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('waiter_id');
            $table->index('is_read');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_notifications');
    }
};
