<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$date = '2026-03-26';
$shiftId = 5;
$type = 'bar';

$profit = \App\Models\OrderItem::whereHas('order', function($q) use ($date, $shiftId) {
    $q->whereDate('created_at', $date)
      ->where('status', 'served')
      ->where('shift_id', $shiftId);
})->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
  ->selectRaw('SUM((order_items.unit_price - product_variants.buying_price_per_unit) * order_items.quantity) as profit')
  ->value('profit');

echo "Calculated Profit for Shift 5: {$profit}\n";

$allProfit = \App\Models\OrderItem::whereHas('order', function($q) use ($date) {
    $q->whereDate('created_at', $date)
      ->where('status', 'served');
})->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
  ->selectRaw('SUM((order_items.unit_price - product_variants.buying_price_per_unit) * order_items.quantity) as profit')
  ->value('profit');
echo "Total Profit for ALL shifts today: {$allProfit}\n";
