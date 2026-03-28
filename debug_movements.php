<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\StockMovement;
use App\Models\ProductVariant;

$variant = ProductVariant::where('name', 'like', '%Serengeti Apple%')->first();
if (!$variant) {
    echo "Variant not found\n";
    exit;
}

echo "Found Variant: " . $variant->id . " - " . $variant->name . "\n";

$movements = StockMovement::where('product_variant_id', $variant->id)->get();
echo "Total Stock Movements: " . $movements->count() . "\n";

foreach ($movements as $m) {
    echo "Movement: {$m->type}, Qty: {$m->quantity}, From: {$m->from_location}, To: {$m->to_location}, Date: {$m->created_at}\n";
}
