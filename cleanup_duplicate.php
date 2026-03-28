<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

\App\Models\FinancialHandover::destroy(3);
echo "Deleted handover ID: 3 (Duplicate with incorrect date/amount)\n";
