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
        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('can_sell_in_tots')->default(false)->after('selling_price_per_unit');
            $table->integer('total_tots')->nullable()->after('can_sell_in_tots'); // Number of tots per bottle
            $table->decimal('selling_price_per_tot', 10, 2)->nullable()->after('total_tots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['can_sell_in_tots', 'total_tots', 'selling_price_per_tot']);
        });
    }
};
