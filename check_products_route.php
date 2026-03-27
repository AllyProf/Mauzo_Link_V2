<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$p = \App\Models\MenuItem::where('slug', 'products')->first();
echo "Route: " . ($p->route ?? 'null') . "\n";
