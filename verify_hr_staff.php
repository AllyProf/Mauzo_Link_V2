<?php
/**
 * Verify HR Staff Member
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

print "========================================\n";
print "Verify HR Staff Member\n";
print "========================================\n\n";

$staff = Staff::where('email', 'hr@mauzo.com')->first();

if (!$staff) {
    print "❌ HR staff member not found!\n";
    print "   Run: php create_hr_staff.php\n";
    exit(1);
}

print "✓ Found HR staff member:\n";
print "   Name: {$staff->full_name}\n";
print "   Email: {$staff->email}\n";
print "   Staff ID: {$staff->staff_id}\n";
print "   Status: " . ($staff->is_active ? "Active" : "Inactive") . "\n\n";

// Verify password
$passwordCheck = Hash::check('password', $staff->password);
print "Password Verification:\n";
print "   Password 'password': " . ($passwordCheck ? "✓ Correct" : "✗ Incorrect") . "\n\n";

// Check role and permissions
$role = $staff->role;
if ($role) {
    print "Role Information:\n";
    print "   Role: {$role->name}\n";
    
    $hrPermissions = $role->permissions()->where('module', 'hr')->get();
    print "   HR Permissions: {$hrPermissions->count()}\n";
    
    if ($hrPermissions->count() > 0) {
        print "   ✓ Has HR access\n";
        foreach ($hrPermissions as $perm) {
            print "     - {$perm->name}\n";
        }
    } else {
        print "   ✗ No HR permissions!\n";
        print "   → Run: php attach_hr_permissions.php owner-email@example.com {$role->name}\n";
    }
} else {
    print "✗ No role assigned!\n";
}

print "\n";
print "========================================\n";
print "Login Credentials\n";
print "========================================\n\n";
print "Email: hr@mauzo.com\n";
print "Password: password\n\n";
print "Access: /hr/dashboard\n";

