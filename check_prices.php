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

echo "Product Variant: " . $variant->name . " (ID: " . $variant->id . ")\n";
echo "Buying Price Per Unit: " . $variant->buying_price_per_unit . "\n";
echo "Selling Price Per Unit: " . $variant->selling_price_per_unit . "\n";

$warehouseStock = StockLocation::where('product_variant_id', $variant->id)
    ->where('location', 'warehouse')
    ->first();

if ($warehouseStock) {
    echo "Warehouse Stock ID: " . $warehouseStock->id . "\n";
    echo "Quantity: " . $warehouseStock->quantity . "\n";
    echo "Average Buying Price: " . $warehouseStock->average_buying_price . "\n";
    echo "Selling Price: " . $warehouseStock->selling_price . "\n";
} else {
    echo "No warehouse stock found for this variant.\n";
}
