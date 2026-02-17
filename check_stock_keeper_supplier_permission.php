<?php
/**
 * Check Stock Keeper Supplier Permission
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;

$stockKeeper = Staff::where('email', 'stockkeeper@mauzo.com')->first();

if (!$stockKeeper) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

echo "========================================\n";
echo "Check Stock Keeper Supplier Permission\n";
echo "========================================\n\n";

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Email: {$stockKeeper->email}\n";
$role = $stockKeeper->role;
echo "Role: " . ($role ? $role->name : 'None') . "\n";
echo "Role ID: " . ($role ? $role->id : 'None') . "\n\n";

if ($role) {
    // Check supplier permissions
    $supplierPerms = Permission::where('module', 'suppliers')->get();
    echo "All Supplier Permissions:\n";
    foreach ($supplierPerms as $perm) {
        $has = $role->hasPermission($perm->module, $perm->action);
        echo "  - {$perm->name} ({$perm->module}.{$perm->action}): " . ($has ? "✓ HAS" : "✗ NO") . "\n";
    }
    
    // Get all role permissions
    echo "\nAll Role Permissions:\n";
    $allPerms = $role->permissions()->get();
    foreach ($allPerms as $perm) {
        echo "  - {$perm->module}.{$perm->action}\n";
    }
    
    // Check if role has suppliers.view
    $hasSuppliersView = $role->hasPermission('suppliers', 'view');
    echo "\nHas suppliers.view: " . ($hasSuppliersView ? "YES" : "NO") . "\n";
    
    // Check role_permissions table directly
    echo "\nDirect Database Check:\n";
    $rolePerms = DB::table('role_permissions')
        ->where('role_id', $role->id)
        ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
        ->where('permissions.module', 'suppliers')
        ->get();
    
    if ($rolePerms->count() > 0) {
        echo "  Found {$rolePerms->count()} supplier permission(s) in database:\n";
        foreach ($rolePerms as $rp) {
            echo "    - {$rp->module}.{$rp->action} (Permission ID: {$rp->permission_id})\n";
        }
    } else {
        echo "  No supplier permissions found in database\n";
    }
}

echo "\n========================================\n";
echo "Check Complete\n";
echo "========================================\n";

