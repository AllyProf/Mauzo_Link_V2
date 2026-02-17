<?php
/**
 * Create HR Staff Member - Final Version
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
use Illuminate\Support\Facades\DB;

print "========================================\n";
print "Create HR Staff Member\n";
print "========================================\n\n";

try {
    // Get first available owner
    $owner = User::where('role', '!=', 'admin')->first();
    
    if (!$owner) {
        print "❌ No owner found in the system.\n";
        exit(1);
    }
    
    print "✓ Found owner: {$owner->email} (ID: {$owner->id})\n\n";
    
    // Check if staff already exists
    $existingStaff = Staff::where('email', 'hr@mauzo.com')->first();
    
    if ($existingStaff) {
        print "⚠️  Staff with email hr@mauzo.com already exists.\n";
        print "   Updating password and ensuring HR access...\n\n";
        
        // Update password
        $existingStaff->password = Hash::make('password');
        $existingStaff->is_active = true;
        $existingStaff->save();
        
        $staff = $existingStaff;
        print "✓ Updated existing staff member\n";
    } else {
        // Find or create a role with HR permissions
        // First, try to find Manager role
        $hrRole = Role::where('user_id', $owner->id)
            ->where(function($q) {
                $q->where('name', 'like', '%Manager%')
                  ->orWhere('name', 'like', '%manager%')
                  ->orWhere('name', 'like', '%HR%')
                  ->orWhere('name', 'like', '%hr%');
            })
            ->first();
        
        // If no role found, get the first available role
        if (!$hrRole) {
            $hrRole = Role::where('user_id', $owner->id)->where('is_active', true)->first();
        }
        
        // If still no role, create one
        if (!$hrRole) {
            print "Creating HR Manager role...\n";
            $hrRole = Role::create([
                'user_id' => $owner->id,
                'name' => 'HR Manager',
                'slug' => 'hr-manager',
                'description' => 'Human Resources Manager',
                'is_active' => true,
            ]);
            print "✓ Created HR Manager role\n";
        } else {
            print "✓ Found role: {$hrRole->name}\n";
        }
        
        // Attach HR permissions to role
        $hrPermissions = Permission::where('module', 'hr')->get();
        if ($hrPermissions->count() > 0) {
            $hrRole->permissions()->syncWithoutDetaching($hrPermissions->pluck('id'));
            print "✓ Attached {$hrPermissions->count()} HR permissions to role\n";
        } else {
            print "⚠️  No HR permissions found. Staff will be created but may not have HR access.\n";
        }
        
        // Generate staff ID
        $staffId = Staff::generateStaffId($owner->id);
        print "✓ Generated Staff ID: {$staffId}\n";
        
        // Create staff member
        print "\nCreating staff member...\n";
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
        
        print "✓ Created HR staff member\n";
    }
    
    // Verify password
    $passwordVerified = Hash::check('password', $staff->password);
    
    // Check HR permissions
    $role = $staff->role;
    $hasHrPermissions = false;
    $hrPermCount = 0;
    
    if ($role) {
        $hrPermCount = $role->permissions()->where('module', 'hr')->count();
        $hasHrPermissions = $hrPermCount > 0;
    }
    
    print "\n";
    print "========================================\n";
    print "HR Staff Credentials\n";
    print "========================================\n\n";
    print "Email: hr@mauzo.com\n";
    print "Password: password\n\n";
    
    print "Verification:\n";
    print "  Staff ID: {$staff->staff_id}\n";
    print "  Full Name: {$staff->full_name}\n";
    print "  Password Hash: " . ($passwordVerified ? "✓ Verified" : "✗ Failed") . "\n";
    print "  Role: " . ($role ? $role->name : 'None') . "\n";
    print "  HR Permissions: " . ($hasHrPermissions ? "✓ Yes ({$hrPermCount})" : "✗ No") . "\n";
    print "  Status: " . ($staff->is_active ? "✓ Active" : "✗ Inactive") . "\n";
    
    print "\n";
    print "========================================\n";
    print "Access Information\n";
    print "========================================\n\n";
    print "📍 Login URL: /login\n";
    print "📍 HR Dashboard: /hr/dashboard\n\n";
    
    if (!$hasHrPermissions) {
        print "⚠️  WARNING: Role does not have HR permissions!\n";
        if ($role) {
            print "   Run: php attach_hr_permissions.php {$owner->email} {$role->name}\n";
        }
    } else {
        print "✅ HR Staff member is ready with full HR access!\n";
    }
    
    print "\n✅ Setup Complete!\n";
    
} catch (\Exception $e) {
    print "❌ Error: " . $e->getMessage() . "\n";
    print "File: " . $e->getFile() . "\n";
    print "Line: " . $e->getLine() . "\n";
    exit(1);
}

