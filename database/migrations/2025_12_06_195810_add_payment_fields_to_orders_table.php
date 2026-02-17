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
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'mobile_money', 'card', 'bank_transfer'])->nullable()->after('payment_status');
            $table->string('mobile_money_number')->nullable()->after('payment_method'); // Customer's phone
            $table->string('transaction_reference')->nullable()->after('mobile_money_number'); // M-Pesa transaction code
            $table->foreignId('reconciliation_id')->nullable()->after('transaction_reference')->constrained('waiter_daily_reconciliations')->onDelete('set null');
            
            $table->index('payment_method');
            $table->index('reconciliation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['reconciliation_id']);
            $table->dropColumn(['payment_method', 'mobile_money_number', 'transaction_reference', 'reconciliation_id']);
        });
    }
};
