<?php
use App\Models\StaffShift;
use App\Models\BarOrder;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shifts = StaffShift::orderBy('id', 'desc')->limit(10)->get();

foreach ($shifts as $s) {
    echo "Auditing Shift #{$s->id} ({$s->shift_number}) for Staff #{$s->staff_id}\n";
    
    // Total Sales in this shift
    // We only count orders that were PAID during this shift by the person who owned the shift
    $orders = BarOrder::where('shift_id', $s->id)
        ->where('status', 'served')
        ->where('payment_status', 'paid')
        ->with('orderPayments')
        ->get();
    
    $cashSales = 0;
    $digitalSales = 0;
    
    foreach ($orders as $o) {
        $orderCash = $o->orderPayments->where('payment_method', 'cash')->sum('amount');
        $orderDigital = $o->orderPayments->where('payment_method', '!=', 'cash')->sum('amount');
        
        // If no payment records, fallback to order summary
        if ($o->orderPayments->count() === 0) {
            if ($o->payment_method === 'cash') $orderCash = $o->paid_amount;
            else $orderDigital = $o->paid_amount;
        }
        
        $cashSales += $orderCash;
        $digitalSales += $orderDigital;
    }
    
    // Expected Closing Balance = Opening Balance + Cash Sales
    $expected = $s->opening_balance + $cashSales;
    
    echo "  DB  -> Cash: {$s->total_sales_cash}, Digital: {$s->total_sales_digital}, Expected: {$s->expected_closing_balance}\n";
    echo "  Recal -> Cash: {$cashSales}, Digital: {$digitalSales}, Expected: {$expected}\n";
    
    $s->update([
        'total_sales_cash' => $cashSales,
        'total_sales_digital' => $digitalSales,
        'expected_closing_balance' => $expected
    ]);
}
echo "Shift data correction complete.\n";
