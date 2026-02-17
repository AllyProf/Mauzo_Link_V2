<?php
/**
 * Create Marketing Menu Items
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;
use App\Models\User;

$owner = User::where('email', 'admin@medalion.com')->first();

if (!$owner) {
    echo "❌ Owner not found\n";
    exit(1);
}

echo "========================================\n";
echo "Create Marketing Menu Items\n";
echo "========================================\n\n";

// Get all business types for this owner
$businessTypes = $owner->businessTypes;

if ($businessTypes->count() === 0) {
    echo "⚠️  No business types found. Creating Marketing menu as common menu.\n";
    $businessTypes = BusinessType::where('is_active', true)->get();
}

// Create Marketing parent menu
$marketingMenu = MenuItem::updateOrCreate(
    ['slug' => 'marketing'],
    [
        'name' => 'Marketing',
        'icon' => 'fa-bullhorn',
        'route' => null, // Parent menu
        'parent_id' => null,
        'sort_order' => 8,
        'is_active' => true,
    ]
);

echo "✓ Created Marketing parent menu (ID: {$marketingMenu->id})\n";

// Create Marketing child menus
$marketingChildren = [
    [
        'name' => 'Marketing Dashboard',
        'slug' => 'marketing-dashboard',
        'icon' => 'fa-dashboard',
        'route' => 'marketing.dashboard',
        'sort_order' => 1,
    ],
    [
        'name' => 'Customer Database',
        'slug' => 'marketing-customers',
        'icon' => 'fa-users',
        'route' => 'marketing.customers',
        'sort_order' => 2,
    ],
    [
        'name' => 'Campaigns',
        'slug' => 'marketing-campaigns',
        'icon' => 'fa-paper-plane',
        'route' => 'marketing.campaigns',
        'sort_order' => 3,
    ],
    [
        'name' => 'Create Campaign',
        'slug' => 'marketing-create-campaign',
        'icon' => 'fa-plus-circle',
        'route' => 'marketing.campaigns.create',
        'sort_order' => 4,
    ],
    [
        'name' => 'Templates',
        'slug' => 'marketing-templates',
        'icon' => 'fa-file-text',
        'route' => 'marketing.templates',
        'sort_order' => 5,
    ],
];

foreach ($marketingChildren as $child) {
    $childMenu = MenuItem::updateOrCreate(
        ['slug' => $child['slug']],
        array_merge($child, [
            'parent_id' => $marketingMenu->id,
            'is_active' => true,
        ])
    );
    echo "✓ Created menu: {$child['name']}\n";
}

// Link Marketing menu to all business types
echo "\nLinking Marketing menu to business types...\n";
foreach ($businessTypes as $businessType) {
    BusinessTypeMenuItem::firstOrCreate(
        [
            'business_type_id' => $businessType->id,
            'menu_item_id' => $marketingMenu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => 8,
        ]
    );
    echo "✓ Linked to: {$businessType->name}\n";
}

// Also link as common menu (available to all)
echo "\nLinking Marketing menu as common menu...\n";
$allBusinessTypes = BusinessType::where('is_active', true)->get();
foreach ($allBusinessTypes as $bt) {
    BusinessTypeMenuItem::firstOrCreate(
        [
            'business_type_id' => $bt->id,
            'menu_item_id' => $marketingMenu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => 8,
        ]
    );
}

echo "\n========================================\n";
echo "Marketing Menu Created!\n";
echo "========================================\n";
echo "\nThe Marketing menu should now appear in the sidebar.\n";
echo "Please refresh the page or log out and log back in.\n";







