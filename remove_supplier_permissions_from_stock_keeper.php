<?php
/**
 * Remove Supplier Permissions from Stock Keeper Role
 * This demonstrates how the business configuration update should work
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Remove Supplier Permissions from Stock Keeper\n";
echo "========================================\n\n";

// Get all Stock Keeper roles
$stockKeeperRoles = Role::where('name', 'like', '%Stock Keeper%')
    ->orWhere('slug', 'like', '%stock-keeper%')
    ->get();

if ($stockKeeperRoles->count() === 0) {
    echo "❌ No Stock Keeper roles found\n";
    exit(1);
}

foreach ($stockKeeperRoles as $role) {
    echo "Processing role: {$role->name} (Owner: {$role->owner->email})\n";
    
    // Get current permissions
    $currentPerms = $role->permissions()->get();
    $currentPermIds = $currentPerms->pluck('id')->toArray();
    echo "  Current permissions count: " . count($currentPermIds) . "\n";
    
    // Get supplier permission IDs
    $supplierPermIds = Permission::where('module', 'suppliers')
        ->pluck('id')
        ->toArray();
    
    echo "  Supplier permission IDs to remove: " . implode(', ', $supplierPermIds) . "\n";
    
    // Remove supplier permissions
    $newPerms = array_diff($currentPermIds, $supplierPermIds);
    
    echo "  New permissions count: " . count($newPerms) . "\n";
    
    // Sync permissions (this is what the update method does)
    $role->permissions()->sync($newPerms);
    
    // Verify
    $role->refresh();
    $role->load('permissions');
    
    $hasSuppliers = $role->hasPermission('suppliers', 'view');
    echo "  Has suppliers.view: " . ($hasSuppliers ? "YES (❌)" : "NO (✓)") . "\n";
    
    // Show remaining permissions
    $remainingPerms = $role->permissions()->get();
    echo "  Remaining permissions:\n";
    foreach ($remainingPerms as $perm) {
        echo "    - {$perm->module}.{$perm->action}\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Complete!\n";
echo "========================================\n";
echo "Supplier permissions have been removed from Stock Keeper roles.\n";
echo "Stock Keeper staff members need to logout and login again to see the changes.\n";

