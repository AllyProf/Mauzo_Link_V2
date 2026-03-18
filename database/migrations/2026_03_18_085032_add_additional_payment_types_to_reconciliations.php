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
            $table->decimal('bank_collected', 10, 2)->default(0)->after('mobile_money_collected');
            $table->decimal('card_collected', 10, 2)->default(0)->after('bank_collected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waiter_daily_reconciliations', function (Blueprint $table) {
            //
        });
    }
};
