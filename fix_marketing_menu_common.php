<?php
/**
 * Add Marketing Menu to Common Menus
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;
use App\Models\User;

$owner = User::where('email', 'admin@medalion.com')->first();

echo "========================================\n";
echo "Add Marketing to Common Menus\n";
echo "========================================\n\n";

// Get Marketing menu
$marketingMenu = MenuItem::where('slug', 'marketing')->first();

if (!$marketingMenu) {
    echo "❌ Marketing menu not found\n";
    exit(1);
}

echo "✓ Found Marketing menu (ID: {$marketingMenu->id})\n\n";

// Get all active business types
$allBusinessTypes = BusinessType::where('is_active', true)->get();

echo "Linking Marketing menu to ALL business types as common menu...\n";

foreach ($allBusinessTypes as $bt) {
    $link = BusinessTypeMenuItem::firstOrCreate(
        [
            'business_type_id' => $bt->id,
            'menu_item_id' => $marketingMenu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => 8, // After Reports, before Settings
        ]
    );
    
    if ($link->wasRecentlyCreated) {
        echo "  ✓ Linked to {$bt->name}\n";
    } else {
        // Update to ensure it's enabled
        $link->is_enabled = true;
        $link->save();
        echo "  ✓ Updated link for {$bt->name}\n";
    }
}

// Also ensure it's linked to owner's business types
$ownerBusinessTypes = $owner->businessTypes;
foreach ($ownerBusinessTypes as $bt) {
    $link = BusinessTypeMenuItem::firstOrCreate(
        [
            'business_type_id' => $bt->id,
            'menu_item_id' => $marketingMenu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => 8,
        ]
    );
    $link->is_enabled = true;
    $link->save();
}

echo "\n✓ Marketing menu linked to all business types!\n\n";

// Verify children are accessible
$children = $marketingMenu->children;
echo "Marketing Menu Children:\n";
foreach ($children as $child) {
    echo "  - {$child->name} (route: {$child->route})\n";
    
    // Ensure children are also linked
    foreach ($allBusinessTypes as $bt) {
        BusinessTypeMenuItem::firstOrCreate(
            [
                'business_type_id' => $bt->id,
                'menu_item_id' => $child->id,
            ],
            [
                'is_enabled' => true,
                'sort_order' => $child->sort_order ?? 0,
            ]
        );
    }
}

echo "\n========================================\n";
echo "Marketing Menu Setup Complete!\n";
echo "========================================\n";
echo "\nThe Marketing menu should now appear in the sidebar.\n";
echo "Please refresh the page (F5) or log out and log back in.\n";







