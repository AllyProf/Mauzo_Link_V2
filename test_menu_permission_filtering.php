<?php
/**
 * Test Menu Permission Filtering for Stock Keeper
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\MenuItem;
use App\Services\MenuService;

$stockKeeper = Staff::where('email', 'stockkeeper@mauzo.com')->first();

if (!$stockKeeper) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

echo "========================================\n";
echo "Test Menu Permission Filtering\n";
echo "========================================\n\n";

$role = $stockKeeper->role;
$owner = $stockKeeper->owner;

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Role: {$role->name}\n";
echo "Has suppliers.view: " . ($role->hasPermission('suppliers', 'view') ? 'YES' : 'NO') . "\n\n";

// Get Bar Management menu
$barManagement = MenuItem::where('slug', 'bar-management')->first();
if ($barManagement) {
    echo "Bar Management Menu:\n";
    echo "  ID: {$barManagement->id}\n";
    echo "  Route: " . ($barManagement->route ?? 'none') . "\n\n";
    
    // Get Suppliers child menu
    $suppliersMenu = MenuItem::where('slug', 'bar-suppliers')->first();
    if ($suppliersMenu) {
        echo "Suppliers Menu:\n";
        echo "  ID: {$suppliersMenu->id}\n";
        echo "  Route: {$suppliersMenu->route}\n";
        echo "  Parent ID: {$suppliersMenu->parent_id}\n\n";
        
        // Test permission check
        $menuService = new MenuService();
        $canAccess = $menuService->canAccessMenuForStaff($role, $suppliersMenu);
        echo "Can access Suppliers menu: " . ($canAccess ? 'YES' : 'NO') . "\n";
    }
}

// Test full menu generation
echo "\nFull Menu Generation:\n";
$menuService = new MenuService();
$menus = $menuService->getStaffMenus($role, $owner);

foreach ($menus as $menu) {
    if ($menu->slug === 'bar-management') {
        echo "Bar Management found with " . (isset($menu->children) ? $menu->children->count() : 0) . " children\n";
        if (isset($menu->children)) {
            foreach ($menu->children as $child) {
                $hasAccess = $menuService->canAccessMenuForStaff($role, $child);
                echo "  - {$child->name} (route: " . ($child->route ?? 'none') . "): " . ($hasAccess ? 'VISIBLE' : 'HIDDEN') . "\n";
            }
        }
    }
}

echo "\n========================================\n";
echo "Test Complete\n";
echo "========================================\n";

