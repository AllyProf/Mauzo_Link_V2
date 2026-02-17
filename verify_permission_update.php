<?php
/**
 * Verify Permission Update Process
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Verify Permission Update Process\n";
echo "========================================\n\n";

$stockKeeper = Staff::where('email', 'stockkeeper@mauzo.com')->first();

if (!$stockKeeper) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

$role = $stockKeeper->role;
$owner = $stockKeeper->owner;

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Role: {$role->name} (ID: {$role->id})\n";
echo "Owner: {$owner->email}\n\n";

// Check current supplier permissions
echo "Current Supplier Permissions in Database:\n";
$supplierPerms = DB::table('role_permissions')
    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
    ->where('role_permissions.role_id', $role->id)
    ->where('permissions.module', 'suppliers')
    ->get();

if ($supplierPerms->count() > 0) {
    foreach ($supplierPerms as $perm) {
        echo "  - {$perm->module}.{$perm->action} (Permission ID: {$perm->permission_id})\n";
    }
    echo "\n⚠️  Stock Keeper still has supplier permissions!\n";
    echo "These need to be removed from the business configuration edit page.\n\n";
    
    echo "To fix this:\n";
    echo "1. Go to: http://192.168.1.101:8000/business-configuration/edit\n";
    echo "2. Find the 'Stock Keeper' role\n";
    echo "3. Uncheck all 'Suppliers' permissions (View, Create, Edit, Delete)\n";
    echo "4. Click 'Save' or 'Update'\n";
    echo "5. Stock Keeper needs to logout and login again\n\n";
    
    echo "Or, I can remove them now. Should I proceed? (This will remove supplier permissions)\n";
} else {
    echo "✓ No supplier permissions found - Good!\n";
}

// Show all permissions
echo "\nAll Role Permissions:\n";
$allPerms = DB::table('role_permissions')
    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
    ->where('role_permissions.role_id', $role->id)
    ->orderBy('permissions.module')
    ->orderBy('permissions.action')
    ->get();

foreach ($allPerms as $perm) {
    echo "  - {$perm->module}.{$perm->action}\n";
}

echo "\n========================================\n";
echo "Verification Complete\n";
echo "========================================\n";

