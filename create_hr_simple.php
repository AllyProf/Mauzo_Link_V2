<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$owner = User::where('role', '!=', 'admin')->first();
if (!$owner) die("No owner found\n");

$existing = Staff::where('email', 'hr@mauzo.com')->first();

if ($existing) {
    $existing->password = Hash::make('password');
    $existing->is_active = true;
    $existing->save();
    echo "Updated existing staff\n";
    $staff = $existing;
} else {
    $role = Role::where('user_id', $owner->id)->where('is_active', true)->first();
    if (!$role) die("No role found. Please create a role first.\n");
    
    $staffId = Staff::generateStaffId($owner->id);
    $staff = Staff::create([
        'user_id' => $owner->id,
        'staff_id' => $staffId,
        'full_name' => 'HR Manager',
        'email' => 'hr@mauzo.com',
        'gender' => 'other',
        'phone_number' => '+255710000000',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'salary_paid' => 0,
        'is_active' => true,
    ]);
    echo "Created staff: {$staff->staff_id}\n";
}

echo "\nCredentials:\n";
echo "Email: hr@mauzo.com\n";
echo "Password: password\n";
echo "Staff ID: {$staff->staff_id}\n";

