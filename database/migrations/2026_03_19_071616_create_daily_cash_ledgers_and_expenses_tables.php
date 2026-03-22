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
        Schema::create('daily_cash_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The Business Owner
            $table->foreignId('accountant_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->date('ledger_date');
            
            // Core Accounting Math
            $table->decimal('opening_cash', 12, 2)->default(0); // Float brought forward from yesterday
            $table->decimal('total_cash_received', 12, 2)->default(0); // Only physical cash handed over mapped to this ledger
            $table->decimal('total_digital_received', 12, 2)->default(0); // Mobile, Banks
            $table->decimal('total_expenses', 12, 2)->default(0); // Total cash used for operations
            
            // Expected/Actual Math
            $table->decimal('expected_closing_cash', 12, 2)->default(0); // Opening + Cash Received - Expenses
            $table->decimal('actual_closing_cash', 12, 2)->nullable(); // What accountant physically counted at night
            
            // Final Split (End of Day Lock)
            $table->decimal('profit_generated', 12, 2)->default(0); // Calculated by stock reports Real-time Profit
            $table->decimal('profit_submitted_to_boss', 12, 2)->default(0); // Actual profit given to boss
            $table->decimal('carried_forward', 12, 2)->default(0); // Cycle money left for tomorrow's opening_cash
            
            $table->enum('status', ['open', 'closed', 'verified'])->default('open');
            $table->timestamp('closed_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'ledger_date']);
        });

        Schema::create('daily_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_cash_ledger_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The Business Owner
            $table->foreignId('logged_by')->nullable()->constrained('staff')->onDelete('set null'); // Usually Accountant
            
            $table->string('category'); // e.g., Utilities, Restocking, Transport, Office
            $table->text('description'); // Specific detail
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->default('cash'); // Cash taken from drawer

            $table->boolean('is_approved')->default(true); // For potential owner approval workflow
            
            $table->timestamps();
        });

        // Add detailed digital money tracking to financial_handovers
        Schema::table('financial_handovers', function (Blueprint $table) {
            // Store exact details like {"mpesa": 50000, "nmb": 120000, "mixx": 20000}
            $table->json('payment_breakdown')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_handovers', function (Blueprint $table) {
            $table->dropColumn('payment_breakdown');
        });
        
        Schema::dropIfExists('daily_expenses');
        Schema::dropIfExists('daily_cash_ledgers');
    }
};
