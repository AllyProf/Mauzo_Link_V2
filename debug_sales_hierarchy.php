<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$sales = \App\Models\MenuItem::where('slug', 'sales')->first();
$children = \App\Models\MenuItem::where('parent_id', $sales->id)->get();

echo "Children of Sales:\n";
foreach ($children as $c) {
    echo " - " . $c->name . " (Slug: " . $c->slug . ", Route: " . ($c->route ?? 'null') . ")\n";
    $subchildren = \App\Models\MenuItem::where('parent_id', $c->id)->get();
    foreach ($subchildren as $sc) {
        echo "   - " . $sc->name . " (Slug: " . $sc->slug . ", Route: " . ($sc->route ?? 'null') . ")\n";
    }
}
