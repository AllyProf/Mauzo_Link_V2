<?php
/**
 * Create HR Menu Items
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;

echo "========================================\n";
echo "Create HR Menu Items\n";
echo "========================================\n\n";

// Check if HR menu already exists
$existingMenu = MenuItem::where('slug', 'hr')->first();

if ($existingMenu) {
    echo "⚠️  HR menu already exists. Skipping creation.\n";
    exit(0);
}

// Create parent HR menu
$hrMenu = MenuItem::create([
    'name' => 'HR',
    'slug' => 'hr',
    'icon' => 'fa-users',
    'route' => 'hr.dashboard',
    'sort_order' => 50,
    'is_active' => true,
    'description' => 'Human Resources Management',
]);

echo "✓ Created HR parent menu\n";

// Create HR submenu items
$subMenus = [
    [
        'name' => 'HR Dashboard',
        'slug' => 'hr-dashboard',
        'icon' => 'fa-dashboard',
        'route' => 'hr.dashboard',
        'sort_order' => 1,
    ],
    [
        'name' => 'Attendance',
        'slug' => 'hr-attendance',
        'icon' => 'fa-check-circle',
        'route' => 'hr.attendance',
        'sort_order' => 2,
    ],
    [
        'name' => 'Leaves',
        'slug' => 'hr-leaves',
        'icon' => 'fa-calendar',
        'route' => 'hr.leaves',
        'sort_order' => 3,
    ],
    [
        'name' => 'Payroll',
        'slug' => 'hr-payroll',
        'icon' => 'fa-money',
        'route' => 'hr.payroll',
        'sort_order' => 4,
    ],
    [
        'name' => 'Performance Reviews',
        'slug' => 'hr-performance-reviews',
        'icon' => 'fa-star',
        'route' => 'hr.performance-reviews',
        'sort_order' => 5,
    ],
];

foreach ($subMenus as $subMenu) {
    MenuItem::create([
        'name' => $subMenu['name'],
        'slug' => $subMenu['slug'],
        'icon' => $subMenu['icon'],
        'route' => $subMenu['route'],
        'parent_id' => $hrMenu->id,
        'sort_order' => $subMenu['sort_order'],
        'is_active' => true,
    ]);
    echo "✓ Created {$subMenu['name']} menu item\n";
}

// Attach to all business types
$businessTypes = BusinessType::all();
foreach ($businessTypes as $bt) {
    $hrMenu->businessTypes()->attach($bt->id, [
        'is_enabled' => true,
        'sort_order' => 50,
    ]);
}
echo "\n✓ Attached HR menu to all business types\n";

echo "\n========================================\n";
echo "HR Menu Created Successfully!\n";
echo "========================================\n";

