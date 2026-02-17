<?php
/**
 * Check Stock Keeper Permissions and Menus
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;
use App\Services\MenuService;

$staff = Staff::where('email', 'stockkeeper@mauzo.com')->first();

if (!$staff) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

echo "========================================\n";
echo "Stock Keeper Analysis\n";
echo "========================================\n\n";

echo "Staff: {$staff->full_name}\n";
echo "Email: {$staff->email}\n";
$role = $staff->role;
echo "Role: " . ($role ? $role->name : 'None') . "\n\n";

if ($role) {
    echo "Role Permissions:\n";
    $permissions = $role->permissions()->get();
    echo "Total: {$permissions->count()}\n";
    foreach ($permissions as $perm) {
        echo "  - {$perm->name} ({$perm->module}.{$perm->action})\n";
    }
    
    // Check specific permissions
    echo "\nPermission Checks:\n";
    $checks = [
        ['inventory', 'view'],
        ['stock_receipt', 'view'],
        ['stock_transfer', 'view'],
        ['products', 'view'],
        ['suppliers', 'view'],
    ];
    
    foreach ($checks as $check) {
        $has = $role->hasPermission($check[0], $check[1]);
        echo "  {$check[0]}.{$check[1]}: " . ($has ? "✓" : "✗") . "\n";
    }
} else {
    echo "❌ No role assigned!\n";
}

// Test menu generation
echo "\nMenu Generation Test:\n";
$owner = $staff->owner;
$menuService = new MenuService();
$menus = $menuService->getStaffMenus($role, $owner);

echo "Total menus: {$menus->count()}\n";
foreach ($menus as $menu) {
    $childrenCount = isset($menu->children) ? $menu->children->count() : 0;
    echo "  - {$menu->name} (children: {$childrenCount})\n";
    if ($childrenCount > 0 && isset($menu->children)) {
        foreach ($menu->children as $child) {
            echo "    • {$child->name}\n";
        }
    }
}

