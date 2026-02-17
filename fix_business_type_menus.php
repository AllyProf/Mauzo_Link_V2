<?php
/**
 * Fix Business Type Menus - Attach menu items to Bar and Restaurant
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix Business Type Menus\n";
echo "========================================\n\n";

// Get Bar and Restaurant business types
$barType = BusinessType::where('slug', 'bar')->first();
$restaurantType = BusinessType::where('slug', 'restaurant')->first();

if (!$barType) {
    echo "❌ Bar business type not found\n";
    exit(1);
}

if (!$restaurantType) {
    echo "❌ Restaurant business type not found\n";
    exit(1);
}

echo "✓ Found Bar business type (ID: {$barType->id})\n";
echo "✓ Found Restaurant business type (ID: {$restaurantType->id})\n\n";

// Get all menu items that should be linked to Bar
$barMenuSlugs = [
    'bar-management', 'bar-suppliers', 'bar-products', 'bar-stock-receipts', 
    'bar-stock-transfers', 'bar-stock-transfers-available', 'bar-stock-transfers-all', 
    'bar-stock-transfers-history', 'bar-orders', 'bar-orders-create', 'bar-orders-all', 
    'bar-orders-drinks', 'bar-payments', 'bar-tables', 'bar-counter-settings',
    'beverage-inventory', 'beverage-inventory-overview', 'beverage-inventory-warehouse',
    'beverage-inventory-products', 'beverage-inventory-receipts', 'beverage-inventory-add',
    'beverage-inventory-levels', 'beverage-inventory-alerts', 'beverage-inventory-settings'
];

// Get all menu items that should be linked to Restaurant
$restaurantMenuSlugs = [
    'restaurant-management', 'restaurant-orders-food', 'table-management', 
    'kitchen-display', 'menu-management'
];

// Attach Bar menus
echo "Attaching Bar menu items...\n";
$barMenus = MenuItem::whereIn('slug', $barMenuSlugs)->get();
foreach ($barMenus as $menu) {
    BusinessTypeMenuItem::updateOrCreate(
        [
            'business_type_id' => $barType->id,
            'menu_item_id' => $menu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => $menu->sort_order ?? 0,
        ]
    );
    echo "  ✓ Attached: {$menu->name}\n";
}

// Also attach parent menus if they have children
$barParentMenus = MenuItem::whereIn('slug', ['bar-management', 'beverage-inventory'])
    ->whereNull('parent_id')
    ->get();
foreach ($barParentMenus as $menu) {
    BusinessTypeMenuItem::updateOrCreate(
        [
            'business_type_id' => $barType->id,
            'menu_item_id' => $menu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => $menu->sort_order ?? 0,
        ]
    );
    echo "  ✓ Attached parent: {$menu->name}\n";
}

// Attach Restaurant menus
echo "\nAttaching Restaurant menu items...\n";
$restaurantMenus = MenuItem::whereIn('slug', $restaurantMenuSlugs)->get();
foreach ($restaurantMenus as $menu) {
    BusinessTypeMenuItem::updateOrCreate(
        [
            'business_type_id' => $restaurantType->id,
            'menu_item_id' => $menu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => $menu->sort_order ?? 0,
        ]
    );
    echo "  ✓ Attached: {$menu->name}\n";
}

// Also attach parent menus
$restaurantParentMenus = MenuItem::whereIn('slug', ['restaurant-management', 'table-management', 'kitchen-display', 'menu-management'])
    ->whereNull('parent_id')
    ->get();
foreach ($restaurantParentMenus as $menu) {
    BusinessTypeMenuItem::updateOrCreate(
        [
            'business_type_id' => $restaurantType->id,
            'menu_item_id' => $menu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => $menu->sort_order ?? 0,
        ]
    );
    echo "  ✓ Attached parent: {$menu->name}\n";
}

// Check users with Bar and Restaurant enabled
echo "\nChecking users with Bar and Restaurant enabled...\n";
$users = User::whereHas('businessTypes', function($q) use ($barType, $restaurantType) {
    $q->whereIn('business_type_id', [$barType->id, $restaurantType->id])
      ->where('is_enabled', true);
})->get();

if ($users->count() > 0) {
    echo "✓ Found {$users->count()} user(s) with Bar/Restaurant enabled:\n";
    foreach ($users as $user) {
        $enabledTypes = $user->enabledBusinessTypes()->pluck('name')->toArray();
        echo "  - {$user->email}: " . implode(', ', $enabledTypes) . "\n";
    }
} else {
    echo "⚠️  No users found with Bar/Restaurant enabled\n";
}

echo "\n========================================\n";
echo "Fix Complete!\n";
echo "========================================\n\n";
echo "Menu items have been attached to Bar and Restaurant business types.\n";
echo "Please logout and login again to see the menus in the sidebar.\n";

