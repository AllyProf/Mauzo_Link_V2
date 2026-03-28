<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$recs = \App\Models\WaiterDailyReconciliation::where('staff_shift_id', 1)->get();
foreach ($recs as $r) {
    echo "ID: {$r->id}, Date: {$r->reconciliation_date}\n";
}
