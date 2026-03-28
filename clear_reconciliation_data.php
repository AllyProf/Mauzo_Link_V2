<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "Clearing Reconciliation and Payment data...\n";

    // Disable foreign key checks to truncate safely
    Schema::disableForeignKeyConstraints();

    // 1. Clear Waiter Reconciliations
    DB::table('waiter_daily_reconciliations')->truncate();
    echo "- Waiter Daily Reconciliations cleared.\n";

    // 2. Clear Financial Handovers (connected to reconciliations)
    DB::table('financial_handovers')->truncate();
    echo "- Financial Handovers cleared.\n";

    // 3. Clear Order Payments
    DB::table('order_payments')->truncate();
    echo "- Order Payments cleared.\n";

    // 4. Clear Orders and Order Items (to ensure no orphaned payments/reconciliations)
    DB::table('order_items')->truncate();
    DB::table('kitchen_order_items')->truncate();
    DB::table('orders')->truncate();
    echo "- Orders and Order Items cleared.\n";

    // 5. Clear Petty Cash (often related to daily finance)
    DB::table('petty_cash_issues')->truncate();
    echo "- Petty Cash issues cleared.\n";

    Schema::enableForeignKeyConstraints();

    echo "\nSUCCESS: Data cleared. System is now ready for fresh testing.\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
