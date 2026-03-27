<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$sm = \App\Models\MenuItem::where('slug', 'bar-stock-mgmt')->first();
$rs = \App\Models\MenuItem::where('slug', 'bar-stock-receipts')->first();

echo "Bar Stock Mgmt ID: " . ($sm->id ?? 'null') . "\n";
echo "Receiving Stock Parent ID: " . ($rs->parent_id ?? 'null') . "\n";
echo "Receiving Stock Route: " . ($rs->route ?? 'null') . "\n";
