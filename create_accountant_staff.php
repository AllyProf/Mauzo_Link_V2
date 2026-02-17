<?php
/**
 * Create Accountant Staff Account with Easy Credentials
 * 
 * This script helps create an accountant staff account with proper role and permissions
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "Accountant Staff Account Creator\n";
echo "========================================\n\n";

// Get owner email from command line or use first available owner
$ownerEmail = $argv[1] ?? null;

if ($ownerEmail) {
    $owner = User::where('email', $ownerEmail)->first();
} else {
    // Get first available owner
    $owner = User::first();
    if ($owner) {
        echo "No email provided. Using first available owner:\n";
        echo "  Email: {$owner->email}\n";
        echo "  Name: {$owner->name}\n\n";
    }
}

if (!$owner) {
    echo "❌ No owner found in the system.\n";
    echo "Please create an owner account first or provide an owner email.\n";
    exit(1);
}

echo "✓ Using owner: {$owner->name} ({$owner->email}, ID: {$owner->id})\n\n";

// Find or create Accountant role
$accountantRole = Role::where('user_id', $owner->id)
    ->where(function($q) {
        $q->where('name', 'like', '%Accountant%')
          ->orWhere('name', 'like', '%accountant%')
          ->orWhere('slug', 'like', '%accountant%');
    })
    ->first();

if (!$accountantRole) {
    echo "Creating Accountant role...\n";
    
    $accountantRole = Role::create([
        'user_id' => $owner->id,
        'name' => 'Accountant',
        'slug' => \Illuminate\Support\Str::slug('Accountant-' . $owner->id . '-' . time()),
        'description' => 'Accountant role with finance and reports permissions',
        'is_system_role' => false,
        'is_active' => true,
    ]);
    
    // Get finance and reports permissions
    $financePermissions = Permission::where('module', 'finance')->get();
    $reportsPermissions = Permission::where('module', 'reports')->get();
    
    $permissionIds = $financePermissions->pluck('id')->merge($reportsPermissions->pluck('id'))->toArray();
    
    if (!empty($permissionIds)) {
        $accountantRole->permissions()->sync($permissionIds);
        echo "✓ Accountant permissions attached\n";
    }
} else {
    echo "✓ Accountant role exists: {$accountantRole->name} (ID: {$accountantRole->id})\n";
}

// Check if Accountant staff already exists
$existingAccountant = Staff::where('user_id', $owner->id)
    ->where('role_id', $accountantRole->id)
    ->first();

if ($existingAccountant) {
    echo "\n⚠️  Accountant staff already exists:\n";
    echo "  Name: {$existingAccountant->full_name}\n";
    echo "  Email: {$existingAccountant->email}\n";
    echo "  Staff ID: {$existingAccountant->staff_id}\n";
    
    // Calculate password
    $nameParts = explode(' ', trim($existingAccountant->full_name));
    $lastName = end($nameParts);
    $password = strtoupper($lastName);
    
    // Reset password
    $existingAccountant->password = Hash::make($password);
    $existingAccountant->is_active = true;
    $existingAccountant->save();
    
    echo "\n✓ Password reset!\n";
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "ACCOUNTANT LOGIN CREDENTIALS:\n";
    echo str_repeat("=", 60) . "\n";
    echo "Email: {$existingAccountant->email}\n";
    echo "Password: {$password}\n";
    echo str_repeat("=", 60) . "\n";
    echo "\n📍 Accountant Dashboard URL: /accountant/dashboard\n";
    exit(0);
}

// Create new accountant staff
echo "Creating new Accountant staff member...\n";

// Generate unique staff ID
$lastStaff = Staff::where('user_id', $owner->id)
    ->orderBy('id', 'desc')
    ->first();
    
$staffNumber = 1;
if ($lastStaff && preg_match('/STF(\d{8})(\d{4})/', $lastStaff->staff_id, $matches)) {
    $staffNumber = intval($matches[2]) + 1;
}

$staffId = 'STF' . date('Ymd') . str_pad($staffNumber, 4, '0', STR_PAD_LEFT);

// Check if staff_id already exists, increment if needed
while (Staff::where('staff_id', $staffId)->exists()) {
    $staffNumber++;
    $staffId = 'STF' . date('Ymd') . str_pad($staffNumber, 4, '0', STR_PAD_LEFT);
}

$fullName = "Accountant Manager";
$email = "accountant@mauzo.com"; // Change this to your preferred email
$phone = "+255710000000"; // Change this to your preferred phone

// Check if email already exists in users table (this would block login)
$emailInUsers = User::where('email', $email)->exists();
if ($emailInUsers) {
    echo "⚠️  Email {$email} exists in users table. Using alternative email...\n";
    $email = "accountant-staff@mauzo.com";
}

// Check if email already exists in staff table
$emailInStaff = Staff::where('email', $email)->exists();
if ($emailInStaff) {
    echo "⚠️  Email {$email} exists in staff table. Using alternative email...\n";
    $email = "accountant-" . time() . "@mauzo.com";
}

// Calculate password from last name
$nameParts = explode(' ', trim($fullName));
$lastName = end($nameParts);
$password = strtoupper($lastName); // Password will be "MANAGER"

$staff = Staff::create([
    'user_id' => $owner->id,
    'staff_id' => $staffId,
    'full_name' => $fullName,
    'email' => $email,
    'gender' => 'other',
    'phone_number' => $phone,
    'password' => Hash::make($password),
    'role_id' => $accountantRole->id,
    'salary_paid' => 0,
    'is_active' => true,
]);

echo "✓ Accountant staff created!\n";
echo "\n" . str_repeat("=", 60) . "\n";
echo "ACCOUNTANT LOGIN CREDENTIALS:\n";
echo str_repeat("=", 60) . "\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
echo str_repeat("=", 60) . "\n";
echo "\n📍 Accountant Dashboard URL: /accountant/dashboard\n";
echo "\n⚠️  Note: You can change the email/phone in Staff Management\n";
echo "   The password is the last name in UPPERCASE: {$password}\n";
