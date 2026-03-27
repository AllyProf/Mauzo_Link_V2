<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$staff = \App\Models\Staff::where('full_name', 'like', '%Counter Staff%')->with('role')->first();
$owner = $staff->owner;

$menuService = new \App\Services\MenuService();
$reflection = new ReflectionClass($menuService);
$ms = $menuService;

// Access private method getStaffMenus? It's public.
$menus = $ms->getStaffMenus($staff->role, $owner);

echo "Final Menus Count: " . $menus->count() . "\n";
foreach ($menus as $m) {
    echo " - " . $m->name . " (" . $m->slug . ")\n";
}

// Check Restaurant specifically
$restaurant = \App\Models\BusinessType::where('slug', 'restaurant')->first();
$allCommonMenuIds = \App\Models\MenuItem::whereIn('slug', ['dashboard', 'sales', 'products', 'customers', 'staff', 'hr', 'reports', 'marketing', 'settings', 'accountant-parent', 'stock-audit', 'counter-reconciliation', 'chef-reconciliation', 'targets', 'common-purchase-requests'])->pluck('id');

$typeMenus = $restaurant->enabledMenuItems()
    ->whereNull('parent_id')
    ->where('is_active', true)
    ->whereNotIn('menu_items.id', $allCommonMenuIds->toArray())
    ->get();

echo "Restaurant Top-level (Non-Common): " . $typeMenus->count() . "\n";
foreach ($typeMenus as $tm) {
    echo "   - " . $tm->name . " (Slug: " . $tm->slug . ")\n";
}
