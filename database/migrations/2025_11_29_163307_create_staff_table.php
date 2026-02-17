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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner who registered this staff
            $table->string('staff_id')->unique(); // Auto-generated staff ID
            $table->string('full_name');
            $table->string('email')->unique();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('nida')->nullable(); // Optional
            $table->string('phone_number');
            $table->string('password'); // Auto-generated from last name
            $table->string('next_of_kin')->nullable(); // Optional
            $table->string('next_of_kin_phone')->nullable(); // Optional
            $table->string('location_branch')->nullable(); // Optional
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null'); // Role from business configuration
            $table->decimal('salary_paid', 10, 2); // Salary amount
            $table->string('religion')->nullable();
            $table->string('nida_attachment')->nullable(); // File path for NIDA document
            $table->string('voter_id_attachment')->nullable(); // File path for Voter ID
            $table->string('professional_certificate_attachment')->nullable(); // File path for professional certificate
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('staff_id');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
