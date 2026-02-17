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
        // MySQL doesn't support direct enum modification, so we need to use raw SQL
        // First, check if the column exists and modify it
        if (Schema::hasColumn('orders', 'order_source')) {
            // Use raw SQL to alter the enum
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `order_source` ENUM('web', 'kiosk', 'mobile') DEFAULT 'web'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum values
        if (Schema::hasColumn('orders', 'order_source')) {
            // First, update any 'mobile' values to 'web' to avoid data loss
            DB::table('orders')
                ->where('order_source', 'mobile')
                ->update(['order_source' => 'web']);
            
            // Then modify the enum back to original
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `order_source` ENUM('web', 'kiosk') DEFAULT 'web'");
        }
    }
};
