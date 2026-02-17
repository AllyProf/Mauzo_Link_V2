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
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('receipt_number')->unique();
            $table->integer('quantity_received'); // In packages (e.g., 10 crates)
            $table->integer('total_units'); // Calculated: quantity_received × items_per_package
            $table->decimal('buying_price_per_unit', 10, 2);
            $table->decimal('selling_price_per_unit', 10, 2);
            $table->decimal('total_buying_cost', 10, 2); // Calculated
            $table->decimal('total_selling_value', 10, 2); // Calculated
            $table->decimal('profit_per_unit', 10, 2); // Calculated
            $table->decimal('total_profit', 10, 2); // Calculated
            $table->date('received_date');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null'); // Staff who received
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('product_variant_id');
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_receipts');
    }
};
