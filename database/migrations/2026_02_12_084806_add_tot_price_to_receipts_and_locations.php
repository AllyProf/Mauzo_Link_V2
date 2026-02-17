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
            $table->decimal('selling_price_per_tot', 10, 2)->nullable()->after('selling_price_per_unit');
        });

        Schema::table('stock_locations', function (Blueprint $table) {
            $table->decimal('selling_price_per_tot', 10, 2)->nullable()->after('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->dropColumn('selling_price_per_tot');
        });

        Schema::table('stock_locations', function (Blueprint $table) {
            $table->dropColumn('selling_price_per_tot');
        });
    }
};
