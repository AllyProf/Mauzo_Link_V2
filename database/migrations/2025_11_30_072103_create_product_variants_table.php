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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('measurement'); // e.g., 350ml, 500ml, 1 litre
            $table->string('packaging'); // e.g., crates, cartons
            $table->integer('items_per_package'); // e.g., 24 bottles per crate
            $table->decimal('buying_price_per_unit', 10, 2)->default(0);
            $table->decimal('selling_price_per_unit', 10, 2)->default(0);
            $table->string('barcode')->nullable();
            $table->string('qr_code')->nullable(); // QR code data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
