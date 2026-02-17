<?php
/**
 * Fix HR Menu - Create menu items and attach permissions
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Staff;
use App\Models\User;

print "========================================\n";
print "Fix HR Menu for Logged In Staff\n";
print "========================================\n\n";

// Find HR staff
$hrStaff = Staff::where('email', 'hr@mauzo.com')->first();

if (!$hrStaff) {
    print "❌ HR staff not found. Please create staff first.\n";
    exit(1);
}

print "✓ Found HR staff: {$hrStaff->full_name}\n";
print "   Role: " . ($hrStaff->role ? $hrStaff->role->name : 'None') . "\n\n";

$owner = $hrStaff->owner;
print "✓ Owner: {$owner->email}\n\n";

// Step 1: Create HR menu if not exists
print "Step 1: Creating HR Menu Items...\n";
$hrMenu = MenuItem::where('slug', 'hr')->first();

if (!$hrMenu) {
    $hrMenu = MenuItem::create([
        'name' => 'HR',
        'slug' => 'hr',
        'icon' => 'fa-users',
        'route' => 'hr.dashboard',
        'sort_order' => 50,
        'is_active' => true,
        'description' => 'Human Resources Management',
    ]);
    print "✓ Created HR parent menu\n";
} else {
    print "✓ HR menu already exists\n";
}

// Create submenu items
$subMenus = [
    ['name' => 'HR Dashboard', 'slug' => 'hr-dashboard', 'icon' => 'fa-dashboard', 'route' => 'hr.dashboard', 'sort_order' => 1],
    ['name' => 'Attendance', 'slug' => 'hr-attendance', 'icon' => 'fa-check-circle', 'route' => 'hr.attendance', 'sort_order' => 2],
    ['name' => 'Leaves', 'slug' => 'hr-leaves', 'icon' => 'fa-calendar', 'route' => 'hr.leaves', 'sort_order' => 3],
    ['name' => 'Payroll', 'slug' => 'hr-payroll', 'icon' => 'fa-money', 'route' => 'hr.payroll', 'sort_order' => 4],
    ['name' => 'Performance Reviews', 'slug' => 'hr-performance-reviews', 'icon' => 'fa-star', 'route' => 'hr.performance-reviews', 'sort_order' => 5],
];

foreach ($subMenus as $subMenu) {
    $existing = MenuItem::where('slug', $subMenu['slug'])->first();
    if (!$existing) {
        MenuItem::create([
            'name' => $subMenu['name'],
            'slug' => $subMenu['slug'],
            'icon' => $subMenu['icon'],
            'route' => $subMenu['route'],
            'parent_id' => $hrMenu->id,
            'sort_order' => $subMenu['sort_order'],
            'is_active' => true,
        ]);
        print "✓ Created {$subMenu['name']}\n";
    }
}

// Attach to all business types
$businessTypes = BusinessType::all();
foreach ($businessTypes as $bt) {
    if (!$hrMenu->businessTypes()->where('business_type_id', $bt->id)->exists()) {
        $hrMenu->businessTypes()->attach($bt->id, [
            'is_enabled' => true,
            'sort_order' => 50,
        ]);
    }
}
print "✓ Menu attached to business types\n\n";

// Step 2: Attach HR permissions to role
print "Step 2: Attaching HR Permissions...\n";
$role = $hrStaff->role;

if ($role) {
    $hrPermissions = Permission::where('module', 'hr')->get();
    
    if ($hrPermissions->count() > 0) {
        $role->permissions()->syncWithoutDetaching($hrPermissions->pluck('id'));
        print "✓ Attached {$hrPermissions->count()} HR permissions to {$role->name} role\n";
    } else {
        print "⚠️  No HR permissions found. Run: php artisan db:seed --class=PermissionSeeder\n";
    }
} else {
    print "⚠️  Staff has no role assigned!\n";
}

print "\n";
print "========================================\n";
print "Setup Complete!\n";
print "========================================\n\n";
print "✅ HR menu items created\n";
print "✅ HR permissions attached to role\n\n";
print "📋 Next Steps:\n";
print "1. Logout and login again as hr@mauzo.com\n";
print "2. You should see 'HR' menu in the sidebar\n";
print "3. Click 'HR' to access HR Dashboard\n\n";

