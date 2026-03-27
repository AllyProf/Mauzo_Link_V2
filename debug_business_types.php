<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$staff = \App\Models\Staff::where('full_name', 'like', '%Counter Staff%')->with('role')->first();
$owner = $staff->owner;

echo "Owner Business Types:\n";
$businessTypes = $owner->enabledBusinessTypes()->get();
foreach ($businessTypes as $bt) {
    echo " - " . $bt->name . " (Slug: " . $bt->slug . ")\n";
}

$sm = \App\Models\MenuItem::where('slug', 'bar-stock-mgmt')->first();
if ($sm) {
    echo "Stock Management ID: " . $sm->id . "\n";
    foreach ($businessTypes as $bt) {
        $isEnabled = $bt->enabledMenuItems()->where('menu_items.id', $sm->id)->exists();
        echo " - Enabled in " . $bt->name . ": " . ($isEnabled ? 'YES' : 'NO') . "\n";
    }
}
