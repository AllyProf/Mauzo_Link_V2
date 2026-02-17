<?php
/**
 * Verify Waiter Account
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Services\MenuService;

$waiter = Staff::where('email', 'waiter@mauzo.com')->first();

if (!$waiter) {
    echo "❌ Waiter not found\n";
    exit(1);
}

echo "========================================\n";
echo "Verify Waiter Account\n";
echo "========================================\n\n";

echo "Waiter: {$waiter->full_name}\n";
echo "Email: {$waiter->email}\n";
echo "Staff ID: {$waiter->staff_id}\n";
$role = $waiter->role;
echo "Role: " . ($role ? $role->name : 'None') . "\n";
echo "Owner: {$waiter->owner->email}\n\n";

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
        ['bar_orders', 'create'],
        ['bar_tables', 'view'],
        ['products', 'view'],
        ['customers', 'view'],
        ['customers', 'create'],
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
$owner = $waiter->owner;
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

