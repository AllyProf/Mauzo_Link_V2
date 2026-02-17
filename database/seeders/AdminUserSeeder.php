<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@mauzolink.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@mauzolink.com',
                'password' => Hash::make('Admin@2024'), // Change this password after first login!
                'role' => 'admin',
                'email_verified_at' => now(),
                'business_name' => 'EmCa Technologies',
                'business_type' => 'software_company',
                'phone' => '+255749719998',
                'address' => 'Ben Bella Street, Moshi',
                'city' => 'Moshi',
                'country' => 'Tanzania',
            ]
        );

        // Update role if user already exists
        if ($admin->wasRecentlyCreated === false) {
            $admin->update(['role' => 'admin']);
        }

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@mauzolink.com');
        $this->command->info('Password: Admin@2024');
        $this->command->warn('⚠️  IMPORTANT: Please change the password after first login!');
    }
}
