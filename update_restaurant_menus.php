<?php
/**
 * Update Restaurant Menu Items with Routes
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;

echo "========================================\n";
echo "Update Restaurant Menu Items with Routes\n";
echo "========================================\n\n";

// Find restaurant business type
$restaurantType = BusinessType::where('slug', 'restaurant')->first();

if (!$restaurantType) {
    echo "❌ Restaurant business type not found.\n";
    exit(1);
}

echo "✓ Found Restaurant business type (ID: {$restaurantType->id})\n\n";

// Menu structure with routes
$menuStructure = [
    'restaurant-management' => [
        'name' => 'Restaurant Management',
        'slug' => 'restaurant-management',
        'icon' => 'fa-cutlery',
        'route' => null,
        'children' => [
            [
                'name' => 'Food Orders',
                'slug' => 'restaurant-orders-food',
                'icon' => 'fa-cutlery',
                'route' => 'bar.orders.food',
            ],
            [
                'name' => 'Staff Management',
                'slug' => 'restaurant-staff',
                'icon' => 'fa-users',
                'route' => 'staff.index',
            ],
            [
                'name' => 'Restaurant Reports',
                'slug' => 'restaurant-reports',
                'icon' => 'fa-chart-bar',
                'route' => 'bar.chef.reports',
            ],
            [
                'name' => 'Reconciliation',
                'slug' => 'restaurant-reconciliation',
                'icon' => 'fa-balance-scale',
                'route' => 'bar.chef.reconciliation',
            ],
        ],
    ],
    'table-management' => [
        'name' => 'Table Management',
        'slug' => 'table-management',
        'icon' => 'fa-table',
        'route' => null,
        'children' => [
            [
                'name' => 'Table Layout',
                'slug' => 'table-layout',
                'icon' => 'fa-th',
                'route' => 'bar.tables.index',
            ],
            [
                'name' => 'Table Status',
                'slug' => 'table-status',
                'icon' => 'fa-info-circle',
                'route' => 'bar.tables.index',
            ],
            [
                'name' => 'Reservations',
                'slug' => 'table-reservations',
                'icon' => 'fa-calendar',
                'route' => 'bar.tables.index',
            ],
        ],
    ],
    'kitchen-display' => [
        'name' => 'Kitchen Display',
        'slug' => 'kitchen-display',
        'icon' => 'fa-tv',
        'route' => null,
        'children' => [
            [
                'name' => 'Active Orders',
                'slug' => 'kitchen-active-orders',
                'icon' => 'fa-fire',
                'route' => 'bar.chef.dashboard',
            ],
            [
                'name' => 'Kitchen Settings',
                'slug' => 'kitchen-settings',
                'icon' => 'fa-cog',
                'route' => 'bar.chef.dashboard',
            ],
        ],
    ],
    'menu-management' => [
        'name' => 'Menu Management',
        'slug' => 'menu-management',
        'icon' => 'fa-book',
        'route' => null,
        'children' => [
            [
                'name' => 'Menu Items',
                'slug' => 'menu-items',
                'icon' => 'fa-list',
                'route' => 'bar.chef.food-items',
            ],
            [
                'name' => 'Menu Categories',
                'slug' => 'menu-categories',
                'icon' => 'fa-tags',
                'route' => 'bar.chef.food-items',
            ],
            [
                'name' => 'Menu Pricing',
                'slug' => 'menu-pricing',
                'icon' => 'fa-dollar-sign',
                'route' => 'bar.chef.food-items',
            ],
        ],
    ],
];

$updatedCount = 0;
$createdCount = 0;

foreach ($menuStructure as $parentSlug => $parentData) {
    // Find or create parent menu
    $parentMenu = MenuItem::where('slug', $parentSlug)->first();
    
    if (!$parentMenu) {
        echo "⚠️  Parent menu '{$parentData['name']}' not found. Creating...\n";
        $parentMenu = MenuItem::create([
            'name' => $parentData['name'],
            'slug' => $parentSlug,
            'icon' => $parentData['icon'],
            'route' => $parentData['route'],
            'is_active' => true,
            'parent_id' => null,
            'sort_order' => 0,
        ]);
        $createdCount++;
    } else {
        // Update parent menu
        $parentMenu->update([
            'name' => $parentData['name'],
            'icon' => $parentData['icon'],
            'route' => $parentData['route'],
            'is_active' => true,
        ]);
    }
    
    // Link parent to restaurant business type
    BusinessTypeMenuItem::firstOrCreate(
        [
            'business_type_id' => $restaurantType->id,
            'menu_item_id' => $parentMenu->id,
        ],
        [
            'is_enabled' => true,
            'sort_order' => 0,
        ]
    );
    
    echo "✓ {$parentData['name']} (ID: {$parentMenu->id})\n";
    
    // Process children
    if (isset($parentData['children'])) {
        $sortOrder = 1;
        foreach ($parentData['children'] as $childData) {
            $childMenu = MenuItem::where('slug', $childData['slug'])->first();
            
            if (!$childMenu) {
                $childMenu = MenuItem::create([
                    'name' => $childData['name'],
                    'slug' => $childData['slug'],
                    'icon' => $childData['icon'],
                    'route' => $childData['route'],
                    'is_active' => true,
                    'parent_id' => $parentMenu->id,
                    'sort_order' => $sortOrder,
                ]);
                $createdCount++;
                echo "  ✓ Created child: {$childData['name']} → {$childData['route']}\n";
            } else {
                $childMenu->update([
                    'name' => $childData['name'],
                    'icon' => $childData['icon'],
                    'route' => $childData['route'],
                    'parent_id' => $parentMenu->id,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]);
                $updatedCount++;
                echo "  ✓ Updated child: {$childData['name']} → {$childData['route']}\n";
            }
            
            // Link child to restaurant business type
            BusinessTypeMenuItem::firstOrCreate(
                [
                    'business_type_id' => $restaurantType->id,
                    'menu_item_id' => $childMenu->id,
                ],
                [
                    'is_enabled' => true,
                    'sort_order' => $sortOrder,
                ]
            );
            
            $sortOrder++;
        }
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Summary:\n";
echo "  Created: {$createdCount} menu item(s)\n";
echo "  Updated: {$updatedCount} menu item(s)\n";
echo "========================================\n";
echo "\n✓ Restaurant menu items updated with routes!\n";
echo "  Refresh your browser to see the changes.\n";

