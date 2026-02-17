<?php
/**
 * Fix Stock Keeper Permissions
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;

echo "========================================\n";
echo "Fix Stock Keeper Permissions\n";
echo "========================================\n\n";

// Get all Stock Keeper roles
$stockKeeperRoles = Role::where('name', 'like', '%Stock Keeper%')
    ->orWhere('slug', 'like', '%stock-keeper%')
    ->get();

if ($stockKeeperRoles->count() === 0) {
    echo "❌ No Stock Keeper roles found\n";
    exit(1);
}

// Get required permissions
$requiredPerms = Permission::whereIn('module', [
    'inventory',
    'stock_receipt',
    'stock_transfer',
    'products',
    'suppliers',
])->get();

echo "Found {$stockKeeperRoles->count()} Stock Keeper role(s)\n";
echo "Found {$requiredPerms->count()} required permissions\n\n";

foreach ($stockKeeperRoles as $role) {
    echo "Updating role: {$role->name} (Owner: {$role->owner->email})\n";
    
    // Attach all required permissions
    $role->permissions()->syncWithoutDetaching($requiredPerms->pluck('id'));
    
    echo "✓ Attached {$requiredPerms->count()} permissions\n";
    
    // Verify
    $hasInventory = $role->hasPermission('inventory', 'view');
    $hasStockReceipt = $role->hasPermission('stock_receipt', 'view');
    $hasStockTransfer = $role->hasPermission('stock_transfer', 'view');
    $hasProducts = $role->hasPermission('products', 'view');
    $hasSuppliers = $role->hasPermission('suppliers', 'view');
    
    echo "  - inventory.view: " . ($hasInventory ? "✓" : "✗") . "\n";
    echo "  - stock_receipt.view: " . ($hasStockReceipt ? "✓" : "✗") . "\n";
    echo "  - stock_transfer.view: " . ($hasStockTransfer ? "✓" : "✗") . "\n";
    echo "  - products.view: " . ($hasProducts ? "✓" : "✗") . "\n";
    echo "  - suppliers.view: " . ($hasSuppliers ? "✓" : "✗") . "\n";
    echo "\n";
}

echo "========================================\n";
echo "Fix Complete!\n";
echo "========================================\n";
echo "Stock Keeper should now see:\n";
echo "  - Products (with all submenus)\n";
echo "  - Bar Management (Stock Receipts, Stock Transfers, Suppliers)\n";
echo "  - Inventory menus\n\n";
echo "Please logout and login again as Stock Keeper to see the menus.\n";

