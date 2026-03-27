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
        Schema::table('financial_handovers', function (Blueprint $table) {
            $table->decimal('circulation_money', 15, 2)->default(0)->after('amount')->comment('Money used for business operations (float)');
            $table->decimal('profit_amount', 15, 2)->default(0)->after('circulation_money')->comment('Calculated profit for this handover');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_handovers', function (Blueprint $table) {
            $table->dropColumn(['circulation_money', 'profit_amount']);
        });
    }
};
