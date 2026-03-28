<?php

use Illuminate\Support\Facades\DB;
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\StockMovement;
use App\Models\BarOrder;

$movements = StockMovement::where('product_variant_id', 103)
    ->where('from_location', 'counter')
    ->orderBy('created_at', 'asc')
    ->get();

foreach ($movements as $m) {
    echo "ID: {$m->id}, Qty: {$m->quantity}, Price: {$m->unit_price}, Type: {$m->reference_type}, ID: {$m->reference_id}\n";
    $order = \App\Models\BarOrder::find($m->reference_id);
    if ($order) {
        $payments = \DB::table('order_payments')->where('order_id', $order->id)->sum('amount');
        echo "   --- STATUS: Total: {$order->total_amount}, Paid: {$payments}, Payment: {$order->payment_status}\n";
    } else {
        echo "   --- ORDER NOT FOUND\n";
    }
}
