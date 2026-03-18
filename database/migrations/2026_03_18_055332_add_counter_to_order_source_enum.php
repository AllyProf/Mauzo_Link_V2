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
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `order_source` ENUM('web', 'kiosk', 'mobile', 'counter') DEFAULT 'web'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `order_source` ENUM('web', 'kiosk', 'mobile') DEFAULT 'web'");
    }
};
