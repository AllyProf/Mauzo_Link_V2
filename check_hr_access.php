<?php
/**
 * Check HR Access - Shows which users/roles can access HR
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Staff;

print "========================================\n";
print "HR Access Credentials Check\n";
print "========================================\n\n";

// Get all owners
$owners = User::where('role', '!=', 'admin')->get();

print "BUSINESS OWNERS (Full Access):\n";
print "----------------------------------------\n";
foreach ($owners as $owner) {
    print "Email: {$owner->email}\n";
    print "Name: {$owner->name}\n";
    print "Status: ✅ Has full HR access (Owner)\n";
    print "\n";
}

// Get roles with HR permissions
print "\nROLES WITH HR PERMISSIONS:\n";
print "----------------------------------------\n";
foreach ($owners as $owner) {
    $roles = Role::where('user_id', $owner->id)
        ->whereHas('permissions', function($q) {
            $q->where('module', 'hr');
        })
        ->get();
    
    if ($roles->count() > 0) {
        print "For Owner: {$owner->email}\n";
        foreach ($roles as $role) {
            print "  - Role: {$role->name}\n";
            
            // Get staff with this role
            $staff = Staff::where('user_id', $owner->id)
                ->where('role_id', $role->id)
                ->where('is_active', true)
                ->get();
            
            if ($staff->count() > 0) {
                print "    Staff with this role:\n";
                foreach ($staff as $s) {
                    print "      • Email: {$s->email}\n";
                    print "        Name: {$s->full_name}\n";
                    print "        Status: ✅ Can access HR\n";
                }
            } else {
                print "    (No active staff assigned to this role)\n";
            }
        }
        print "\n";
    }
}

print "\n========================================\n";
print "How to Access HR:\n";
print "========================================\n\n";
print "1. OWNER ACCESS:\n";
print "   - Login with your owner email and password\n";
print "   - Navigate to /hr/dashboard\n";
print "   - Or click 'HR' in the sidebar menu\n\n";

print "2. STAFF ACCESS:\n";
print "   - Staff must be assigned to a role with HR permissions\n";
print "   - Login with staff email and password\n";
print "   - HR menu will appear if they have permissions\n\n";

print "3. TO GIVE STAFF HR ACCESS:\n";
print "   - Assign staff to Manager/Admin role (if it has HR permissions)\n";
print "   - OR run: php attach_hr_permissions.php your-email@example.com RoleName\n";
print "   - Then assign staff to that role\n\n";

