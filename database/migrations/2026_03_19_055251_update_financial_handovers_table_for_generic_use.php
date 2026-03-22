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
        Schema::table('financial_handovers', function (Blueprint $table) {
            $table->string('handover_type')->default('accountant_to_owner')->after('accountant_id');
            $table->unsignedBigInteger('recipient_id')->nullable()->after('handover_type'); // Staff ID or User ID (Owner)
            $table->string('department')->nullable()->after('recipient_id'); // food, bar
            $table->string('payment_method')->default('cash')->after('amount'); // mainly cash for physical handovers
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_handovers', function (Blueprint $table) {
            $table->dropColumn(['handover_type', 'recipient_id', 'department', 'payment_method']);
        });
    }
};
