<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ownerId = 1;
$startStr = '2026-03-22 00:00:00';
$endStr   = '2026-03-28 23:59:59';

$profits = \Illuminate\Support\Facades\DB::table('order_items')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
    ->where('orders.user_id', $ownerId)
    ->where('orders.payment_status', 'paid')
    ->whereBetween('orders.created_at', [$startStr, $endStr])
    ->selectRaw('DATE(orders.created_at) as day_date, SUM((order_items.unit_price - COALESCE(product_variants.buying_price_per_unit, 0)) * order_items.quantity) as amount')
    ->groupByRaw('DATE(orders.created_at)')
    ->get()
    ->pluck('amount', 'day_date');

$revenues = \Illuminate\Support\Facades\DB::table('orders')
    ->where('user_id', $ownerId)
    ->whereBetween('created_at', [$startStr, $endStr])
    ->where('payment_status', 'paid')
    ->selectRaw('DATE(created_at) as day_date, SUM(total_amount) as amount')
    ->groupByRaw('DATE(created_at)')
    ->get()
    ->pluck('amount', 'day_date');

echo "Profits by day: " . json_encode($profits) . "\n";
echo "Revenue by day: " . json_encode($revenues) . "\n";

$rev = floatval($revenues->get('2026-03-28', 0));
$prof = floatval($profits->get('2026-03-28', 0));
echo "Revenue Mar28: $rev | Profit Mar28: $prof | Margin: " . round(($prof / $rev) * 100, 1) . "%\n";
