<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

print "========================================\n";
print "HR Tables Verification\n";
print "========================================\n\n";

$tables = [
    'attendances' => 'Attendances',
    'leaves' => 'Leaves',
    'payrolls' => 'Payrolls',
    'performance_reviews' => 'Performance Reviews',
];

$allExist = true;

foreach ($tables as $table => $name) {
    try {
        $exists = Schema::hasTable($table);
        if ($exists) {
            $count = DB::table($table)->count();
            print "✓ {$name} table: EXISTS ({$count} records)\n";
        } else {
            print "✗ {$name} table: MISSING\n";
            $allExist = false;
        }
    } catch (\Exception $e) {
        print "✗ {$name} table: ERROR - " . $e->getMessage() . "\n";
        $allExist = false;
    }
}

print "\n";

if ($allExist) {
    print "✅ All HR tables exist!\n";
    print "\nYou can now access /hr/dashboard\n";
} else {
    print "❌ Some tables are missing. Run migrations:\n";
    print "   php artisan migrate\n";
}

