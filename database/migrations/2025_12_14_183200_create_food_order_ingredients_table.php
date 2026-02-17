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
        Schema::create('food_order_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_order_item_id')->constrained('kitchen_order_items')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('restrict');
            // Note: ingredient_batches table must exist before this migration runs
            // If it doesn't exist, this will be added in a later migration
            $table->unsignedBigInteger('ingredient_batch_id')->nullable();
            $table->decimal('quantity_used', 10, 2); // Quantity of ingredient used
            $table->string('unit', 50); // Unit of measurement (kg, liter, piece, etc.)
            $table->decimal('cost_at_time', 10, 2)->nullable(); // Cost per unit at time of use
            $table->decimal('total_cost', 10, 2)->nullable(); // Total cost: quantity_used × cost_at_time
            $table->text('notes')->nullable(); // Any notes about this ingredient usage
            $table->timestamps();
            
            $table->index('kitchen_order_item_id');
            $table->index('ingredient_id');
            $table->index('ingredient_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_order_ingredients');
    }
};
