<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

foreach (['pos', 'bar-orders', 'transactions'] as $slug) {
    $m = \App\Models\MenuItem::where('slug', $slug)->first();
    echo "$slug Route: " . ($m->route ?? 'null') . "\n";
}
