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
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->timestamp('audited_at')->nullable()->after('verified_at');
            $table->unsignedBigInteger('audited_by')->nullable()->after('audited_at');
            
            $table->foreign('audited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['audited_by']);
            $table->dropColumn(['audited_at', 'audited_by']);
        });
    }
};
