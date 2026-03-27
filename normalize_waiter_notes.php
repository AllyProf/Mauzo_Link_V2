<?php
use App\Models\WaiterDailyReconciliation;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$recs = WaiterDailyReconciliation::whereDate('reconciliation_date', '2026-03-26')
    ->get();

foreach ($recs as $r) {
    echo "Updating Waiter Rec #{$r->id}\n";
    if ($r->notes) {
        $notes = json_decode($r->notes, true);
        if (is_array($notes)) {
            $newRecorded = [];
            if (isset($notes['recorded_breakdown'])) {
                foreach($notes['recorded_breakdown'] as $k => $v) {
                    $kLower = strtolower(trim($k));
                    if (strpos($kLower, 'mpesa') !== false || strpos($kLower, 'm-pesa') !== false) $k = 'mpesa';
                    elseif (strpos($kLower, 'stanbic') !== false) $k = 'stanbic';
                    elseif (strpos($kLower, 'nmb') !== false) $k = 'nmb';
                    elseif (strpos($kLower, 'crdb') !== false) $k = 'crdb';
                    $newRecorded[$k] = $v;
                }
                $notes['recorded_breakdown'] = $newRecorded;
            }

            $newSubmitted = [];
            if (isset($notes['submitted_breakdown'])) {
                foreach($notes['submitted_breakdown'] as $k => $v) {
                    $kLower = strtolower(trim($k));
                    if (strpos($kLower, 'mpesa') !== false || strpos($kLower, 'm-pesa') !== false) $k = 'mpesa';
                    elseif (strpos($kLower, 'stanbic') !== false) $k = 'stanbic';
                    elseif (strpos($kLower, 'nmb') !== false) $k = 'nmb';
                    elseif (strpos($kLower, 'crdb') !== false) $k = 'crdb';
                    $newSubmitted[$k] = $v;
                }
                $notes['submitted_breakdown'] = $newSubmitted;
            }
            
            $r->update(['notes' => json_encode($notes)]);
        }
    }
}
echo "Waiter Reconciliation notes normalized.\n";
