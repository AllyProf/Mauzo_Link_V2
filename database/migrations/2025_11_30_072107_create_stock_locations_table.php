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
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->enum('location', ['warehouse', 'counter'])->default('warehouse');
            $table->integer('quantity')->default(0); // Current stock quantity
            $table->decimal('average_buying_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'product_variant_id', 'location']);
            $table->index('user_id');
            $table->index('product_variant_id');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
