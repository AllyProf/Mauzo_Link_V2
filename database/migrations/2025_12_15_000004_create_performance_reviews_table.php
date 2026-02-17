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
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained()->onDelete('cascade');
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->date('review_date');
            $table->foreignId('reviewer_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->decimal('performance_rating', 3, 1)->default(0); // 1.0 to 5.0
            $table->text('goals_achieved')->nullable();
            $table->text('goals_pending')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('training_needs')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('next_review_date')->nullable();
            $table->enum('status', ['draft', 'completed', 'acknowledged'])->default('draft');
            $table->timestamp('staff_acknowledged_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'staff_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};

