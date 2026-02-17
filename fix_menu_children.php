<?php
/**
 * Fix Menu Children - Ensure all child menu items are attached to business types
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;

echo "========================================\n";
echo "Fix Menu Children Attachment\n";
echo "========================================\n\n";

$barType = BusinessType::where('slug', 'bar')->first();
$restaurantType = BusinessType::where('slug', 'restaurant')->first();

if (!$barType || !$restaurantType) {
    echo "❌ Business types not found\n";
    exit(1);
}

// Get all parent menus for Bar and Restaurant
$barParents = MenuItem::whereIn('slug', ['bar-management', 'beverage-inventory'])
    ->whereNull('parent_id')
    ->get();

$restaurantParents = MenuItem::whereIn('slug', ['restaurant-management', 'table-management', 'kitchen-display', 'menu-management'])
    ->whereNull('parent_id')
    ->get();

echo "Fixing Bar menu children...\n";
foreach ($barParents as $parent) {
    $children = MenuItem::where('parent_id', $parent->id)->get();
    foreach ($children as $child) {
        BusinessTypeMenuItem::updateOrCreate(
            [
                'business_type_id' => $barType->id,
                'menu_item_id' => $child->id,
            ],
            [
                'is_enabled' => true,
                'sort_order' => $child->sort_order ?? 0,
            ]
        );
        echo "  ✓ Attached child: {$child->name} to Bar\n";
    }
}

echo "\nFixing Restaurant menu children...\n";
foreach ($restaurantParents as $parent) {
    $children = MenuItem::where('parent_id', $parent->id)->get();
    foreach ($children as $child) {
        BusinessTypeMenuItem::updateOrCreate(
            [
                'business_type_id' => $restaurantType->id,
                'menu_item_id' => $child->id,
            ],
            [
                'is_enabled' => true,
                'sort_order' => $child->sort_order ?? 0,
            ]
        );
        echo "  ✓ Attached child: {$child->name} to Restaurant\n";
    }
}

// Also attach all children of common menus to both business types
echo "\nAttaching common menu children...\n";
$commonParents = MenuItem::whereIn('slug', ['sales', 'products', 'customers', 'staff'])
    ->whereNull('parent_id')
    ->get();

foreach ([$barType, $restaurantType] as $type) {
    foreach ($commonParents as $parent) {
        $children = MenuItem::where('parent_id', $parent->id)->get();
        foreach ($children as $child) {
            BusinessTypeMenuItem::updateOrCreate(
                [
                    'business_type_id' => $type->id,
                    'menu_item_id' => $child->id,
                ],
                [
                    'is_enabled' => true,
                    'sort_order' => $child->sort_order ?? 0,
                ]
            );
        }
    }
    echo "  ✓ Attached common menu children to {$type->name}\n";
}

echo "\n========================================\n";
echo "Fix Complete!\n";
echo "========================================\n";
echo "All menu children have been attached to business types.\n";
echo "Please logout and login again to see the menus.\n";

