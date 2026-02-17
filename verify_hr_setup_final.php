<?php
/**
 * Final HR Setup Verification
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;
use App\Models\MenuItem;
use App\Models\Role;
use App\Models\User;

print "========================================\n";
print "HR Setup Verification\n";
print "========================================\n\n";

// 1. Check Permissions
print "1. Checking HR Permissions...\n";
$hrPermissions = Permission::where('module', 'hr')->get();
if ($hrPermissions->count() > 0) {
    print "   ✓ Found {$hrPermissions->count()} HR permissions\n";
    foreach ($hrPermissions as $p) {
        print "     - {$p->name} ({$p->module}.{$p->action})\n";
    }
} else {
    print "   ✗ No HR permissions found\n";
}
print "\n";

// 2. Check Menu Items
print "2. Checking HR Menu Items...\n";
$hrMenu = MenuItem::where('slug', 'hr')->first();
if ($hrMenu) {
    print "   ✓ HR menu exists\n";
    $subMenus = MenuItem::where('parent_id', $hrMenu->id)->get();
    print "   ✓ Found {$subMenus->count()} HR submenu items\n";
} else {
    print "   ✗ HR menu not found\n";
    print "   → Run: php create_hr_menu.php\n";
}
print "\n";

// 3. Check Role Permissions
print "3. Checking Role Permissions...\n";
$owners = User::take(3)->get();
$foundRoles = false;
foreach ($owners as $owner) {
    $roles = Role::where('user_id', $owner->id)->get();
    foreach ($roles as $role) {
        $hasHrPerms = $role->permissions()->where('module', 'hr')->count() > 0;
        if ($hasHrPerms) {
            print "   ✓ Role '{$role->name}' for {$owner->email} has HR permissions\n";
            $foundRoles = true;
        }
    }
}
if (!$foundRoles) {
    print "   ⚠ No roles with HR permissions found\n";
    print "   → Run: php attach_hr_permissions.php your-email@example.com RoleName\n";
}
print "\n";

print "========================================\n";
print "Verification Complete\n";
print "========================================\n\n";

if ($hrPermissions->count() > 0 && $hrMenu) {
    print "✅ HR Setup is Complete!\n";
    print "\nAccess HR Dashboard at: /hr/dashboard\n";
} else {
    print "⚠️  Some components are missing. Please run:\n";
    if ($hrPermissions->count() == 0) {
        print "   - php create_hr_perms_simple.php\n";
    }
    if (!$hrMenu) {
        print "   - php create_hr_menu.php\n";
    }
}

