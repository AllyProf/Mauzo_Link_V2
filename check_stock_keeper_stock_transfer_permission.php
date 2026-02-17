<?php
/**
 * Check Stock Keeper Stock Transfer Permissions
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
echo "Check Stock Keeper Stock Transfer Permissions\n";
echo "========================================\n\n";

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Role: {$role->name}\n\n";

// Check stock_transfer permissions
$hasStockTransferView = $role->hasPermission('stock_transfer', 'view');
$hasStockTransferCreate = $role->hasPermission('stock_transfer', 'create');
$hasInventoryView = $role->hasPermission('inventory', 'view');

echo "Permissions:\n";
echo "  stock_transfer.view: " . ($hasStockTransferView ? "✓ YES" : "✗ NO") . "\n";
echo "  stock_transfer.create: " . ($hasStockTransferCreate ? "✓ YES" : "✗ NO") . "\n";
echo "  inventory.view: " . ($hasInventoryView ? "✓ YES" : "✗ NO") . "\n\n";

// Check if role name matches
$roleName = strtolower(trim($role->name ?? ''));
$isStockKeeper = in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper']);
echo "Role name check:\n";
echo "  Role name: '{$roleName}'\n";
echo "  Matches Stock Keeper: " . ($isStockKeeper ? "✓ YES" : "✗ NO") . "\n\n";

echo "========================================\n";
echo "Analysis\n";
echo "========================================\n";

if ($hasStockTransferView || $hasInventoryView || $isStockKeeper) {
    echo "✓ Stock Keeper should have access to /bar/stock-transfers/available\n";
    echo "The controller should allow access based on:\n";
    if ($hasStockTransferView) echo "  - stock_transfer.view permission\n";
    if ($hasInventoryView) echo "  - inventory.view permission\n";
    if ($isStockKeeper) echo "  - Stock Keeper role name\n";
} else {
    echo "✗ Stock Keeper does NOT have required permissions\n";
    echo "Need to add stock_transfer.view or inventory.view permission\n";
}

