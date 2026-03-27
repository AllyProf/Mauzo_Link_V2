<?php

use App\Models\BarOrder;
use App\Models\OrderPayment;
use App\Models\FinancialHandover;
use App\Models\WaiterDailyReconciliation;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel if needed (but we can just use artisan tinker to run this script)
$date = '2026-03-19';

$orders = BarOrder::whereDate('created_at', $date)->get();
foreach ($orders as $order) {
    $order->items()->delete();
    $order->delete();
}

OrderPayment::whereDate('created_at', $date)->delete();
FinancialHandover::whereDate('handover_date', $date)->delete();
WaiterDailyReconciliation::whereDate('reconciliation_date', $date)->delete();

echo "Complete wipe of all records (Orders, Payments, Reconciliations, Handovers) for March 19, 2026 successful!\n";
