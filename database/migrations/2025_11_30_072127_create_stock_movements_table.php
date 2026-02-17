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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->enum('movement_type', ['receipt', 'transfer', 'sale', 'adjustment', 'return'])->default('receipt');
            $table->enum('from_location', ['warehouse', 'counter', 'supplier'])->nullable();
            $table->enum('to_location', ['warehouse', 'counter', 'customer'])->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('reference_type')->nullable(); // e.g., StockReceipt, StockTransfer, Order
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('product_variant_id');
            $table->index('movement_type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
