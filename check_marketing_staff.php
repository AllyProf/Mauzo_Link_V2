<?php
/**
 * Check Marketing Staff Credentials
 * 
 * This script helps verify and fix marketing staff login credentials
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "Marketing Staff Credentials Checker\n";
echo "========================================\n\n";

// Get owner (you may need to change this)
$ownerEmail = $argv[1] ?? null;
if (!$ownerEmail) {
    echo "Usage: php check_marketing_staff.php <owner_email>\n";
    echo "Example: php check_marketing_staff.php owner@business.com\n\n";
    echo "Or enter owner email now: ";
    $handle = fopen("php://stdin", "r");
    $ownerEmail = trim(fgets($handle));
    fclose($handle);
}

$owner = User::where('email', $ownerEmail)->first();

if (!$owner) {
    echo "❌ Owner not found with email: {$ownerEmail}\n";
    exit(1);
}

echo "✓ Found owner: {$owner->name} (ID: {$owner->id})\n\n";

// Find Marketing role
$marketingRole = Role::where('user_id', $owner->id)
    ->where(function($q) {
        $q->where('name', 'like', '%Marketing%')
          ->orWhere('name', 'like', '%marketing%');
    })
    ->first();

if (!$marketingRole) {
    echo "⚠️  Marketing role not found. Available roles:\n";
    $roles = Role::where('user_id', $owner->id)->get();
    foreach ($roles as $role) {
        echo "  - {$role->name} (ID: {$role->id})\n";
    }
    echo "\n";
} else {
    echo "✓ Found Marketing role: {$marketingRole->name} (ID: {$marketingRole->id})\n\n";
}

// Find all staff for this owner
$staffMembers = Staff::where('user_id', $owner->id)
    ->with('role')
    ->get();

echo "All Staff Members:\n";
echo str_repeat("-", 80) . "\n";
printf("%-5s %-30s %-30s %-20s %-15s\n", "ID", "Name", "Email", "Role", "Status");
echo str_repeat("-", 80) . "\n";

foreach ($staffMembers as $staff) {
    $roleName = $staff->role ? $staff->role->name : 'No Role';
    $status = $staff->is_active ? 'Active' : 'Inactive';
    printf("%-5s %-30s %-30s %-20s %-15s\n", 
        $staff->id, 
        substr($staff->full_name, 0, 30),
        substr($staff->email, 0, 30),
        substr($roleName, 0, 20),
        $status
    );
    
    // Calculate password
    $nameParts = explode(' ', trim($staff->full_name));
    $lastName = end($nameParts);
    $calculatedPassword = strtoupper($lastName);
    
    // Test password
    $passwordMatch = Hash::check($calculatedPassword, $staff->password);
    
    echo "     Password: {$calculatedPassword} " . ($passwordMatch ? "✓" : "✗") . "\n";
}

echo str_repeat("-", 80) . "\n\n";

// Ask to check specific staff
echo "Enter staff email to check/reset password (or press Enter to exit): ";
$handle = fopen("php://stdin", "r");
$staffEmail = trim(fgets($handle));
fclose($handle);

if ($staffEmail) {
    $staff = Staff::where('user_id', $owner->id)
        ->where('email', $staffEmail)
        ->first();
    
    if (!$staff) {
        echo "❌ Staff not found with email: {$staffEmail}\n";
        exit(1);
    }
    
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
    echo "  Password Match: " . ($passwordMatch ? "✓ YES" : "✗ NO") . "\n";
    
    if (!$passwordMatch) {
        echo "\n⚠️  Password doesn't match! Would you like to reset it? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $reset = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($reset) === 'yes') {
            $staff->password = Hash::make($calculatedPassword);
            $staff->save();
            echo "✓ Password reset to: {$calculatedPassword}\n";
        }
    }
    
    echo "\nLogin Credentials:\n";
    echo "  Email: {$staff->email}\n";
    echo "  Password: {$calculatedPassword}\n";
}

echo "\n========================================\n";
echo "Done!\n";
echo "========================================\n";







