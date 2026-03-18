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
        Schema::create('petty_cash_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Business Owner ID
            $table->unsignedBigInteger('issued_by'); // Staff/Accountant
            $table->unsignedBigInteger('staff_id'); // Recipient (Chef, Stock Keeper)
            $table->decimal('amount', 15, 2);
            $table->string('purpose'); // Reason for issuing
            $table->enum('status', ['issued', 'completed', 'cancelled'])->default('issued');
            $table->date('issue_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('issued_by')->references('id')->on('users')->onDelete('cascade'); // Simplified to user ID
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_issues');
    }
};
