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
        // Add foreign key constraint after ingredient_batches table is created
        Schema::table('food_order_ingredients', function (Blueprint $table) {
            $table->foreign('ingredient_batch_id')
                  ->references('id')
                  ->on('ingredient_batches')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_order_ingredients', function (Blueprint $table) {
            $table->dropForeign(['ingredient_batch_id']);
        });
    }
};
