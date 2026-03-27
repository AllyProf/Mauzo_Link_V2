<?php
use App\Models\FinancialHandover;
use App\Models\BarOrder;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$h = FinancialHandover::find(15);
if (!$h) { echo "Handover 15 not found\n"; exit; }

echo "Auditing Handover #15 for Shift #{$h->staff_shift_id}\n";

$orders = BarOrder::where('shift_id', $h->staff_shift_id)
    ->where('payment_status', 'paid')
    ->with('orderPayments')
    ->get();

$breakdown = ['cash' => 0];

foreach ($orders as $o) {
    foreach ($o->orderPayments as $p) {
        $provider = strtolower(trim($p->mobile_money_number ?? ''));
        $method = strtolower($p->payment_method ?? '');
        $label = 'mobile_money';
        
        if ($method === 'cash') { $label = 'cash'; }
        elseif (str_contains($provider, 'mpesa') || str_contains($provider, 'm-pesa')) { $label = 'mpesa'; }
        elseif (str_contains($provider, 'stanbic')) { $label = 'stanbic'; }
        elseif (str_contains($provider, 'nmb')) { $label = 'nmb'; }
        elseif (str_contains($provider, 'crdb')) { $label = 'crdb'; }
        elseif (str_contains($provider, 'nbc')) { $label = 'nbc'; }
        elseif (str_contains($provider, 'kcb')) { $label = 'kcb'; }
        elseif (str_contains($provider, 'equity')) { $label = 'equity'; }
        elseif (str_contains($provider, 'mixx')) { $label = 'mixx'; }
        elseif (str_contains($provider, 'halo')) { $label = 'halopesa'; }
        elseif (str_contains($provider, 'tigo')) { $label = 'tigo_pesa'; }
        elseif (str_contains($provider, 'airtel')) { $label = 'airtel_money'; }
        elseif (str_contains($provider, 'visa')) { $label = 'visa'; }
        elseif (str_contains($provider, 'mastercard')) { $label = 'mastercard'; }
        elseif (str_contains($method, 'bank')) { $label = 'bank_transfer'; }
        
        $breakdown[$label] = ($breakdown[$label] ?? 0) + $p->amount;
    }
}

echo "New Breakdown: " . json_encode($breakdown) . "\n";
$h->update(['payment_breakdown' => $breakdown]);
echo "Handover #15 updated.\n";
