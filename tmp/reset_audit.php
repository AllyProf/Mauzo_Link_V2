<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WaiterDailyReconciliation;
use App\Models\FinancialHandover;
use App\Models\BarOrder;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

$date = '2026-03-19';

echo "Cleaning up EVERYTHING for $date...\n";

DB::beginTransaction();
try {
    // 1. Delete Reconciliations
    WaiterDailyReconciliation::whereDate('reconciliation_date', $date)->delete();
    // 2. Delete Handovers
    FinancialHandover::whereDate('handover_date', $date)->delete();
    // 3. Delete Order Payments
    OrderPayment::whereDate('created_at', $date)->delete();
    // 4. Delete Order Items (Drinks)
    OrderItem::whereHas('order', function($q) use ($date) {
        $q->whereDate('created_at', $date);
    })->delete();
    // 5. Delete Orders themselves
    BarOrder::whereDate('created_at', $date)->delete();

    DB::commit();
    echo "SUCCESS: SYSTEM PURGE for $date. The page should now show ZERO SALES and ZERO COLLECTIONS.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
