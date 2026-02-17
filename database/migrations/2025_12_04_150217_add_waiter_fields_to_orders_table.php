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
            // Add waiter (staff) who placed the order
            $table->foreignId('waiter_id')->nullable()->after('created_by')->constrained('staff')->onDelete('set null');
            // Track order source (web dashboard, kiosk, or mobile app)
            $table->enum('order_source', ['web', 'kiosk', 'mobile'])->default('web')->after('waiter_id');
            // Track which waiter collected payment
            $table->foreignId('paid_by_waiter_id')->nullable()->after('served_by')->constrained('staff')->onDelete('set null');
            // Update status enum to include 'prepared'
            $table->enum('status', ['pending', 'preparing', 'prepared', 'served', 'cancelled'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['waiter_id']);
            $table->dropForeign(['paid_by_waiter_id']);
            $table->dropColumn(['waiter_id', 'order_source', 'paid_by_waiter_id']);
            // Revert status enum
            $table->enum('status', ['pending', 'preparing', 'served', 'cancelled'])->default('pending')->change();
        });
    }
};
