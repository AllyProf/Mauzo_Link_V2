<?php

use App\Models\MenuItem;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$items = MenuItem::where('route', 'accountant.reconciliations')->get();
foreach ($items as $item) {
    echo "Updating " . $item->name . " from " . $item->route . " to bar.manager.reconciliations\n";
    $item->update(['route' => 'bar.manager.reconciliations']);
}
echo "Done.\n";
