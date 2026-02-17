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
        Schema::create('voice_clips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Oda nambari", "kutoka kwa mhudumu", etc.
            $table->enum('category', ['static', 'number', 'waiter', 'product'])->default('static');
            $table->string('audio_path'); // Path to audio file
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_clips');
    }
};







