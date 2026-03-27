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
        Schema::table('waiter_daily_reconciliations', function (Blueprint $table) {
            // Drop the old date-only unique index that's causing the conflict
            $table->dropUnique('wd_waiter_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('waiter_daily_reconciliations', function (Blueprint $table) {
            // Restore it for rollback purposes if possible
            $table->unique(['waiter_id', 'reconciliation_date'], 'wd_waiter_date_unique');
        });
    }
};
