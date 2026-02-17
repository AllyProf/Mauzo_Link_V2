<?php
/**
 * Debug Stock Keeper Menu Generation
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\BusinessType;
use App\Models\MenuItem;
use App\Models\BusinessTypeMenuItem;
use App\Services\MenuService;

$staff = Staff::where('email', 'stockkeeper@mauzo.com')->first();
if (!$staff) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

$owner = $staff->owner;
$role = $staff->role;

echo "========================================\n";
echo "Debug Stock Keeper Menu\n";
echo "========================================\n\n";

echo "Owner: {$owner->email}\n";
echo "Staff: {$staff->full_name}\n";
echo "Role: {$role->name}\n\n";

// Check enabled business types
$enabledTypes = $owner->enabledBusinessTypes()->get();
echo "Owner's Enabled Business Types:\n";
foreach ($enabledTypes as $type) {
    echo "  - {$type->name} (slug: {$type->slug})\n";
}
echo "\n";

// Check Bar Management menu
$barType = BusinessType::where('slug', 'bar')->first();
$barManagement = MenuItem::where('slug', 'bar-management')->first();

if ($barManagement) {
    echo "Bar Management Menu:\n";
    echo "  ID: {$barManagement->id}\n";
    echo "  Parent ID: " . ($barManagement->parent_id ?? 'null') . "\n";
    echo "  Route: " . ($barManagement->route ?? 'null') . "\n";
    echo "  Is Active: " . ($barManagement->is_active ? 'Yes' : 'No') . "\n\n";
    
    // Check if attached to Bar
    $attached = BusinessTypeMenuItem::where('business_type_id', $barType->id)
        ->where('menu_item_id', $barManagement->id)
        ->first();
    echo "  Attached to Bar: " . ($attached ? "Yes (enabled: " . ($attached->is_enabled ? 'Yes' : 'No') . ")" : "No") . "\n\n";
    
    // Get children
    $children = MenuItem::where('parent_id', $barManagement->id)->get();
    echo "  Children ({$children->count()}):\n";
    foreach ($children as $child) {
        $childAttached = BusinessTypeMenuItem::where('business_type_id', $barType->id)
            ->where('menu_item_id', $child->id)
            ->first();
        
        $hasPerm = false;
        if ($child->route) {
            $routePerms = [
                'bar.suppliers.index' => ['module' => 'suppliers', 'action' => 'view'],
                'bar.stock-receipts.index' => ['module' => 'stock_receipt', 'action' => 'view'],
                'bar.stock-transfers.index' => ['module' => 'stock_transfer', 'action' => 'view'],
            ];
            if (isset($routePerms[$child->route])) {
                $perm = $routePerms[$child->route];
                $hasPerm = $role->hasPermission($perm['module'], $perm['action']);
            }
        }
        
        echo "    - {$child->name} (route: " . ($child->route ?? 'none') . ")\n";
        echo "      Attached: " . ($childAttached ? "Yes" : "No") . "\n";
        echo "      Has Permission: " . ($hasPerm ? "Yes" : "No") . "\n";
    }
}

// Test menu service
echo "\nMenuService Test:\n";
$menuService = new MenuService();

// Manually check what getStaffMenus returns
$menus = $menuService->getStaffMenus($role, $owner);
echo "Total menus returned: {$menus->count()}\n";

foreach ($menus as $menu) {
    $childrenCount = isset($menu->children) ? $menu->children->count() : 0;
    echo "  - {$menu->name} (slug: {$menu->slug}, children: {$childrenCount})\n";
    if (isset($menu->business_type_id)) {
        echo "    Business Type ID: {$menu->business_type_id}\n";
    }
}

