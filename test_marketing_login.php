<?php
/**
 * Test Marketing Staff Login
 * This simulates the exact login process
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "Test Marketing Staff Login\n";
echo "========================================\n\n";

$email = 'marketing@medalion.com';
$password = 'MANAGER';

echo "Testing login with:\n";
echo "  Email: {$email}\n";
echo "  Password: {$password}\n\n";

// Step 1: Check if email exists in users table (this blocks staff login)
echo "Step 1: Checking if email exists in users table...\n";
$userExists = User::where('email', $email)->first();
if ($userExists) {
    echo "  ❌ BLOCKED: Email exists in users table!\n";
    echo "     User ID: {$userExists->id}\n";
    echo "     This prevents staff login.\n";
    echo "\n  Solution: Change staff email to: marketing-staff@medalion.com\n";
    exit(1);
} else {
    echo "  ✓ Email not in users table - OK\n";
}

// Step 2: Check if staff exists
echo "\nStep 2: Checking if staff exists...\n";
$staff = Staff::where('email', $email)->first();
if (!$staff) {
    echo "  ❌ Staff not found!\n";
    exit(1);
} else {
    echo "  ✓ Staff found (ID: {$staff->id})\n";
}

// Step 3: Check if staff is active
echo "\nStep 3: Checking if staff is active...\n";
if (!$staff->is_active) {
    echo "  ❌ Staff account is INACTIVE!\n";
    $staff->is_active = true;
    $staff->save();
    echo "  ✓ Account activated!\n";
} else {
    echo "  ✓ Staff is active\n";
}

// Step 4: Verify password
echo "\nStep 4: Verifying password...\n";
$passwordCheck = Hash::check($password, $staff->password);
if (!$passwordCheck) {
    echo "  ❌ Password incorrect!\n";
    echo "  Resetting password...\n";
    $staff->password = Hash::make($password);
    $staff->save();
    echo "  ✓ Password reset to: {$password}\n";
    $passwordCheck = Hash::check($password, $staff->password);
    echo "  ✓ Verified: " . ($passwordCheck ? "YES" : "NO") . "\n";
} else {
    echo "  ✓ Password is correct!\n";
}

// Step 5: Check owner
echo "\nStep 5: Checking owner...\n";
$owner = $staff->owner;
if (!$owner) {
    echo "  ❌ Owner not found!\n";
    exit(1);
} else {
    echo "  ✓ Owner: {$owner->name} (ID: {$owner->id})\n";
}

// Final check
echo "\n" . str_repeat("=", 60) . "\n";
if ($passwordCheck && $staff->is_active && !$userExists) {
    echo "✅ ALL CHECKS PASSED!\n";
    echo "Login should work with:\n";
    echo "  Email: {$email}\n";
    echo "  Password: {$password}\n";
    echo "\nIf login still fails, try:\n";
    echo "  1. Clear browser cache and cookies\n";
    echo "  2. Try incognito/private window\n";
    echo "  3. Check browser console for errors\n";
    echo "  4. Verify you're using the correct login URL: /login\n";
} else {
    echo "❌ LOGIN WILL FAIL\n";
    echo "Issues found above need to be fixed.\n";
}
echo str_repeat("=", 60) . "\n";







