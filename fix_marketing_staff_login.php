<?php
/**
 * Fix Marketing Staff Login
 * 
 * This script helps identify and fix marketing staff login issues
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "Marketing Staff Login Fixer\n";
echo "========================================\n\n";

// List all owners
$owners = User::where('role', '!=', 'admin')->get();

if ($owners->count() === 0) {
    echo "❌ No business owners found.\n";
    exit(1);
}

echo "Available Business Owners:\n";
foreach ($owners as $index => $owner) {
    echo "  " . ($index + 1) . ". {$owner->name} ({$owner->email})\n";
}

echo "\nEnter owner number (or email): ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

$owner = null;
if (is_numeric($input)) {
    $index = (int)$input - 1;
    if (isset($owners[$index])) {
        $owner = $owners[$index];
    }
} else {
    $owner = User::where('email', $input)->first();
}

if (!$owner) {
    echo "❌ Owner not found.\n";
    exit(1);
}

echo "\n✓ Selected owner: {$owner->name} (ID: {$owner->id})\n\n";

// Find all staff
$staffMembers = Staff::where('user_id', $owner->id)
    ->with('role')
    ->get();

if ($staffMembers->count() === 0) {
    echo "❌ No staff members found for this owner.\n";
    echo "\nTo create a Marketing staff member:\n";
    echo "1. Go to: /staff/create\n";
    echo "2. Fill in the form\n";
    echo "3. Select a role with Marketing permissions\n";
    exit(1);
}

echo "All Staff Members:\n";
echo str_repeat("=", 100) . "\n";
printf("%-5s %-25s %-30s %-20s %-10s %-15s\n", "ID", "Name", "Email", "Role", "Status", "Password");
echo str_repeat("-", 100) . "\n";

$staffList = [];
foreach ($staffMembers as $index => $staff) {
    $roleName = $staff->role ? $staff->role->name : 'No Role';
    $status = $staff->is_active ? 'Active' : 'Inactive';
    
    // Calculate password
    $nameParts = explode(' ', trim($staff->full_name));
    $lastName = end($nameParts);
    $calculatedPassword = strtoupper($lastName);
    
    // Test password
    $passwordMatch = Hash::check($calculatedPassword, $staff->password);
    $passwordStatus = $passwordMatch ? "✓" : "✗";
    
    printf("%-5s %-25s %-30s %-20s %-10s %-15s %s\n", 
        $staff->id, 
        substr($staff->full_name, 0, 25),
        substr($staff->email, 0, 30),
        substr($roleName, 0, 20),
        $status,
        $calculatedPassword,
        $passwordStatus
    );
    
    $staffList[] = $staff;
}

echo str_repeat("=", 100) . "\n\n";

// Ask which staff to fix
echo "Enter staff number to check/fix (or 'all' to fix all): ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));
fclose($handle);

if ($choice === 'all') {
    echo "\nFixing all staff passwords...\n";
    foreach ($staffList as $staff) {
        $nameParts = explode(' ', trim($staff->full_name));
        $lastName = end($nameParts);
        $newPassword = strtoupper($lastName);
        
        $staff->password = Hash::make($newPassword);
        $staff->save();
        
        echo "✓ Fixed: {$staff->email} → Password: {$newPassword}\n";
    }
} elseif (is_numeric($choice)) {
    $index = (int)$choice - 1;
    if (isset($staffList[$index])) {
        $staff = $staffList[$index];
        
        echo "\nStaff Details:\n";
        echo "  ID: {$staff->id}\n";
        echo "  Name: {$staff->full_name}\n";
        echo "  Email: {$staff->email}\n";
        echo "  Phone: {$staff->phone_number}\n";
        echo "  Status: " . ($staff->is_active ? 'Active' : 'Inactive') . "\n";
        echo "  Role: " . ($staff->role ? $staff->role->name : 'No Role') . "\n";
        
        // Calculate password
        $nameParts = explode(' ', trim($staff->full_name));
        $lastName = end($nameParts);
        $calculatedPassword = strtoupper($lastName);
        
        echo "\n  Calculated Password: {$calculatedPassword}\n";
        
        // Test password
        $passwordMatch = Hash::check($calculatedPassword, $staff->password);
        echo "  Current Password Match: " . ($passwordMatch ? "✓ YES" : "✗ NO") . "\n";
        
        if (!$passwordMatch) {
            echo "\n⚠️  Password doesn't match! Resetting...\n";
            $staff->password = Hash::make($calculatedPassword);
            $staff->save();
            echo "✓ Password reset to: {$calculatedPassword}\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "LOGIN CREDENTIALS:\n";
        echo str_repeat("=", 60) . "\n";
        echo "Email: {$staff->email}\n";
        echo "Password: {$calculatedPassword}\n";
        echo str_repeat("=", 60) . "\n";
        
        // Check if role has marketing permissions
        if ($staff->role) {
            $hasMarketing = $staff->role->hasPermission('marketing', 'view');
            echo "\nMarketing Access: " . ($hasMarketing ? "✓ YES" : "✗ NO") . "\n";
            if (!$hasMarketing) {
                echo "\n⚠️  This staff member doesn't have Marketing permissions!\n";
                echo "To fix:\n";
                echo "1. Go to Roles management\n";
                echo "2. Edit role: {$staff->role->name}\n";
                echo "3. Add Marketing permissions (view, create, edit)\n";
            }
        }
    } else {
        echo "❌ Invalid staff number.\n";
    }
} else {
    echo "❌ Invalid choice.\n";
}

echo "\n========================================\n";
echo "Done!\n";
echo "========================================\n";







