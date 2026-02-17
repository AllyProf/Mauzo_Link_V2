<?php
/**
 * Debug Marketing Staff Login
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "Debug Marketing Staff Login\n";
echo "========================================\n\n";

// Find Marketing staff
$staff = Staff::where('email', 'marketing@medalion.com')
    ->with('role')
    ->first();

if (!$staff) {
    echo "❌ Marketing staff not found with email: marketing@medalion.com\n\n";
    echo "Searching for all staff with 'marketing' in email...\n";
    $allStaff = Staff::where('email', 'like', '%marketing%')->get();
    if ($allStaff->count() > 0) {
        foreach ($allStaff as $s) {
            echo "  Found: {$s->email} (Name: {$s->full_name})\n";
        }
    }
    exit(1);
}

echo "✓ Found Marketing staff:\n";
echo "  ID: {$staff->id}\n";
echo "  Staff ID: {$staff->staff_id}\n";
echo "  Name: {$staff->full_name}\n";
echo "  Email: {$staff->email}\n";
echo "  Phone: {$staff->phone_number}\n";
echo "  Status: " . ($staff->is_active ? 'Active ✓' : 'Inactive ✗') . "\n";
echo "  Role: " . ($staff->role ? $staff->role->name : 'No Role ✗') . "\n";
echo "  Owner ID: {$staff->user_id}\n\n";

// Check if staff is active
if (!$staff->is_active) {
    echo "⚠️  Staff account is INACTIVE! Activating...\n";
    $staff->is_active = true;
    $staff->save();
    echo "✓ Account activated!\n\n";
}

// Calculate password
$nameParts = explode(' ', trim($staff->full_name));
$lastName = end($nameParts);
$calculatedPassword = strtoupper($lastName);

echo "Password Analysis:\n";
echo "  Full Name: {$staff->full_name}\n";
echo "  Last Name: {$lastName}\n";
echo "  Calculated Password: {$calculatedPassword}\n";
echo "  Stored Hash: " . substr($staff->password, 0, 20) . "...\n\n";

// Test different password variations
$testPasswords = [
    $calculatedPassword,
    strtolower($calculatedPassword),
    $lastName,
    strtolower($lastName),
    'MANAGER',
    'manager',
    'Manager',
];

echo "Testing password variations:\n";
$matched = false;
foreach ($testPasswords as $testPwd) {
    $match = Hash::check($testPwd, $staff->password);
    echo "  '{$testPwd}': " . ($match ? "✓ MATCH!" : "✗") . "\n";
    if ($match) {
        $matched = true;
        echo "    → This is the correct password!\n";
    }
}

if (!$matched) {
    echo "\n⚠️  No password match found! Resetting password...\n";
    $newPassword = $calculatedPassword;
    $staff->password = Hash::make($newPassword);
    $staff->is_active = true;
    $staff->save();
    echo "✓ Password reset to: {$newPassword}\n";
    echo "✓ Verified: " . (Hash::check($newPassword, $staff->password) ? "YES" : "NO") . "\n";
} else {
    echo "\n✓ Password is correct!\n";
}

// Check if email exists in users table (this would block staff login)
$userExists = User::where('email', $staff->email)->first();
if ($userExists) {
    echo "\n⚠️  WARNING: Email '{$staff->email}' also exists in users table!\n";
    echo "  This will prevent staff login. User ID: {$userExists->id}\n";
    echo "  Staff login checks if email exists in users table first.\n";
    echo "  Solution: Change staff email to a unique one.\n";
}

// Check role permissions
if ($staff->role) {
    echo "\nRole Permissions:\n";
    $permissions = $staff->role->permissions()->where('module', 'marketing')->get();
    if ($permissions->count() > 0) {
        echo "  Marketing permissions: ✓\n";
        foreach ($permissions as $perm) {
            echo "    - {$perm->action}\n";
        }
    } else {
        echo "  Marketing permissions: ✗ NONE!\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "FINAL LOGIN CREDENTIALS:\n";
echo str_repeat("=", 60) . "\n";
echo "Email: {$staff->email}\n";
echo "Password: {$calculatedPassword}\n";
echo str_repeat("=", 60) . "\n";

// Test login simulation
echo "\nSimulating login check...\n";
$testEmail = $staff->email;
$testPassword = $calculatedPassword;

// Check if user exists (this blocks staff login)
$userCheck = User::where('email', $testEmail)->exists();
if ($userCheck) {
    echo "❌ BLOCKED: Email exists in users table - staff login will fail!\n";
    echo "   Fix: Change staff email to something unique like: marketing-staff@medalion.com\n";
} else {
    echo "✓ Email not in users table - OK\n";
}

// Check staff exists
$staffCheck = Staff::where('email', $testEmail)->first();
if (!$staffCheck) {
    echo "❌ Staff not found\n";
} else {
    echo "✓ Staff found\n";
    echo "  Active: " . ($staffCheck->is_active ? "YES" : "NO") . "\n";
    
    // Check password
    $passwordCheck = Hash::check($testPassword, $staffCheck->password);
    echo "  Password match: " . ($passwordCheck ? "YES ✓" : "NO ✗") . "\n";
    
    if ($passwordCheck && $staffCheck->is_active && !$userCheck) {
        echo "\n✅ All checks passed! Login should work.\n";
    } else {
        echo "\n❌ Login will fail. Issues found above.\n";
    }
}







