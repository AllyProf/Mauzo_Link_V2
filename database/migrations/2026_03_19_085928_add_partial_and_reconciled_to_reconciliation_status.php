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
        // On MySQL, altering an Enum requires a DB statement or Doctrine
        DB::statement("ALTER TABLE waiter_daily_reconciliations MODIFY COLUMN status ENUM('pending', 'submitted', 'verified', 'disputed', 'partial', 'reconciled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE waiter_daily_reconciliations MODIFY COLUMN status ENUM('pending', 'submitted', 'verified', 'disputed') DEFAULT 'pending'");
    }
};
