<?php
/**
 * Verify Business Type Menus
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\User;
use App\Services\MenuService;

echo "========================================\n";
echo "Verify Business Type Menus\n";
echo "========================================\n\n";

// Get user
$user = User::where('email', 'owner@mauzo.com')->first();
if (!$user) {
    $user = User::where('role', '!=', 'admin')->first();
}

if (!$user) {
    echo "❌ No owner found\n";
    exit(1);
}

echo "Checking menus for: {$user->email}\n\n";

// Check enabled business types
$enabledTypes = $user->enabledBusinessTypes()->get();
echo "Enabled Business Types:\n";
foreach ($enabledTypes as $type) {
    echo "  - {$type->name} (slug: {$type->slug})\n";
}
echo "\n";

// Check menu items for each business type
foreach ($enabledTypes as $type) {
    echo "Menu items for {$type->name}:\n";
    $menus = $type->enabledMenuItems()
        ->whereNull('parent_id')
        ->where('is_active', true)
        ->get();
    
    if ($menus->count() > 0) {
        foreach ($menus as $menu) {
            $children = MenuItem::where('parent_id', $menu->id)->count();
            echo "  ✓ {$menu->name} (slug: {$menu->slug}, route: " . ($menu->route ?? 'none') . ", children: {$children})\n";
        }
    } else {
        echo "  ⚠️  No menu items found\n";
    }
    echo "\n";
}

// Test MenuService
echo "Testing MenuService...\n";
$menuService = new MenuService();
$userMenus = $menuService->getUserMenus($user);

echo "Total menus returned: {$userMenus->count()}\n";
echo "Menu names:\n";
foreach ($userMenus as $menu) {
    $childrenCount = isset($menu->children) ? $menu->children->count() : 0;
    echo "  - {$menu->name} (children: {$childrenCount})\n";
}

echo "\n========================================\n";
echo "Verification Complete\n";
echo "========================================\n";

