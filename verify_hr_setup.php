<?php
/**
 * Verify HR Setup
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "HR Setup Verification\n";
echo "========================================\n\n";

// Check if HR tables exist
echo "Checking HR Tables:\n";
$tables = ['attendances', 'leaves', 'payrolls', 'performance_reviews'];
foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo $exists ? "✓ " : "✗ ";
    echo "Table '{$table}': " . ($exists ? "EXISTS" : "MISSING") . "\n";
}
echo "\n";

// Check HR permissions
echo "Checking HR Permissions:\n";
$hrPermissions = Permission::where('module', 'hr')->get();
if ($hrPermissions->count() > 0) {
    echo "✓ Found {$hrPermissions->count()} HR permissions:\n";
    foreach ($hrPermissions as $perm) {
        echo "  - {$perm->name} ({$perm->module}.{$perm->action})\n";
    }
} else {
    echo "✗ No HR permissions found. Run: php artisan db:seed --class=PermissionSeeder\n";
}
echo "\n";

// Check if HR routes are accessible
echo "Checking HR Controller:\n";
$controllerPath = __DIR__ . '/app/Http/Controllers/HRController.php';
if (file_exists($controllerPath)) {
    echo "✓ HRController.php exists\n";
} else {
    echo "✗ HRController.php missing\n";
}
echo "\n";

// Check HR views
echo "Checking HR Views:\n";
$views = ['dashboard', 'attendance', 'leaves', 'payroll', 'performance-reviews'];
foreach ($views as $view) {
    $viewPath = __DIR__ . "/resources/views/hr/{$view}.blade.php";
    $exists = file_exists($viewPath);
    echo $exists ? "✓ " : "✗ ";
    echo "View 'hr/{$view}.blade.php': " . ($exists ? "EXISTS" : "MISSING") . "\n";
}
echo "\n";

echo "========================================\n";
echo "Verification Complete!\n";
echo "========================================\n";

