<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$staff = \App\Models\Staff::where('full_name', 'like', '%Counter Staff%')->with('role')->first();
$owner = $staff->owner;

$menuService = new \App\Services\MenuService();
$menus = $menuService->getStaffMenus($staff->role, $owner);

echo "Final Menus for Counter Role:\n";
foreach ($menus as $menu) {
    echo " - " . $menu->name . " (Slug: " . ($menu->slug ?? 'null') . ")";
    if (isset($menu->children) && $menu->children->count() > 0) {
        echo " [Children: " . $menu->children->pluck('name')->implode(', ') . "]";
    }
    echo "\n";
}
