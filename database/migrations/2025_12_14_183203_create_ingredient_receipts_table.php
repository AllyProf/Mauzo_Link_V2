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
        Schema::create('ingredient_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->string('receipt_number')->unique(); // e.g., IR2025120001
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->decimal('quantity_received', 10, 2); // Quantity received
            $table->string('unit', 50); // Unit of measurement
            $table->decimal('cost_per_unit', 10, 2); // Cost per unit
            $table->decimal('total_cost', 10, 2); // Total cost: quantity × cost_per_unit
            $table->date('expiry_date')->nullable(); // Expiry date if applicable
            $table->date('received_date'); // Date when ingredient was received
            $table->string('batch_number')->nullable(); // Batch number for tracking
            $table->string('location')->nullable(); // Storage location
            $table->text('notes')->nullable(); // Additional notes
            $table->foreignId('received_by')->nullable()->constrained('staff')->onDelete('set null'); // Staff who received
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('ingredient_id');
            $table->index('supplier_id');
            $table->index('receipt_number');
            $table->index('received_date');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_receipts');
    }
};
