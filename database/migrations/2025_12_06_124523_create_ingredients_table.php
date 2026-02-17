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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->string('name'); // Ingredient name
            $table->string('unit'); // Unit of measurement (kg, liter, piece, etc.)
            $table->decimal('current_stock', 10, 2)->default(0); // Current stock level
            $table->decimal('min_stock_level', 10, 2)->default(0); // Minimum stock level for alerts
            $table->decimal('max_stock_level', 10, 2)->nullable(); // Maximum stock level
            $table->string('location')->nullable(); // Storage location
            $table->decimal('cost_per_unit', 10, 2)->nullable(); // Cost per unit
            $table->text('supplier_info')->nullable(); // Supplier information
            $table->date('expiry_date')->nullable(); // Expiry date if applicable
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
