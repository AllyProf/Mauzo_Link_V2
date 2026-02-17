<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$migrations = [
    '2025_11_29_190759_create_permission_tables',
    '2025_11_30_072117_create_orders_table',
    '2025_12_05_122922_create_sms_campaigns_table',
];

foreach ($migrations as $migration) {
    if (!DB::table('migrations')->where('migration', $migration)->exists()) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 1
        ]);
        echo "Marked: {$migration}\n";
    } else {
        echo "Already marked: {$migration}\n";
    }
}

echo "Done!\n";

