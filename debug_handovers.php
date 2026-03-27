<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\FinancialHandover::orderBy('id', 'desc')->limit(10)->get() as $h) {
    echo "ID: {$h->id} Dept: {$h->department} Shift: {$h->staff_shift_id} Date: {$h->handover_date} Sales: {$h->amount} Profit: {$h->profit_amount}\n";
}
