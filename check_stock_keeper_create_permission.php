<?php
/**
 * Check Stock Keeper Create Permission
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
echo "Check Stock Keeper Create Permission\n";
echo "========================================\n\n";

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Role: {$role->name}\n\n";

// Check permissions
$hasStockTransferCreate = $role->hasPermission('stock_transfer', 'create');
$hasInventoryEdit = $role->hasPermission('inventory', 'edit');

echo "Permissions:\n";
echo "  stock_transfer.create: " . ($hasStockTransferCreate ? "✓ YES" : "✗ NO") . "\n";
echo "  inventory.edit: " . ($hasInventoryEdit ? "✓ YES" : "✗ NO") . "\n\n";

// Check role name
$roleName = strtolower(trim($role->name ?? ''));
$isStockKeeper = in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper']);

echo "Role check:\n";
echo "  Role name: '{$roleName}'\n";
echo "  Matches allowed roles: " . ($isStockKeeper ? "✓ YES" : "✗ NO") . "\n\n";

echo "========================================\n";
echo "Analysis\n";
echo "========================================\n";

$canCreate = $hasStockTransferCreate || $hasInventoryEdit || $isStockKeeper;

if ($canCreate) {
    echo "✓ Stock Keeper SHOULD have access to create stock transfers\n";
    echo "Based on:\n";
    if ($hasStockTransferCreate) echo "  - stock_transfer.create permission\n";
    if ($hasInventoryEdit) echo "  - inventory.edit permission\n";
    if ($isStockKeeper) echo "  - Stock Keeper role name\n";
} else {
    echo "✗ Stock Keeper does NOT have access\n";
    echo "Need to add stock_transfer.create or inventory.edit permission\n";
}

