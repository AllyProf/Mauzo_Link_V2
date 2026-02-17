<?php
/**
 * Create Chef Staff Account with Easy Credentials
 * 
 * This script helps create a chef staff account with proper role and permissions
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
echo "Chef Staff Account Creator\n";
echo "========================================\n\n";

// Get owner email (you can modify this)
$ownerEmail = $argv[1] ?? null;
if (!$ownerEmail) {
    echo "Usage: php create_chef_staff.php <owner_email>\n";
    echo "Example: php create_chef_staff.php owner@business.com\n\n";
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

// Find or create Chef role
$chefRole = Role::where('user_id', $owner->id)
    ->where(function($q) {
        $q->where('name', 'like', '%Chef%')
          ->orWhere('name', 'like', '%chef%')
          ->orWhere('slug', 'like', '%chef%');
    })
    ->first();

if (!$chefRole) {
    echo "⚠️  Chef role not found. Creating new Chef role...\n";
    
    // Create Chef role
    $chefRole = Role::create([
        'user_id' => $owner->id,
        'name' => 'Chef',
        'slug' => 'chef',
        'description' => 'Kitchen staff who manages food orders and ingredients',
        'is_system_role' => false,
        'is_active' => true,
    ]);
    
    echo "✓ Chef role created (ID: {$chefRole->id})\n";
    
    // Get bar_orders permissions
    $barOrdersPermissions = Permission::where('module', 'bar_orders')->get();
    
    if ($barOrdersPermissions->count() > 0) {
        // Attach all bar_orders permissions to Chef role
        $chefRole->permissions()->attach($barOrdersPermissions->pluck('id'));
        echo "✓ Attached bar_orders permissions to Chef role\n";
    } else {
        echo "⚠️  No bar_orders permissions found. Please check your permissions table.\n";
    }
} else {
    echo "✓ Found Chef role: {$chefRole->name} (ID: {$chefRole->id})\n";
    
    // Check if role has bar_orders permissions
    $hasBarOrdersView = $chefRole->hasPermission('bar_orders', 'view');
    $hasBarOrdersEdit = $chefRole->hasPermission('bar_orders', 'edit');
    
    if (!$hasBarOrdersView || !$hasBarOrdersEdit) {
        echo "⚠️  Chef role missing bar_orders permissions. Adding them...\n";
        $barOrdersPermissions = Permission::where('module', 'bar_orders')->get();
        if ($barOrdersPermissions->count() > 0) {
            $chefRole->permissions()->syncWithoutDetaching($barOrdersPermissions->pluck('id'));
            echo "✓ Added bar_orders permissions to Chef role\n";
        }
    } else {
        echo "✓ Chef role has bar_orders permissions\n";
    }
}

echo "\n";

// Check if chef staff already exists
$existingChef = Staff::where('user_id', $owner->id)
    ->where('role_id', $chefRole->id)
    ->first();

if ($existingChef) {
    echo "⚠️  Chef staff already exists:\n";
    echo "  Name: {$existingChef->full_name}\n";
    echo "  Email: {$existingChef->email}\n";
    echo "  Staff ID: {$existingChef->staff_id}\n";
    
    // Calculate password
    $nameParts = explode(' ', trim($existingChef->full_name));
    $lastName = end($nameParts);
    $password = strtoupper($lastName);
    
    // Reset password
    $existingChef->password = Hash::make($password);
    $existingChef->is_active = true;
    $existingChef->save();
    
    echo "\n✓ Password reset!\n";
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "CHEF LOGIN CREDENTIALS:\n";
    echo str_repeat("=", 60) . "\n";
    echo "Email: {$existingChef->email}\n";
    echo "Password: {$password}\n";
    echo str_repeat("=", 60) . "\n";
    echo "\n📍 Chef Dashboard URL: /bar/chef/dashboard\n";
    exit(0);
}

// Create new chef staff
echo "Creating new Chef staff member...\n";

$staffId = Staff::generateStaffId($owner->id);
$fullName = "Chef Manager";
$email = "chef@medalion.com"; // Change this to your preferred email
$phone = "+255710000000"; // Change this to your preferred phone

// Check if email already exists in users table (this would block login)
$emailInUsers = User::where('email', $email)->exists();
if ($emailInUsers) {
    echo "⚠️  Email {$email} exists in users table. Using alternative email...\n";
    $email = "chef-staff@medalion.com";
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
    'role_id' => $chefRole->id,
    'salary_paid' => 0,
    'is_active' => true,
]);

echo "✓ Chef staff created!\n";
echo "\n" . str_repeat("=", 60) . "\n";
echo "CHEF LOGIN CREDENTIALS:\n";
echo str_repeat("=", 60) . "\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
echo str_repeat("=", 60) . "\n";
echo "\n📍 Chef Dashboard URL: /bar/chef/dashboard\n";
echo "\n⚠️  Note:\n";
echo "   - You can change the email/phone in Staff Management\n";
echo "   - The password is the last name in UPPERCASE: {$password}\n";
echo "   - Make sure the Chef role has 'bar_orders' permissions\n";
echo "\n";





