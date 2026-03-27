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
            $table->unsignedBigInteger('staff_shift_id')->nullable()->after('accountant_id');
            
            // Optional: add foreign key if you want
            // $table->foreign('staff_shift_id')->references('id')->on('staff_shifts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_handovers', function (Blueprint $table) {
            $table->dropColumn('staff_shift_id');
        });
    }
};
