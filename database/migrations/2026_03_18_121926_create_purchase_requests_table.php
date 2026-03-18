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
        Schema::create('purchase_requests', function (Blueprint $header) {
            $header->id();
            $header->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner/Business
            $header->foreignId('staff_id')->constrained('staff')->onDelete('cascade'); // Requester
            $header->string('request_number')->unique();
            $header->text('items_list'); // List of items needed (json or text)
            $header->decimal('estimated_amount', 15, 2);
            $header->decimal('issued_amount', 15, 2)->nullable();
            $header->enum('status', ['pending', 'approved', 'issued', 'rejected'])->default('pending');
            $header->foreignId('processed_by')->nullable()->constrained('staff')->onDelete('set null'); // Accountant
            $header->timestamp('processed_at')->nullable();
            $header->text('reason')->nullable(); // Rejection reason
            $header->text('notes')->nullable();
            $header->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
