<?php
/**
 * Add Supplier Permissions to Stock Keeper Role
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\Permission;

echo "========================================\n";
echo "Add Supplier Permissions to Stock Keeper\n";
echo "========================================\n\n";

// Get all Stock Keeper roles
$stockKeeperRoles = Role::where('name', 'like', '%Stock Keeper%')
    ->orWhere('slug', 'like', '%stock-keeper%')
    ->get();

if ($stockKeeperRoles->count() === 0) {
    echo "❌ No Stock Keeper roles found\n";
    exit(1);
}

// Get supplier permissions
$supplierPerms = Permission::where('module', 'suppliers')->get();

if ($supplierPerms->count() === 0) {
    echo "❌ No supplier permissions found\n";
    exit(1);
}

echo "Found {$stockKeeperRoles->count()} Stock Keeper role(s)\n";
echo "Found {$supplierPerms->count()} supplier permission(s)\n\n";

foreach ($stockKeeperRoles as $role) {
    echo "Processing role: {$role->name} (Owner: {$role->owner->email})\n";
    
    // Get current permissions
    $currentPerms = $role->permissions()->get();
    $currentPermIds = $currentPerms->pluck('id')->toArray();
    echo "  Current permissions: " . count($currentPermIds) . "\n";
    
    // Add supplier permissions
    $supplierPermIds = $supplierPerms->pluck('id')->toArray();
    $newPermIds = array_unique(array_merge($currentPermIds, $supplierPermIds));
    
    echo "  Adding supplier permissions: " . implode(', ', $supplierPermIds) . "\n";
    echo "  New total permissions: " . count($newPermIds) . "\n";
    
    // Sync permissions
    $role->permissions()->sync($newPermIds);
    
    // Verify
    $role->refresh();
    $role->load('permissions');
    
    $hasSuppliersView = $role->hasPermission('suppliers', 'view');
    echo "  Has suppliers.view: " . ($hasSuppliersView ? "✓ YES" : "✗ NO") . "\n";
    
    // Show supplier permissions
    $supplierPermsForRole = $role->permissions()->get()->filter(function($perm) {
        return $perm->module === 'suppliers';
    });
    echo "  Supplier permissions added:\n";
    foreach ($supplierPermsForRole as $perm) {
        echo "    - {$perm->module}.{$perm->action}\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Complete!\n";
echo "========================================\n";
echo "Supplier permissions have been added to Stock Keeper roles.\n";
echo "\nIMPORTANT: Stock Keeper staff members need to:\n";
echo "1. Logout completely from their account\n";
echo "2. Login again\n";
echo "3. Clear browser cache (Ctrl+F5) if needed\n";
echo "\nAfter logging in again, they should see the Suppliers menu in Bar Management.\n";

