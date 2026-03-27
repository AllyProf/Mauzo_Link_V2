<?php
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$recent = OrderPayment::where('payment_method', '!=', 'cash')
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

foreach ($recent as $p) {
    echo "ID: {$p->id}, Method: {$p->payment_method}, Number: {$p->mobile_money_number}, Amt: {$p->amount}\n";
}
