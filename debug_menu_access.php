<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$staff = \App\Models\Staff::where('full_name', 'like', '%Counter Staff%')->with('role')->first();
$owner = $staff->owner;
$sm = \App\Models\MenuItem::where('slug', 'bar-stock-mgmt')->first();

$menuService = new \App\Services\MenuService();
$reflection = new ReflectionClass($menuService);
$method = $reflection->getMethod('canAccessMenuForStaff');
$method->setAccessible(true);

// Mock behavior of getMenuChildrenForStaff
$businessType = \App\Models\BusinessType::where('slug', 'bar')->first();
$children = $businessType->enabledMenuItems()
    ->where('parent_id', $sm->id)
    ->where('is_active', true)
    ->get();

echo "Children of Stock Management:\n";
foreach ($children as $child) {
    $canAccess = $method->invoke($menuService, $staff->role, $child);
    echo " - " . $child->name . " (Route: " . ($child->route ?? 'null') . "): " . ($canAccess ? 'TRUE' : 'FALSE') . "\n";
}
