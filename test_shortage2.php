<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$h = \App\Models\FinancialHandover::find(5);
if ($h) {
    echo json_encode($h->toArray());
} else {
    echo "Handover 5 not found\n";
}
