<?php
/**
 * Create Marketing Staff Member
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
echo "Create Marketing Staff Member\n";
echo "========================================\n\n";

// Get owner
$owner = User::where('email', 'admin@medalion.com')->first();

if (!$owner) {
    echo "❌ Owner not found. Please update the email in this script.\n";
    exit(1);
}

echo "✓ Owner: {$owner->name} (ID: {$owner->id})\n\n";

// Check if Marketing role exists
$marketingRole = Role::where('user_id', $owner->id)
    ->where(function($q) {
        $q->where('name', 'like', '%Marketing%')
          ->orWhere('name', 'like', '%marketing%');
    })
    ->first();

if (!$marketingRole) {
    echo "Creating Marketing role...\n";
    
    // Create Marketing role
    $marketingRole = Role::create([
        'user_id' => $owner->id,
        'name' => 'Marketing',
        'slug' => 'marketing',
        'description' => 'Marketing staff with access to SMS campaigns and customer database',
        'is_system_role' => false,
        'is_active' => true,
    ]);
    
    echo "✓ Marketing role created (ID: {$marketingRole->id})\n";
    
    // Get Marketing permissions
    $permissions = Permission::where('module', 'marketing')->get();
    
    if ($permissions->count() === 0) {
        echo "⚠️  No Marketing permissions found. Please run: php artisan db:seed --class=PermissionSeeder\n";
    } else {
        // Attach all marketing permissions
        $marketingRole->permissions()->sync($permissions->pluck('id'));
        echo "✓ Marketing permissions attached\n";
    }
} else {
    echo "✓ Marketing role exists: {$marketingRole->name} (ID: {$marketingRole->id})\n";
}

// Check if Marketing staff already exists
$existingStaff = Staff::where('user_id', $owner->id)
    ->where('role_id', $marketingRole->id)
    ->first();

if ($existingStaff) {
    echo "\n⚠️  Marketing staff already exists:\n";
    echo "  Name: {$existingStaff->full_name}\n";
    echo "  Email: {$existingStaff->email}\n";
    
    // Calculate password
    $nameParts = explode(' ', trim($existingStaff->full_name));
    $lastName = end($nameParts);
    $password = strtoupper($lastName);
    
    // Reset password
    $existingStaff->password = Hash::make($password);
    $existingStaff->is_active = true;
    $existingStaff->save();
    
    echo "\n✓ Password reset!\n";
    echo "\nLOGIN CREDENTIALS:\n";
    echo "  Email: {$existingStaff->email}\n";
    echo "  Password: {$password}\n";
    exit(0);
}

// Create Marketing staff
echo "\nCreating Marketing staff member...\n";

$staffId = Staff::generateStaffId($owner->id);
$fullName = "Marketing Manager";
$email = "marketing@medalion.com";
$phone = "+255710000000";

// Calculate password
$nameParts = explode(' ', trim($fullName));
$lastName = end($nameParts);
$password = strtoupper($lastName);

$staff = Staff::create([
    'user_id' => $owner->id,
    'staff_id' => $staffId,
    'full_name' => $fullName,
    'email' => $email,
    'gender' => 'other',
    'phone_number' => $phone,
    'password' => Hash::make($password),
    'role_id' => $marketingRole->id,
    'salary_paid' => 0,
    'is_active' => true,
]);

echo "✓ Marketing staff created!\n";
echo "\n" . str_repeat("=", 60) . "\n";
echo "LOGIN CREDENTIALS:\n";
echo str_repeat("=", 60) . "\n";
echo "Email: {$email}\n";
echo "Password: {$password}\n";
echo str_repeat("=", 60) . "\n";
echo "\n⚠️  Note: You can change the email/phone in Staff Management\n";
echo "   The password is the last name in UPPERCASE: {$password}\n";







