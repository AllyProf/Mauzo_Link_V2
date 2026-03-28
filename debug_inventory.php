<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\OrderItem;
use App\Models\ProductVariant;

$variant = ProductVariant::where('name', 'like', '%Serengeti Apple%')->first();
if (!$variant) {
    echo "Variant not found\n";
    exit;
}

echo "Found Variant: " . $variant->id . " - " . $variant->name . "\n";

$items = OrderItem::where('product_variant_id', $variant->id)
    ->with('order')
    ->get();

echo "Total Order Items: " . $items->count() . "\n";

$summary = [];
foreach ($items as $item) {
    $status = $item->order->status ?? 'N/A';
    $paymentStatus = $item->order->payment_status ?? 'N/A';
    $key = "$status / $paymentStatus";
    if (!isset($summary[$key])) {
        $summary[$key] = ['count' => 0, 'qty' => 0];
    }
    $summary[$key]['count']++;
    $summary[$key]['qty'] += $item->quantity;
}

print_r($summary);
