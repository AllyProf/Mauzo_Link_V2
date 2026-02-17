<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@mauzo.com'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@mauzo.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'business_name' => 'MauzoLink Admin',
                'is_configured' => true,
            ]
        );
        $this->command->info("✓ Super Admin: superadmin@mauzo.com / password");

        // Create Owner
        $owner = User::firstOrCreate(
            ['email' => 'owner@mauzo.com'],
            [
                'name' => 'Business Owner',
                'email' => 'owner@mauzo.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'email_verified_at' => now(),
                'business_name' => 'MauzoLink Business',
                'is_configured' => true,
            ]
        );
        $this->command->info("✓ Owner: owner@mauzo.com / password");

        // Get all owners (non-admin users)
        $owners = User::where('role', '!=', 'admin')->get();

        if ($owners->isEmpty()) {
            $this->command->warn('No owners found. Created owner@mauzo.com');
        }

        foreach ($owners as $owner) {
            $this->command->info("\nCreating staff for owner: {$owner->email}");

            // Create roles
            $roles = [
                [
                    'slug' => 'manager',
                    'name' => 'Manager',
                    'description' => 'General Manager',
                ],
                [
                    'slug' => 'hr-manager',
                    'name' => 'HR Manager',
                    'description' => 'Human Resources Manager',
                ],
                [
                    'slug' => 'counter',
                    'name' => 'Counter',
                    'description' => 'Counter Staff',
                ],
                [
                    'slug' => 'stock-keeper',
                    'name' => 'Stock Keeper',
                    'description' => 'Stock Keeper',
                ],
                [
                    'slug' => 'accountant',
                    'name' => 'Accountant',
                    'description' => 'Accountant',
                ],
                [
                    'slug' => 'marketer',
                    'name' => 'Marketer',
                    'description' => 'Marketing Staff',
                ],
            ];

            $createdRoles = [];
            foreach ($roles as $roleData) {
                $role = Role::firstOrCreate(
                    [
                        'user_id' => $owner->id,
                        'slug' => $roleData['slug'],
                    ],
                    [
                        'name' => $roleData['name'],
                        'description' => $roleData['description'],
                        'is_active' => true,
                    ]
                );
                $createdRoles[$roleData['slug']] = $role;
            }

            // Attach permissions to roles
            $allPermissions = Permission::all();
            
            // Manager gets all permissions
            $createdRoles['manager']->permissions()->syncWithoutDetaching($allPermissions->pluck('id'));
            
            // HR Manager gets HR permissions
            $hrPermissions = Permission::where('module', 'hr')->get();
            if ($hrPermissions->count() > 0) {
                $createdRoles['hr-manager']->permissions()->syncWithoutDetaching($hrPermissions->pluck('id'));
            }

            // Counter gets bar_orders, bar_payments, bar_tables, stock_transfer.view, products, and stock_receipt permissions
            $counterPerms = Permission::whereIn('module', ['bar_orders', 'bar_payments', 'bar_tables', 'products', 'stock_receipt'])->get();
            // Also add stock_transfer.view permission
            $stockTransferViewPerm = Permission::where('module', 'stock_transfer')->where('action', 'view')->first();
            if ($stockTransferViewPerm) {
                $counterPerms->push($stockTransferViewPerm);
            }
            $createdRoles['counter']->permissions()->syncWithoutDetaching($counterPerms->pluck('id'));

            // Stock Keeper gets inventory, stock_receipt, stock_transfer, and products permissions
            $stockPerms = Permission::whereIn('module', ['inventory', 'stock_receipt', 'stock_transfer', 'products'])->get();
            $createdRoles['stock-keeper']->permissions()->syncWithoutDetaching($stockPerms->pluck('id'));

            // Accountant gets finance, reports permissions
            $accountantPerms = Permission::whereIn('module', ['finance', 'reports'])->get();
            $createdRoles['accountant']->permissions()->syncWithoutDetaching($accountantPerms->pluck('id'));

            // Marketer gets marketing permissions
            $marketingPerms = Permission::where('module', 'marketing')->get();
            $createdRoles['marketer']->permissions()->syncWithoutDetaching($marketingPerms->pluck('id'));

            // Create staff members
            $staffMembers = [
                [
                    'email' => 'hr@mauzo.com',
                    'name' => 'HR Manager',
                    'phone' => '+255710000000',
                    'role_slug' => 'hr-manager',
                ],
                [
                    'email' => 'manager@mauzo.com',
                    'name' => 'Manager',
                    'phone' => '+255710000001',
                    'role_slug' => 'manager',
                ],
                [
                    'email' => 'counter@mauzo.com',
                    'name' => 'Counter Staff',
                    'phone' => '+255710000002',
                    'role_slug' => 'counter',
                ],
                [
                    'email' => 'stockkeeper@mauzo.com',
                    'name' => 'Stock Keeper',
                    'phone' => '+255710000003',
                    'role_slug' => 'stock-keeper',
                ],
                [
                    'email' => 'accountant@mauzo.com',
                    'name' => 'Accountant',
                    'phone' => '+255710000004',
                    'role_slug' => 'accountant',
                ],
                [
                    'email' => 'marketer@mauzo.com',
                    'name' => 'Marketer',
                    'phone' => '+255710000005',
                    'role_slug' => 'marketer',
                ],
            ];

            foreach ($staffMembers as $staffData) {
                $existingStaff = Staff::where('email', $staffData['email'])
                    ->where('user_id', $owner->id)
                    ->first();

                if ($existingStaff) {
                    $existingStaff->password = Hash::make('password');
                    $existingStaff->is_active = true;
                    $existingStaff->role_id = $createdRoles[$staffData['role_slug']]->id;
                    $existingStaff->save();
                    $this->command->info("✓ Updated {$staffData['name']}: {$staffData['email']} / password");
                } else {
                    // Check if email exists for another owner
                    $emailExists = Staff::where('email', $staffData['email'])->exists();
                    if ($emailExists) {
                        // Use owner-specific email
                        $ownerEmail = str_replace('@mauzo.com', '@' . str_replace(' ', '', strtolower($owner->name)) . '.com', $staffData['email']);
                        $staffData['email'] = $ownerEmail;
                    }

                    // Generate unique staff_id
                    $staffId = Staff::generateStaffId($owner->id);
                    $attempts = 0;
                    while (Staff::where('staff_id', $staffId)->exists() && $attempts < 100) {
                        // Get last staff globally and increment
                        $lastStaff = Staff::orderBy('staff_id', 'desc')->first();
                        if ($lastStaff) {
                            $lastNumber = (int) substr($lastStaff->staff_id, -4);
                            $newNumber = $lastNumber + 1;
                        } else {
                            $newNumber = 1;
                        }
                        $staffId = 'STF' . date('Y') . date('m') . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
                        $attempts++;
                    }

                    $staff = Staff::create([
                        'user_id' => $owner->id,
                        'staff_id' => $staffId,
                        'full_name' => $staffData['name'],
                        'email' => $staffData['email'],
                        'gender' => 'other',
                        'phone_number' => $staffData['phone'],
                        'password' => Hash::make('password'),
                        'role_id' => $createdRoles[$staffData['role_slug']]->id,
                        'salary_paid' => 0,
                        'is_active' => true,
                    ]);
                    $this->command->info("✓ Created {$staffData['name']}: {$staffData['email']} / password");
                }
            }
        }

        $this->command->info("\n========================================\n");
        $this->command->info("All Users & Staff Created!\n");
        $this->command->info("========================================\n");
        $this->command->info("Super Admin: superadmin@mauzo.com / password\n");
        $this->command->info("Owner: owner@mauzo.com / password\n");
        $this->command->info("HR Manager: hr@mauzo.com / password\n");
        $this->command->info("Manager: manager@mauzo.com / password\n");
        $this->command->info("Counter: counter@mauzo.com / password\n");
        $this->command->info("Stock Keeper: stockkeeper@mauzo.com / password\n");
        $this->command->info("Accountant: accountant@mauzo.com / password\n");
        $this->command->info("Marketer: marketer@mauzo.com / password\n");
        $this->command->info("========================================\n");
    }
}

