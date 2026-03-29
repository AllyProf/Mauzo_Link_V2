<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$c = new \App\Http\Controllers\Bar\ManagerReconciliationController();
$m = new ReflectionMethod($c, 'getSystemExpectedBreakdown');
$m->setAccessible(true);
$result = $m->invoke($c, 1, '2026-03-29', 'bar', 6);
echo json_encode($result);
