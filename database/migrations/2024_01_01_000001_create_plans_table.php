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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Standard, Advanced
            $table->string('slug')->unique(); // free, standard, advanced
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0); // Monthly price
            $table->integer('trial_days')->default(30); // Free trial period
            $table->json('features')->nullable(); // Array of features
            $table->integer('max_locations')->default(1); // Number of locations allowed
            $table->integer('max_users')->default(1); // Number of users allowed
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};













