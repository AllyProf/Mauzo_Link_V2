<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to change column type (works without doctrine/dbal)
        DB::statement('ALTER TABLE `sms_campaign_recipients` MODIFY COLUMN `sms_provider_response` TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to VARCHAR(255)
        DB::statement('ALTER TABLE `sms_campaign_recipients` MODIFY COLUMN `sms_provider_response` VARCHAR(255) NULL');
    }
};
