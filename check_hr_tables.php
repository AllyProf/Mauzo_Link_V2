<?php
/**
 * Check HR Tables
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Checking HR Tables...\n\n";

$tables = ['attendances', 'leaves', 'payrolls', 'performance_reviews'];

foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo ($exists ? "✓" : "✗") . " Table '{$table}': " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

echo "\n";

// Check if migrations exist
$migrationFiles = [
    '2025_12_15_000001_create_attendances_table.php',
    '2025_12_15_000002_create_leaves_table.php',
    '2025_12_15_000003_create_payrolls_table.php',
    '2025_12_15_000004_create_performance_reviews_table.php',
];

echo "Checking Migration Files...\n";
foreach ($migrationFiles as $file) {
    $path = __DIR__ . '/database/migrations/' . $file;
    $exists = file_exists($path);
    echo ($exists ? "✓" : "✗") . " {$file}: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

