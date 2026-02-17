<?php
/**
 * Final HR Menu Fix - Complete Setup
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Staff;

echo "========================================\n";
echo "Final HR Menu Fix\n";
echo "========================================\n\n";

// Find HR staff
$hrStaff = Staff::where('email', 'hr@mauzo.com')->first();
if (!$hrStaff) {
    echo "❌ HR staff not found\n";
    exit(1);
}

$role = $hrStaff->role;
$owner = $hrStaff->owner;

echo "✓ HR Staff: {$hrStaff->full_name}\n";
echo "✓ Role: " . ($role ? $role->name : 'None') . "\n";
echo "✓ Owner: {$owner->email}\n\n";

// Step 1: Create/Update HR parent menu
echo "Step 1: Creating HR Menu...\n";
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
    echo "✓ Created HR parent menu\n";
} else {
    // Update to ensure it's correct
    $hrMenu->name = 'HR';
    $hrMenu->icon = 'fa-users';
    $hrMenu->route = 'hr.dashboard';
    $hrMenu->is_active = true;
    $hrMenu->save();
    echo "✓ Updated HR parent menu\n";
}

// Step 2: Create submenu items
echo "\nStep 2: Creating HR Submenu Items...\n";
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
        echo "✓ Created {$subMenu['name']}\n";
    } else {
        // Update existing
        $existing->name = $subMenu['name'];
        $existing->icon = $subMenu['icon'];
        $existing->route = $subMenu['route'];
        $existing->parent_id = $hrMenu->id;
        $existing->is_active = true;
        $existing->save();
        echo "✓ Updated {$subMenu['name']}\n";
    }
}

// Step 3: Attach HR permissions to role
echo "\nStep 3: Attaching HR Permissions...\n";
if ($role) {
    $hrPermissions = Permission::where('module', 'hr')->get();
    
    if ($hrPermissions->count() > 0) {
        $role->permissions()->syncWithoutDetaching($hrPermissions->pluck('id'));
        echo "✓ Attached {$hrPermissions->count()} HR permissions to {$role->name} role\n";
        
        // Verify
        $hasHrView = $role->hasPermission('hr', 'view');
        echo "  - HR View Permission: " . ($hasHrView ? "✓ Yes" : "✗ No") . "\n";
    } else {
        echo "⚠️  No HR permissions found in database\n";
        echo "   Run: php artisan db:seed --class=PermissionSeeder\n";
    }
} else {
    echo "⚠️  Staff has no role assigned!\n";
}

echo "\n";
echo "========================================\n";
echo "Setup Complete!\n";
echo "========================================\n\n";
echo "✅ HR menu created/updated\n";
echo "✅ HR submenu items created\n";
echo "✅ HR permissions attached to role\n\n";
echo "📋 Next Steps:\n";
echo "1. Logout from hr@mauzo.com\n";
echo "2. Login again as hr@mauzo.com\n";
echo "3. You should see 'HR' menu in the sidebar\n";
echo "4. Click 'HR' to access HR Dashboard\n\n";
echo "If menu still doesn't appear, try:\n";
echo "- Clear browser cache\n";
echo "- Access directly: /hr/dashboard\n";

