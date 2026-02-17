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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('biometric_enroll_id')->nullable()->after('staff_id');
            $table->string('verify_mode')->nullable()->after('ip_address'); // Fingerprint, Face, etc.
            $table->string('device_ip')->nullable()->after('verify_mode');
            $table->boolean('is_biometric')->default(false)->after('device_ip');
            
            $table->index('biometric_enroll_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['biometric_enroll_id']);
            $table->dropColumn(['biometric_enroll_id', 'verify_mode', 'device_ip', 'is_biometric']);
        });
    }
};
