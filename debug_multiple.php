<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$handovers = \App\Models\FinancialHandover::where('staff_shift_id', 1)->get();
foreach ($handovers as $h) {
    echo "ID: {$h->id}, Amount: {$h->amount}, Date: {$h->handover_date}, Notes: {$h->notes}\n";
}
