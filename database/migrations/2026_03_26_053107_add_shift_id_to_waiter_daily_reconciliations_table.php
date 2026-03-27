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
            $table->unsignedBigInteger('staff_shift_id')->nullable()->after('waiter_id');
            
            // Drop old unique index if it exists
            $table->dropUnique('wd_waiter_date_type_unique');
            
            // Add new unique index including shift_id
            $table->unique(['waiter_id', 'reconciliation_date', 'reconciliation_type', 'staff_shift_id'], 'wd_waiter_shift_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiter_daily_reconciliations', function (Blueprint $table) {
            $table->dropUnique('wd_waiter_shift_date_unique');
            $table->unique(['waiter_id', 'reconciliation_date', 'reconciliation_type'], 'wd_waiter_date_type_unique');
            $table->dropColumn('staff_shift_id');
        });
    }
};
