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
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('cascade');
            $table->string('target_type'); // 'monthly_bar', 'monthly_food', 'daily_staff'
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->date('target_date')->nullable(); // For daily targets
            $table->integer('month')->nullable(); // For monthly targets
            $table->integer('year')->nullable(); // For monthly targets
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'target_type']);
            $table->index(['month', 'year']);
            $table->index('target_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_targets');
    }
};
