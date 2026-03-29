<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$res = DB::select("SHOW CREATE TABLE waiter_daily_reconciliations");
echo json_encode($res[0]);
