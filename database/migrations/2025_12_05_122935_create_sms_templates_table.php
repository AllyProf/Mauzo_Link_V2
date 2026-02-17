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
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // null for system templates
            $table->string('name'); // Template name
            $table->text('content'); // Template content with placeholders
            $table->enum('category', ['holiday', 'promotion', 'update', 'engagement', 'custom'])->default('custom');
            $table->enum('language', ['sw', 'en', 'both'])->default('both');
            $table->json('placeholders')->nullable(); // Available placeholders: {customer_name}, etc.
            $table->text('description')->nullable();
            $table->boolean('is_system_template')->default(false); // System pre-built templates
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('category');
            $table->index('is_system_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
