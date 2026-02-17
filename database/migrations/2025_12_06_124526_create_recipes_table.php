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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->foreignId('food_item_id')->nullable()->constrained('food_items')->onDelete('set null'); // Linked food item
            $table->string('name'); // Recipe name (usually same as food item)
            $table->text('description')->nullable(); // Recipe description
            $table->text('instructions')->nullable(); // Cooking instructions
            $table->integer('prep_time_minutes')->nullable(); // Preparation time
            $table->integer('cook_time_minutes')->nullable(); // Cooking time
            $table->integer('servings')->nullable(); // Number of servings
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('food_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
