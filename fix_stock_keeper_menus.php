<?php
/**
 * Fix Stock Keeper Menus - Ensure Bar Management shows up
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;
use App\Models\Staff;
use App\Services\MenuService;

$staff = Staff::where('email', 'stockkeeper@mauzo.com')->first();
if (!$staff) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

$owner = $staff->owner;
$role = $staff->role;
$barType = BusinessType::where('slug', 'bar')->first();

echo "========================================\n";
echo "Fix Stock Keeper Menus\n";
echo "========================================\n\n";

echo "Staff: {$staff->full_name}\n";
echo "Owner: {$owner->email}\n";
echo "Role: {$role->name}\n";
echo "Business Type: Bar\n\n";

// Check if Bar Management menu exists
$barManagement = MenuItem::where('slug', 'bar-management')->first();
if (!$barManagement) {
    echo "❌ Bar Management menu not found\n";
    exit(1);
}

echo "✓ Found Bar Management menu\n";

// Check if it's attached to Bar business type
$attached = BusinessTypeMenuItem::where('business_type_id', $barType->id)
    ->where('menu_item_id', $barManagement->id)
    ->first();

if (!$attached) {
    echo "⚠️  Bar Management not attached to Bar business type. Attaching...\n";
    BusinessTypeMenuItem::create([
        'business_type_id' => $barType->id,
        'menu_item_id' => $barManagement->id,
        'is_enabled' => true,
        'sort_order' => 10,
    ]);
    echo "✓ Attached Bar Management to Bar\n";
} else {
    echo "✓ Bar Management is attached to Bar\n";
}

// Check children
$children = MenuItem::where('parent_id', $barManagement->id)->get();
echo "\nBar Management children:\n";
foreach ($children as $child) {
    $childAttached = BusinessTypeMenuItem::where('business_type_id', $barType->id)
        ->where('menu_item_id', $child->id)
        ->first();
    
    $hasPermission = false;
    if ($child->route) {
        // Check if role has permission for this route
        $routePerms = [
            'bar.suppliers.index' => ['module' => 'suppliers', 'action' => 'view'],
            'bar.stock-receipts.index' => ['module' => 'stock_receipt', 'action' => 'view'],
            'bar.stock-transfers.index' => ['module' => 'stock_transfer', 'action' => 'view'],
        ];
        
        if (isset($routePerms[$child->route])) {
            $perm = $routePerms[$child->route];
            $hasPermission = $role->hasPermission($perm['module'], $perm['action']);
        }
    }
    
    echo "  - {$child->name} (route: " . ($child->route ?? 'none') . ")\n";
    echo "    Attached: " . ($childAttached ? "✓" : "✗") . "\n";
    echo "    Has Permission: " . ($hasPermission ? "✓" : "✗") . "\n";
    
    if (!$childAttached) {
        BusinessTypeMenuItem::create([
            'business_type_id' => $barType->id,
            'menu_item_id' => $child->id,
            'is_enabled' => true,
            'sort_order' => $child->sort_order ?? 0,
        ]);
        echo "    ✓ Attached\n";
    }
}

// Test menu generation
echo "\nTesting menu generation...\n";
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
echo "Fix Complete!\n";
echo "========================================\n";

