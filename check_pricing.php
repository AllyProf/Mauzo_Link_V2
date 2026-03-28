<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\StockReceipt;

$r1 = StockReceipt::where('receipt_number', 'like', '%0001')->first();
$r2 = StockReceipt::where('receipt_number', 'like', '%0002')->first();

echo "Batch 1: Qty: {$r1->total_units}, BP: {$r1->buying_price_per_unit}, SP: {$r1->selling_price_per_unit}, Total SP Value: {$r1->total_selling_value}, Total Profit: {$r1->total_profit}\n";
echo "Batch 2: Qty: {$r2->total_units}, BP: {$r2->buying_price_per_unit}, SP: {$r2->selling_price_per_unit}, Total SP Value: {$r2->total_selling_value}, Total Profit: {$r2->total_profit}\n";
