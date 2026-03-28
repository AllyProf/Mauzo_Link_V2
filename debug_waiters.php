<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$recs = \App\Models\WaiterDailyReconciliation::where('staff_shift_id', 1)->get();
foreach ($recs as $r) {
    echo "ID: {$r->id}, Waiter: {$r->waiter_id}, Expected: {$r->expected_amount}, Submitted: {$r->submitted_amount}, Status: {$r->status}\n";
}
echo "Total Sum: " . $recs->sum('submitted_amount') . "\n";
