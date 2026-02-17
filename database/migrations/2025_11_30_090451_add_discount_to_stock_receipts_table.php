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
        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->enum('discount_type', ['fixed', 'percent'])->nullable()->after('total_profit');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_type');
            $table->decimal('discount_value', 10, 2)->default(0)->after('discount_amount'); // Calculated discount value
            $table->decimal('final_buying_cost', 10, 2)->after('discount_value'); // Total buying cost after discount
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_amount', 'discount_value', 'final_buying_cost']);
        });
    }
};
