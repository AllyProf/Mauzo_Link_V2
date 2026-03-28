<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\BarOrder;
use Illuminate\Support\Facades\DB;

if ($staff) $ownerId = $staff->user_id;
else $ownerId = 1;

echo "Owner ID: $ownerId\n";

$orders = BarOrder::where('user_id', $ownerId)->get();
echo "Total Orders: " . $orders->count() . "\n";

foreach ($orders as $order) {
    echo "Order: {$order->order_number}, Status: {$order->status}, Payment Status: '{$order->payment_status}'\n";
}

$pendingCount = BarOrder::where('user_id', $ownerId)
    ->whereNotNull('waiter_id')
    ->where('status', 'pending')
    ->count();

$servedCount = BarOrder::where('user_id', $ownerId)
    ->whereNotNull('waiter_id')
    ->where('status', 'served')
    ->where('payment_status', 'pending')
    ->count();

echo "\nController Logic Results:\n";
echo "Pending Count: $pendingCount\n";
echo "Served Count: $servedCount\n";
