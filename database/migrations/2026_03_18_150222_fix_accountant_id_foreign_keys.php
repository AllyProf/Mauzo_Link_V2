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
            $table->dropForeign(['accountant_id']);
            $table->foreign('accountant_id')->references('id')->on('staff')->onDelete('cascade');
        });

        Schema::table('cash_topups', function (Blueprint $table) {
            $table->dropForeign(['accountant_id']);
            $table->foreign('accountant_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('financial_handovers', function (Blueprint $table) {
            $table->dropForeign(['accountant_id']);
            $table->foreign('accountant_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('cash_topups', function (Blueprint $table) {
            $table->dropForeign(['accountant_id']);
            $table->foreign('accountant_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
