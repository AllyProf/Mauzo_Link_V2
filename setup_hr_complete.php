<?php
/**
 * Complete HR Setup Script
 * This script sets up everything needed for HR functionality
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

echo "========================================\n";
echo "Complete HR Setup\n";
echo "========================================\n\n";

// Step 1: Check and Create HR permissions
echo "Step 1: Checking HR Permissions...\n";
$hrPermissions = Permission::where('module', 'hr')->get();
if ($hrPermissions->count() === 0) {
    echo "⚠️  No HR permissions found. Creating them now...\n";
    
    $modules = ['hr' => 'Human Resources'];
    $actions = [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ];

    foreach ($modules as $module => $moduleName) {
        foreach ($actions as $action => $actionName) {
            Permission::updateOrCreate(
                [
                    'module' => $module,
                    'action' => $action,
                ],
                [
                    'name' => $actionName . ' ' . $moduleName,
                    'description' => 'Permission to ' . strtolower($actionName) . ' ' . strtolower($moduleName),
                    'is_active' => true,
                ]
            );
        }
    }
    
    $hrPermissions = Permission::where('module', 'hr')->get();
    echo "✓ Created {$hrPermissions->count()} HR permissions\n\n";
} else {
    echo "✓ Found {$hrPermissions->count()} HR permissions\n\n";
}

// Step 2: Create HR menu items
echo "Step 2: Creating HR Menu Items...\n";
$existingMenu = MenuItem::where('slug', 'hr')->first();

if (!$existingMenu) {
    // Create parent HR menu
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

    // Create HR submenu items
    $subMenus = [
        ['name' => 'HR Dashboard', 'slug' => 'hr-dashboard', 'icon' => 'fa-dashboard', 'route' => 'hr.dashboard', 'sort_order' => 1],
        ['name' => 'Attendance', 'slug' => 'hr-attendance', 'icon' => 'fa-check-circle', 'route' => 'hr.attendance', 'sort_order' => 2],
        ['name' => 'Leaves', 'slug' => 'hr-leaves', 'icon' => 'fa-calendar', 'route' => 'hr.leaves', 'sort_order' => 3],
        ['name' => 'Payroll', 'slug' => 'hr-payroll', 'icon' => 'fa-money', 'route' => 'hr.payroll', 'sort_order' => 4],
        ['name' => 'Performance Reviews', 'slug' => 'hr-performance-reviews', 'icon' => 'fa-star', 'route' => 'hr.performance-reviews', 'sort_order' => 5],
    ];

    foreach ($subMenus as $subMenu) {
        MenuItem::create([
            'name' => $subMenu['name'],
            'slug' => $subMenu['slug'],
            'icon' => $subMenu['icon'],
            'route' => $subMenu['route'],
            'parent_id' => $hrMenu->id,
            'sort_order' => $subMenu['sort_order'],
            'is_active' => true,
        ]);
        echo "✓ Created {$subMenu['name']} menu item\n";
    }

    // Attach to all business types
    $businessTypes = BusinessType::all();
    foreach ($businessTypes as $bt) {
        $hrMenu->businessTypes()->attach($bt->id, [
            'is_enabled' => true,
            'sort_order' => 50,
        ]);
    }
    echo "✓ Attached HR menu to all business types\n";
} else {
    echo "✓ HR menu already exists\n";
}
echo "\n";

// Step 3: Attach HR permissions to common roles
echo "Step 3: Attaching HR Permissions to Roles...\n";
$owners = User::whereNotNull('email')->get();

foreach ($owners as $owner) {
    // Find Manager role
    $managerRole = Role::where('user_id', $owner->id)
        ->where(function($q) {
            $q->where('name', 'like', '%Manager%')
              ->orWhere('name', 'like', '%manager%')
              ->orWhere('slug', 'like', '%manager%');
        })
        ->first();

    if ($managerRole) {
        $managerRole->permissions()->syncWithoutDetaching($hrPermissions->pluck('id'));
        echo "✓ Attached HR permissions to Manager role for {$owner->email}\n";
    }

    // Find Admin role
    $adminRole = Role::where('user_id', $owner->id)
        ->where(function($q) {
            $q->where('name', 'like', '%Admin%')
              ->orWhere('name', 'like', '%admin%')
              ->orWhere('slug', 'like', '%admin%');
        })
        ->first();

    if ($adminRole) {
        $adminRole->permissions()->syncWithoutDetaching($hrPermissions->pluck('id'));
        echo "✓ Attached HR permissions to Admin role for {$owner->email}\n";
    }
}
echo "\n";

echo "========================================\n";
echo "HR Setup Complete!\n";
echo "========================================\n\n";
echo "HR Dashboard is now available at: /hr/dashboard\n";
echo "HR menu has been added to the sidebar.\n";
echo "HR permissions have been attached to Manager and Admin roles.\n\n";
echo "To access HR:\n";
echo "1. Login as owner (has all permissions)\n";
echo "2. OR login as staff with Manager/Admin role\n";
echo "3. Navigate to HR menu in sidebar\n";

