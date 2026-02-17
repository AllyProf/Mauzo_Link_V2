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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->enum('payment_method', ['cash', 'mobile_money', 'card', 'bank_transfer'])->default('cash');
            $table->decimal('amount', 10, 2);
            $table->string('mobile_money_number')->nullable(); // Customer's phone number
            $table->string('transaction_reference')->nullable(); // M-Pesa transaction code (e.g., QGH7X8Y9Z)
            $table->string('transaction_id')->nullable(); // M-Pesa transaction ID
            $table->enum('payment_status', ['pending', 'verified', 'failed', 'refunded'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('payment_method');
            $table->index('payment_status');
            $table->index('transaction_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
