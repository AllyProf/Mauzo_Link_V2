<?php
/**
 * Create HR Staff Member
 * Email: hr@mauzo.com
 * Password: password
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
echo "Create HR Staff Member\n";
echo "========================================\n\n";

// Get first available owner
$owner = User::where('role', '!=', 'admin')->first();

if (!$owner) {
    echo "❌ No owner found in the system.\n";
    exit(1);
}

echo "✓ Found owner: {$owner->email}\n\n";

// Check if staff already exists
$existingStaff = Staff::where('email', 'hr@mauzo.com')->first();

if ($existingStaff) {
    echo "⚠️  Staff with email hr@mauzo.com already exists.\n";
    echo "   Updating password and ensuring HR access...\n\n";
    
    // Update password
    $existingStaff->password = Hash::make('password');
    $existingStaff->is_active = true;
    $existingStaff->save();
    
    $staff = $existingStaff;
} else {
    // Find or create HR Manager role
    $hrRole = Role::where('user_id', $owner->id)
        ->where(function($q) {
            $q->where('name', 'like', '%HR%')
              ->orWhere('name', 'like', '%hr%')
              ->orWhere('name', 'like', '%Manager%')
              ->orWhere('name', 'like', '%manager%');
        })
        ->first();

    if (!$hrRole) {
        // Create HR Manager role
        echo "Creating HR Manager role...\n";
        $hrRole = Role::create([
            'user_id' => $owner->id,
            'name' => 'HR Manager',
            'slug' => 'hr-manager',
            'description' => 'Human Resources Manager',
            'is_active' => true,
        ]);
        echo "✓ Created HR Manager role\n";
    } else {
        echo "✓ Found role: {$hrRole->name}\n";
    }

    // Attach HR permissions to role
    $hrPermissions = Permission::where('module', 'hr')->get();
    if ($hrPermissions->count() > 0) {
        $hrRole->permissions()->syncWithoutDetaching($hrPermissions->pluck('id'));
        echo "✓ Attached HR permissions to role\n";
    }

    // Generate staff ID
    $staffId = Staff::generateStaffId($owner->id);

    // Create staff member
    $staff = Staff::create([
        'user_id' => $owner->id,
        'staff_id' => $staffId,
        'full_name' => 'HR Manager',
        'email' => 'hr@mauzo.com',
        'gender' => 'other',
        'phone_number' => '+255710000000',
        'password' => Hash::make('password'),
        'role_id' => $hrRole->id,
        'salary_paid' => 0,
        'is_active' => true,
    ]);

    echo "✓ Created HR staff member\n";
}

// Verify password
$passwordVerified = Hash::check('password', $staff->password);

// Check HR permissions
$role = $staff->role;
$hasHrPermissions = false;
if ($role) {
    $hrPermCount = $role->permissions()->where('module', 'hr')->count();
    $hasHrPermissions = $hrPermCount > 0;
}

echo "\n";
echo "========================================\n";
echo "HR Staff Credentials\n";
echo "========================================\n\n";
echo "Email: hr@mauzo.com\n";
echo "Password: password\n\n";

echo "Verification:\n";
echo "  Password Hash: " . ($passwordVerified ? "✓ Verified" : "✗ Failed") . "\n";
echo "  HR Permissions: " . ($hasHrPermissions ? "✓ Yes" : "✗ No") . "\n";
echo "  Staff ID: {$staff->staff_id}\n";
echo "  Role: " . ($role ? $role->name : 'None') . "\n";
echo "  Status: " . ($staff->is_active ? "✓ Active" : "✗ Inactive") . "\n";

echo "\n";
echo "========================================\n";
echo "Access Information\n";
echo "========================================\n\n";
echo "📍 HR Dashboard URL: /hr/dashboard\n";
echo "📍 Login URL: /login\n\n";

if (!$hasHrPermissions) {
    echo "⚠️  WARNING: Role does not have HR permissions!\n";
    echo "   Run: php attach_hr_permissions.php {$owner->email} {$role->name}\n";
}

echo "\n✅ HR Staff member is ready!\n";
echo "   Login with: hr@mauzo.com / password\n";

