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
        Schema::table('kitchen_order_items', function (Blueprint $table) {
            // Add foreign key to food_items table
            $table->foreignId('food_item_id')->nullable()->after('order_id')->constrained('food_items')->onDelete('set null');
            
            // Add index for better query performance
            $table->index('food_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kitchen_order_items', function (Blueprint $table) {
            $table->dropForeign(['food_item_id']);
            $table->dropIndex(['food_item_id']);
            $table->dropColumn('food_item_id');
        });
    }
};
