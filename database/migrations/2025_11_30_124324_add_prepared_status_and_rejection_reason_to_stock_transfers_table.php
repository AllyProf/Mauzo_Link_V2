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
        Schema::table('stock_transfers', function (Blueprint $table) {
            // Add rejection_reason column
            $table->text('rejection_reason')->nullable()->after('notes');
        });

        // Update enum to include 'prepared' status
        DB::statement("ALTER TABLE `stock_transfers` MODIFY COLUMN `status` ENUM('pending', 'approved', 'prepared', 'rejected', 'completed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        // Revert enum to original
        DB::statement("ALTER TABLE `stock_transfers` MODIFY COLUMN `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'");
    }
};
