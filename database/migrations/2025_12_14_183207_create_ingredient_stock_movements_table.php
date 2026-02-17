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
        Schema::create('ingredient_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('restrict');
            // Note: ingredient_batches table must exist before this foreign key can be added
            // We'll add the foreign key in a later migration
            $table->unsignedBigInteger('ingredient_batch_id')->nullable();
            $table->enum('movement_type', ['receipt', 'usage', 'adjustment', 'waste', 'transfer'])->default('usage');
            $table->decimal('quantity', 10, 2); // Quantity moved (positive for receipt, negative for usage)
            $table->string('unit', 50); // Unit of measurement
            $table->string('from_location')->nullable(); // Source location
            $table->string('to_location')->nullable(); // Destination location
            $table->string('reference_type')->nullable(); // Model class (e.g., IngredientReceipt, KitchenOrderItem)
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the reference model
            $table->text('notes')->nullable(); // Additional notes
            $table->foreignId('created_by')->nullable()->constrained('staff')->onDelete('set null'); // Staff who created this movement
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('ingredient_id');
            $table->index('ingredient_batch_id');
            $table->index('movement_type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_stock_movements');
    }
};
