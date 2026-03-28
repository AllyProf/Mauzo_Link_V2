<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$h = \App\Models\FinancialHandover::where('staff_shift_id', 1)->first(); // SHF-0001
if ($h) {
    echo "Handover ID: {$h->id}\n";
    echo "Amount: {$h->amount}\n";
    echo "Breakdown: " . json_encode($h->payment_breakdown) . "\n";
    echo "Status: {$h->status}\n";
} else {
    echo "No handover found for SHF-0001\n";
}
