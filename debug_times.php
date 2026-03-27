<?php
use App\Models\StaffShift;
use App\Models\BarOrder;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = StaffShift::find(5);
$start = $s->opened_at;
$end = $s->closed_at;

echo "Shift 5: {$start} to {$end}\n";

$orders = BarOrder::whereBetween('created_at', [$start, $end])
    ->where('status', 'served')
    ->get();

echo "Orders between those times: " . $orders->count() . "\n";
foreach($orders as $o) {
    echo "  Order #{$o->id} ShiftID: {$o->shift_id} Total: {$o->total_amount} Cash: {$o->orderPayments->where('payment_method', 'cash')->sum('amount')} Digital: {$o->orderPayments->where('payment_method', '!=', 'cash')->sum('amount')}\n";
}
