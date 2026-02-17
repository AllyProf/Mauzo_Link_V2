<?php
/**
 * Test Stock Transfer Access
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;

$stockKeeper = Staff::where('email', 'stockkeeper@mauzo.com')->first();

if (!$stockKeeper) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

$role = $stockKeeper->role;

echo "========================================\n";
echo "Test Stock Transfer Access\n";
echo "========================================\n\n";

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Role: {$role->name}\n\n";

// Check permissions
$hasStockTransferView = $role->hasPermission('stock_transfer', 'view');
$hasInventoryView = $role->hasPermission('inventory', 'view');

echo "Permissions:\n";
echo "  stock_transfer.view: " . ($hasStockTransferView ? "✓ YES" : "✗ NO") . "\n";
echo "  inventory.view: " . ($hasInventoryView ? "✓ YES" : "✗ NO") . "\n\n";

// Check role name
$roleName = strtolower(trim($role->name ?? ''));
$isStockKeeper = in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper']);

echo "Role check:\n";
echo "  Role name: '{$roleName}'\n";
echo "  Matches allowed roles: " . ($isStockKeeper ? "✓ YES" : "✗ NO") . "\n\n";

echo "========================================\n";
echo "Analysis\n";
echo "========================================\n";

$canView = $hasStockTransferView || $hasInventoryView || $isStockKeeper;

if ($canView) {
    echo "✓ Stock Keeper SHOULD have access to /bar/stock-transfers\n";
    echo "Based on:\n";
    if ($hasStockTransferView) echo "  - stock_transfer.view permission\n";
    if ($hasInventoryView) echo "  - inventory.view permission\n";
    if ($isStockKeeper) echo "  - Stock Keeper role name\n";
} else {
    echo "✗ Stock Keeper does NOT have access\n";
}

