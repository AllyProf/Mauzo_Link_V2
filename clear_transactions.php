<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

\App\Models\WaiterDailyReconciliation::truncate();
\App\Models\FinancialHandover::truncate();
\App\Models\BarOrder::truncate();
\App\Models\OrderItem::truncate();
\App\Models\OrderPayment::truncate();
\App\Models\CounterExpense::truncate();
\App\Models\StaffShift::truncate();

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "All transactional data (Orders, Reconciliations, Handovers, Shifts) cleared successfully.\n";
