<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;

// Create Parent 'Financial Analytics'
$parent = MenuItem::updateOrCreate(
    ['slug' => 'bar-financial-analytics'],
    ['name' => 'Financial Analytics', 'icon' => 'fa-line-chart', 'route' => null, 'sort_order' => 15, 'is_active' => true]
);

// Create Child 'Master Sheet Trends'
$child = MenuItem::updateOrCreate(
    ['slug' => 'manager-master-sheet-analytics'],
    ['name' => 'Master Sheet Trends', 'icon' => 'fa-area-chart', 'route' => 'manager.master-sheet.analytics', 'parent_id' => $parent->id, 'sort_order' => 1, 'is_active' => true]
);

// Link to bar business type
$bar = BusinessType::where('slug', 'bar')->first();
if ($bar) {
    BusinessTypeMenuItem::updateOrCreate(
        ['business_type_id' => $bar->id, 'menu_item_id' => $parent->id],
        ['is_enabled' => true, 'sort_order' => 15]
    );
    BusinessTypeMenuItem::updateOrCreate(
        ['business_type_id' => $bar->id, 'menu_item_id' => $child->id],
        ['is_enabled' => true, 'sort_order' => 1]
    );
    echo "Successfully updated Sidebar for MAP-BAR Business Type.\n";
} else {
    echo "Error: 'bar' business type not found.\n";
}
?>
