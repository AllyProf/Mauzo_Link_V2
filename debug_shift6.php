<?php
use App\Models\StaffShift;
use App\Models\BarOrder;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = StaffShift::find(6);
$start = $s->opened_at;
$end = $s->closed_at;

echo "Shift 6: {$start} to {$end}\n";

$orders = BarOrder::whereBetween('created_at', [$start, $end])
    ->with('orderPayments')
    ->get();

echo "Orders: " . $orders->count() . "\n";
foreach($orders as $o) {
    echo "  Order #{$o->id} ShiftID: {$o->shift_id} Total: {$o->total_amount}\n";
    foreach($o->orderPayments as $p) {
        echo "    Payment ID: {$p->id} Method: {$p->payment_method} Provider: {$p->mobile_money_number} Amt: {$p->amount}\n";
    }
}
