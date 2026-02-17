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
        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('food_item_name'); // Name of the food item
            $table->string('variant_name')->nullable(); // e.g., "6 pieces", "Large"
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Price at time of order
            $table->decimal('total_price', 10, 2); // Calculated: quantity × unit_price
            $table->text('special_instructions')->nullable(); // Special instructions for this item
            $table->enum('status', ['pending', 'preparing', 'ready', 'completed'])->default('pending');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->onDelete('set null'); // Chef who prepared
            $table->timestamp('prepared_at')->nullable(); // When chef started preparing
            $table->timestamp('ready_at')->nullable(); // When marked as ready
            $table->timestamps();
            
            $table->index('order_id');
            $table->index('status');
            $table->index('prepared_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_items');
    }
};
