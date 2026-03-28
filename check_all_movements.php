<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\StockMovement;

$movements = StockMovement::where('product_variant_id', 103)
    ->where('from_location', 'counter')
    ->orderBy('created_at', 'asc')
    ->get(['id', 'quantity', 'unit_price', 'created_at', 'notes']);

foreach ($movements as $m) {
    echo "ID: {$m->id}, Qty: {$m->quantity}, Price: {$m->unit_price}, Type: {$m->movement_type}, Time: {$m->created_at}, Note: {$m->notes}\n";
}
