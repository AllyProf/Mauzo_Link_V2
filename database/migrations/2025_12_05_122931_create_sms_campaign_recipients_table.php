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
        Schema::create('sms_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('sms_campaigns')->onDelete('cascade');
            $table->string('phone_number'); // Customer phone number
            $table->string('customer_name')->nullable();
            $table->text('personalized_message')->nullable(); // Message with personalization applied
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'bounced'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('sms_provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('cost', 8, 2)->default(0);
            $table->timestamps();
            
            $table->index('campaign_id');
            $table->index('phone_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_campaign_recipients');
    }
};
