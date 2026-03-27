<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\ProductVariant;
use App\Models\StockLocation;

$variant = ProductVariant::where('name', 'LIKE', '%Fanta Orange%')->first();

if (!$variant) {
    echo "Fanta Orange variant not found.\n";
    exit;
}

$counterStock = StockLocation::where('product_variant_id', $variant->id)
    ->where('location', 'counter')
    ->first();

if ($counterStock) {
    echo "Counter Stock ID: " . $counterStock->id . "\n";
    echo "Quantity: " . $counterStock->quantity . "\n";
    echo "Selling Price: " . $counterStock->selling_price . "\n";
} else {
    echo "No counter stock found for this variant.\n";
}
