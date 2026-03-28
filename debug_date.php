<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$h = \App\Models\FinancialHandover::find(2);
echo "Handover Date: " . (string)$h->handover_date . "\n";
echo "Type: " . gettype($h->handover_date) . "\n";
