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
        // Modify the enum to include 'pending'
        DB::statement("ALTER TABLE `subscriptions` MODIFY COLUMN `status` ENUM('trial', 'active', 'expired', 'cancelled', 'pending') DEFAULT 'trial'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any 'pending' statuses to 'trial' to avoid data loss
        DB::statement("UPDATE `subscriptions` SET `status` = 'trial' WHERE `status` = 'pending'");
        
        // Revert the enum back to original
        DB::statement("ALTER TABLE `subscriptions` MODIFY COLUMN `status` ENUM('trial', 'active', 'expired', 'cancelled') DEFAULT 'trial'");
    }
};
