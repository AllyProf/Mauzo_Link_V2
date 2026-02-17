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
        Schema::create('ingredient_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('restrict');
            $table->foreignId('ingredient_receipt_id')->constrained('ingredient_receipts')->onDelete('restrict');
            $table->string('batch_number')->nullable(); // Batch number for tracking
            $table->decimal('initial_quantity', 10, 2); // Initial quantity in this batch
            $table->decimal('remaining_quantity', 10, 2); // Remaining quantity (for FIFO tracking)
            $table->string('unit', 50); // Unit of measurement
            $table->date('expiry_date')->nullable(); // Expiry date
            $table->date('received_date'); // Date when batch was received
            $table->decimal('cost_per_unit', 10, 2); // Cost per unit at time of receipt
            $table->string('location')->nullable(); // Storage location
            $table->enum('status', ['active', 'expired', 'depleted', 'wasted'])->default('active');
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('ingredient_id');
            $table->index('ingredient_receipt_id');
            $table->index('batch_number');
            $table->index('expiry_date');
            $table->index('status');
            $table->index('received_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_batches');
    }
};
