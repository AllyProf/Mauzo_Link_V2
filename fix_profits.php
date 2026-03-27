<?php
use App\Models\FinancialHandover;
use App\Models\OrderItem;
use App\Models\KitchenOrderItem;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$handovers = FinancialHandover::whereDate('handover_date', '2026-03-26')->get();

foreach ($handovers as $h) {
    echo "Processing Handover ID: {$h->id} (Shift: {$h->staff_shift_id}, Dept: {$h->department})\n";
    $type = $h->department;
    $shiftId = $h->staff_shift_id;
    $date = $h->handover_date;
    $ownerId = $h->user_id;

    $profitAmount = 0;
    if ($type === 'bar') {
        $profitAmount = OrderItem::whereHas('order', function($q) use ($ownerId, $date, $shiftId) {
            $q->where('user_id', $ownerId)->where('status', 'served');
            if ($shiftId) $q->where('shift_id', $shiftId);
            else $q->whereDate('created_at', $date);
        })->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
          ->selectRaw('SUM((order_items.unit_price - product_variants.buying_price_per_unit) * order_items.quantity) as profit')
          ->value('profit') ?? 0;
    } else {
        $profitAmount = KitchenOrderItem::whereHas('order', function($q) use ($ownerId, $date, $shiftId) {
            $q->where('user_id', $ownerId)->where('status', 'served');
            if ($shiftId) $q->where('shift_id', $shiftId);
            else $q->whereDate('created_at', $date);
        })->join('food_items', 'kitchen_order_items.food_item_id', '=', 'food_items.id')
          ->selectRaw('SUM((kitchen_order_items.unit_price - food_items.cost_price) * kitchen_order_items.quantity) as profit')
          ->value('profit') ?? 0;
    }

    echo "Old Profit: {$h->profit_amount} -> New Profit: {$profitAmount}\n";
    $h->update(['profit_amount' => $profitAmount]);
}
echo "Correction complete.\n";
