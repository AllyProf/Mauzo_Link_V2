<?php
/**
 * Check Why Marketing Menu is Not Visible
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;
use App\Models\User;

$owner = User::where('email', 'admin@medalion.com')->first();
$marketingStaff = Staff::where('email', 'marketer@medalion.com')->first();

if (!$marketingStaff) {
    echo "❌ Marketing staff not found\n";
    exit(1);
}

echo "========================================\n";
echo "Marketing Menu Visibility Check\n";
echo "========================================\n\n";

echo "Staff Info:\n";
echo "  Name: {$marketingStaff->full_name}\n";
echo "  Email: {$marketingStaff->email}\n";
echo "  Role: " . ($marketingStaff->role ? $marketingStaff->role->name : 'No Role') . "\n";
echo "  Owner ID: {$marketingStaff->user_id}\n\n";

// Check Marketing menu
$marketingMenu = MenuItem::where('slug', 'marketing')->first();
if (!$marketingMenu) {
    echo "❌ Marketing menu not found!\n";
    exit(1);
}

echo "✓ Marketing menu found (ID: {$marketingMenu->id})\n";
echo "  Name: {$marketingMenu->name}\n";
echo "  Route: " . ($marketingMenu->route ?? 'N/A') . "\n";
echo "  Parent: " . ($marketingMenu->parent_id ? 'Yes' : 'No') . "\n";
echo "  Active: " . ($marketingMenu->is_active ? 'Yes' : 'No') . "\n\n";

// Check children
$children = $marketingMenu->children;
echo "Children: {$children->count()}\n";
foreach ($children as $child) {
    echo "  - {$child->name} (route: {$child->route})\n";
}

// Check business type links
echo "\nBusiness Type Links:\n";
$ownerBusinessTypes = $owner->businessTypes;
foreach ($ownerBusinessTypes as $bt) {
    $link = BusinessTypeMenuItem::where('business_type_id', $bt->id)
        ->where('menu_item_id', $marketingMenu->id)
        ->first();
    echo "  {$bt->name}: " . ($link ? "✓ Linked" : "✗ Not Linked") . "\n";
    
    if (!$link) {
        echo "    Creating link...\n";
        BusinessTypeMenuItem::create([
            'business_type_id' => $bt->id,
            'menu_item_id' => $marketingMenu->id,
            'is_enabled' => true,
            'sort_order' => 8,
        ]);
        echo "    ✓ Link created!\n";
    }
}

// Check permissions
if ($marketingStaff->role) {
    echo "\nRole Permissions:\n";
    $perms = $marketingStaff->role->permissions()->where('module', 'marketing')->get();
    foreach ($perms as $perm) {
        echo "  ✓ {$perm->module}.{$perm->action}\n";
    }
    
    // Test menu access
    echo "\nTesting Menu Access:\n";
    $menuService = new \App\Services\MenuService();
    
    // Simulate staff menu check
    $role = $marketingStaff->role;
    $menus = $menuService->getStaffMenus($role, $owner);
    
    echo "  Total menus returned: {$menus->count()}\n";
    $hasMarketing = false;
    foreach ($menus as $menu) {
        if ($menu->slug === 'marketing' || strpos($menu->name, 'Marketing') !== false) {
            $hasMarketing = true;
            echo "  ✓ Found Marketing menu: {$menu->name}\n";
        }
    }
    
    if (!$hasMarketing) {
        echo "  ✗ Marketing menu not in returned menus!\n";
        echo "\n  Available menus:\n";
        foreach ($menus as $menu) {
            echo "    - {$menu->name} ({$menu->slug})\n";
        }
    }
}

echo "\n========================================\n";
echo "Fix Applied!\n";
echo "========================================\n";







