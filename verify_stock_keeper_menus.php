<?php
/**
 * Verify Stock Keeper Menus - Should only see Bar menus
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Services\MenuService;

$stockKeeper = Staff::where('email', 'stockkeeper@mauzo.com')->first();

if (!$stockKeeper) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

echo "========================================\n";
echo "Verify Stock Keeper Menus\n";
echo "========================================\n\n";

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Email: {$stockKeeper->email}\n";
$role = $stockKeeper->role;
echo "Role: " . ($role ? $role->name : 'None') . "\n";
echo "Owner: {$stockKeeper->owner->email}\n\n";

// Test menu generation
echo "Menu Generation Test:\n";
$owner = $stockKeeper->owner;
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

// Check for Restaurant menus
$hasRestaurantMenus = false;
foreach ($menus as $menu) {
    if (isset($menu->business_type_id)) {
        $businessType = \App\Models\BusinessType::find($menu->business_type_id);
        if ($businessType && $businessType->slug === 'restaurant') {
            $hasRestaurantMenus = true;
            echo "\n⚠️  Found Restaurant menu: {$menu->name}\n";
        }
    }
}

if (!$hasRestaurantMenus) {
    echo "\n✓ No Restaurant menus found - Good!\n";
}

echo "\n========================================\n";
echo "Verification Complete\n";
echo "========================================\n";

