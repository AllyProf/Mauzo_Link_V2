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
        Schema::create('waiter_daily_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Restaurant owner
            $table->foreignId('waiter_id')->constrained('staff')->onDelete('cascade'); // Waiter (staff)
            $table->date('reconciliation_date');
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('cash_collected', 10, 2)->default(0);
            $table->decimal('mobile_money_collected', 10, 2)->default(0);
            $table->decimal('expected_amount', 10, 2)->default(0);
            $table->decimal('submitted_amount', 10, 2)->default(0);
            $table->decimal('difference', 10, 2)->default(0);
            $table->enum('status', ['pending', 'submitted', 'verified', 'disputed'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'waiter_id', 'reconciliation_date'], 'wd_reconciliation_date_idx');
            $table->index('status');
            $table->unique(['waiter_id', 'reconciliation_date'], 'wd_waiter_date_unique'); // One reconciliation per waiter per day
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_daily_reconciliations');
    }
};
