<?php
/**
 * Verify Chef Account
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Services\MenuService;

$chef = Staff::where('email', 'chef@mauzo.com')->first();

if (!$chef) {
    echo "❌ Chef not found\n";
    exit(1);
}

echo "========================================\n";
echo "Verify Chef Account\n";
echo "========================================\n\n";

echo "Chef: {$chef->full_name}\n";
echo "Email: {$chef->email}\n";
echo "Staff ID: {$chef->staff_id}\n";
$role = $chef->role;
echo "Role: " . ($role ? $role->name : 'None') . "\n";
echo "Owner: {$chef->owner->email}\n\n";

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
        ['bar_orders', 'view'],
        ['bar_orders', 'edit'],
        ['products', 'view'],
        ['products', 'edit'],
        ['inventory', 'view'],
        ['inventory', 'edit'],
        ['stock_receipt', 'view'],
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
$owner = $chef->owner;
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

echo "\n========================================\n";
echo "Verification Complete\n";
echo "========================================\n";

