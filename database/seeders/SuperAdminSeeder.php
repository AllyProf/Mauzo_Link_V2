<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the main business owner
        $owner = User::where('role', 'customer')->first();
        
        if (!$owner) {
            $this->command->error('No business owner found in the database. Please create one first.');
            return;
        }

        $this->command->info("Configuring Super Admin for business: {$owner->business_name}");

        // 1. Create the Super Admin Role
        $role = Role::updateOrCreate(
            [
                'user_id' => $owner->id,
                'slug' => 'super-admin'
            ],
            [
                'name' => 'Super Admin',
                'description' => 'Maximum system access with staff password reset capabilities',
                'is_active' => true,
            ]
        );

        // 2. Assign All Permissions to the Role
        $allPermissions = Permission::all();
        $role->permissions()->sync($allPermissions->pluck('id'));
        $this->command->info("✓ Super Admin role created/updated with " . $allPermissions->count() . " permissions.");

        // 3. Create the Super Admin Staff Member
        $email = 'superadmin@' . str_replace(' ', '', strtolower($owner->business_name)) . '.com';
        // Fallback email if business name is empty
        if ($email == 'superadmin@.com') {
            $email = 'superadmin@mauzo.com';
        }

        $staffId = Staff::generateStaffId($owner->id);

        $staff = Staff::updateOrCreate(
            [
                'user_id' => $owner->id,
                'email' => $email
            ],
            [
                'staff_id' => $staffId,
                'full_name' => 'System Super Admin',
                'gender' => 'other',
                'phone_number' => '0700000000',
                'password' => Hash::make('admin123'), // Default password
                'role_id' => $role->id,
                'salary_paid' => 0,
                'is_active' => true,
            ]
        );

        $this->command->info("\n========================================");
        $this->command->info("SUPER ADMIN CREDENTIALS CREATED");
        $this->command->info("========================================");
        $this->command->info("Login Email: {$email}");
        $this->command->info("Password: admin123");
        $this->command->info("Role: Super Admin");
        $this->command->info("========================================\n");
    }
}
