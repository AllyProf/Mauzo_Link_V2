<?php
/**
 * Create Simple Marketing Staff with Easy Credentials
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$owner = User::where('email', 'admin@medalion.com')->first();

if (!$owner) {
    echo "❌ Owner not found\n";
    exit(1);
}

// Get Marketing role
$marketingRole = Role::where('user_id', $owner->id)
    ->where('name', 'Marketing')
    ->first();

if (!$marketingRole) {
    echo "❌ Marketing role not found\n";
    exit(1);
}

// Create a simple marketing staff
$email = 'marketer@medalion.com';
$password = 'MARKETER'; // Simple password

// Check if already exists
$existing = Staff::where('email', $email)->first();
if ($existing) {
    echo "Staff already exists. Resetting password...\n";
    $existing->password = Hash::make($password);
    $existing->is_active = true;
    $existing->save();
    $staff = $existing;
} else {
    $staffId = Staff::generateStaffId($owner->id);
    $staff = Staff::create([
        'user_id' => $owner->id,
        'staff_id' => $staffId,
        'full_name' => 'Marketing Staff',
        'email' => $email,
        'gender' => 'other',
        'phone_number' => '+255710000000',
        'password' => Hash::make($password),
        'role_id' => $marketingRole->id,
        'salary_paid' => 0,
        'is_active' => true,
    ]);
}

// Verify
$verify = Hash::check($password, $staff->password);
$userExists = User::where('email', $email)->exists();

echo "========================================\n";
echo "Marketing Staff Created/Updated\n";
echo "========================================\n\n";

echo "LOGIN CREDENTIALS:\n";
echo "  Email: {$email}\n";
echo "  Password: {$password}\n\n";

echo "VERIFICATION:\n";
echo "  Password Hash Match: " . ($verify ? "✓ YES" : "✗ NO") . "\n";
echo "  Email in Users Table: " . ($userExists ? "✗ YES (BLOCKS LOGIN!)" : "✓ NO") . "\n";
echo "  Staff Active: " . ($staff->is_active ? "✓ YES" : "✗ NO") . "\n";
echo "  Role: " . ($staff->role ? $staff->role->name : "✗ NONE") . "\n\n";

if ($userExists) {
    echo "⚠️  WARNING: Email exists in users table!\n";
    echo "   This will block staff login.\n";
    echo "   Creating with different email...\n\n";
    
    $email = 'marketing-staff@medalion.com';
    $existing2 = Staff::where('email', $email)->first();
    if ($existing2) {
        $existing2->password = Hash::make($password);
        $existing2->is_active = true;
        $existing2->save();
        $staff = $existing2;
    } else {
        $staffId = Staff::generateStaffId($owner->id);
        $staff = Staff::create([
            'user_id' => $owner->id,
            'staff_id' => $staffId,
            'full_name' => 'Marketing Staff',
            'email' => $email,
            'gender' => 'other',
            'phone_number' => '+255710000000',
            'password' => Hash::make($password),
            'role_id' => $marketingRole->id,
            'salary_paid' => 0,
            'is_active' => true,
        ]);
    }
    
    echo "NEW CREDENTIALS:\n";
    echo "  Email: {$email}\n";
    echo "  Password: {$password}\n\n";
}

echo "========================================\n";
echo "Try logging in with these credentials:\n";
echo "  Email: {$email}\n";
echo "  Password: {$password}\n";
echo "========================================\n";







