<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$staff = \App\Models\Staff::where('full_name', 'like', '%Counter Staff%')->with('role')->first();
$owner = $staff->owner;

$menuService = new \App\Services\MenuService();
$menus = $menuService->getStaffMenus($staff->role, $owner);

// Deep dive into business specific loop
$reflection = new ReflectionClass($menuService);
$method = $reflection->getMethod('getMenuChildrenForStaff');
$method->setAccessible(true);
$canAccessMethod = $reflection->getMethod('canAccessMenuForStaff');
$canAccessMethod->setAccessible(true);

$restaurant = \App\Models\BusinessType::where('slug', 'restaurant')->first();
$allCommonMenuIds = \App\Models\MenuItem::whereIn('slug', ['dashboard', 'sales', 'products', 'customers', 'staff', 'hr', 'reports', 'marketing', 'settings', 'accountant-parent', 'stock-audit', 'counter-reconciliation', 'chef-reconciliation', 'targets', 'common-purchase-requests'])->pluck('id');

$typeMenus = $restaurant->enabledMenuItems()
    ->whereNull('parent_id')
    ->where('is_active', true)
    ->whereNotIn('menu_items.id', $allCommonMenuIds->toArray())
    ->get();

echo "Checking Restaurant Top-level Items for Counter Role:\n";
foreach ($typeMenus as $menu) {
    echo " - " . $menu->name . " (Slug: " . $menu->slug . ")\n";
    $children = $method->invoke($menuService, $menu, $restaurant, $staff->role);
    echo "    - Children count: " . $children->count() . "\n";
    foreach ($children as $c) {
        echo "      - Child: " . $c->name . " (Route: " . ($c->route ?? 'null') . ")\n";
    }
    $canAccessParent = ($menu->route && $canAccessMethod->invoke($menuService, $staff->role, $menu));
    echo "    - Parent route check: " . ($canAccessParent ? 'TRUE' : 'FALSE') . "\n";
    $willPush = ($children->count() > 0 || $canAccessParent);
    echo "    - Will be added to sidebar: " . ($willPush ? 'YES' : 'NO') . "\n";
}
