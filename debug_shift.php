<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\BarOrder::where('shift_id', 5)->get() as $o) {
    echo "Order #{$o->id} Num: {$o->order_number} Total: {$o->total_amount} Shift: {$o->shift_id}\n";
}
echo "Total Profit for Shift 5 calculation:\n";

$profit = \App\Models\OrderItem::whereHas('order', function($q) {
    $q->where('shift_id', 5);
})->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
  ->selectRaw('SUM((order_items.unit_price - product_variants.buying_price) * order_items.quantity) as profit')
  ->first();
echo "Calculated Profit: {$profit->profit}\n";
