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
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->string('name'); // Food item name
            $table->string('variant_name')->nullable(); // e.g., "6 pieces", "Large", "Regular"
            $table->text('description')->nullable(); // Description of the food item
            $table->string('image')->nullable(); // Image path
            $table->decimal('price', 10, 2); // Selling price
            $table->integer('prep_time_minutes')->nullable(); // Estimated prep time
            $table->boolean('is_available')->default(true); // Available for ordering
            $table->integer('sort_order')->default(0); // Display order
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_items');
    }
};
