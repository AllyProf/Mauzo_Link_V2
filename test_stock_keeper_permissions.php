<?php

/**
 * Test Stock Keeper Role Permissions Save/Update
 * 
 * This script tests if permissions for Stock Keeper role are being saved correctly
 * when modified through the Business Configuration edit page.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

echo "=== Stock Keeper Permissions Test ===\n\n";

// Find the user (assuming first non-admin user)
$user = User::where('role', '!=', 'admin')->first();

if (!$user) {
    echo "ERROR: No user found!\n";
    exit(1);
}

echo "Testing for user: {$user->name} (ID: {$user->id})\n\n";

// Find Stock Keeper role
$stockKeeperRole = Role::where('user_id', $user->id)
    ->where('name', 'Stock Keeper')
    ->where('is_active', true)
    ->first();

if (!$stockKeeperRole) {
    echo "ERROR: Stock Keeper role not found!\n";
    exit(1);
}

echo "Found Stock Keeper role:\n";
echo "  Role ID: {$stockKeeperRole->id}\n";
echo "  Role Name: {$stockKeeperRole->name}\n";
echo "  Description: " . ($stockKeeperRole->description ?: '(none)') . "\n\n";

// Get current permissions
$currentPermissions = $stockKeeperRole->permissions()->pluck('permissions.id')->toArray();
echo "Current permissions in database (" . count($currentPermissions) . "):\n";
if (count($currentPermissions) > 0) {
    $perms = Permission::whereIn('id', $currentPermissions)->get();
    foreach ($perms as $perm) {
        echo "  - {$perm->module}: {$perm->action} (#{$perm->id})\n";
    }
} else {
    echo "  (none)\n";
}
echo "\n";

// Test 1: Simulate form submission with specific permissions
echo "=== TEST 1: Simulate Form Submission ===\n";
echo "Testing with permissions: products.view, inventory.view, stock_receipt.view\n\n";

// Get permission IDs for the test
$testPermissionIds = Permission::whereIn('module', ['products', 'inventory', 'stock_receipt'])
    ->where('action', 'view')
    ->pluck('id')
    ->toArray();

echo "Test permission IDs: " . implode(', ', $testPermissionIds) . "\n";

// Simulate the update process (same as controller)
DB::beginTransaction();
try {
    // Clear current permissions
    $stockKeeperRole->permissions()->detach();
    
    // Sync new permissions
    $stockKeeperRole->permissions()->sync($testPermissionIds);
    
    // Verify immediately
    $afterSync = DB::table('role_permissions')
        ->where('role_id', $stockKeeperRole->id)
        ->pluck('permission_id')
        ->toArray();
    
    sort($afterSync);
    sort($testPermissionIds);
    
    echo "Permissions after sync: " . implode(', ', $afterSync) . "\n";
    echo "Expected permissions: " . implode(', ', $testPermissionIds) . "\n";
    
    if ($afterSync === $testPermissionIds) {
        echo "✓ TEST PASSED: Permissions synced correctly!\n";
    } else {
        echo "✗ TEST FAILED: Permissions mismatch!\n";
        echo "  Expected count: " . count($testPermissionIds) . "\n";
        echo "  Actual count: " . count($afterSync) . "\n";
    }
    
    // Rollback to keep original state
    DB::rollBack();
    echo "\n(Transaction rolled back to preserve original permissions)\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "✗ TEST FAILED with exception: " . $e->getMessage() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 2: Test with empty permissions array
echo "=== TEST 2: Test Empty Permissions Array ===\n";
echo "Testing sync with empty array (should clear all permissions)\n\n";

DB::beginTransaction();
try {
    // First, add some permissions
    $somePerms = Permission::whereIn('module', ['products'])
        ->where('action', 'view')
        ->pluck('id')
        ->toArray();
    $stockKeeperRole->permissions()->sync($somePerms);
    
    echo "Added " . count($somePerms) . " permissions\n";
    
    // Now sync with empty array
    $stockKeeperRole->permissions()->sync([]);
    
    $afterEmpty = DB::table('role_permissions')
        ->where('role_id', $stockKeeperRole->id)
        ->pluck('permission_id')
        ->toArray();
    
    if (count($afterEmpty) === 0) {
        echo "✓ TEST PASSED: Empty array cleared all permissions correctly!\n";
    } else {
        echo "✗ TEST FAILED: Empty array did not clear permissions!\n";
        echo "  Remaining permissions: " . implode(', ', $afterEmpty) . "\n";
    }
    
    DB::rollBack();
    echo "\n(Transaction rolled back)\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "✗ TEST FAILED with exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test form data structure
echo "=== TEST 3: Test Form Data Structure ===\n";
echo "Simulating form submission data structure\n\n";

// Simulate what the form sends
$formData = [
    'roles' => [
        $stockKeeperRole->id => [
            'name' => 'Stock Keeper',
            'description' => 'Manage inventory and stock',
            'permissions' => [5, 21, 37] // Example permission IDs
        ]
    ]
];

echo "Form data structure:\n";
echo json_encode($formData, JSON_PRETTY_PRINT) . "\n\n";

// Process like controller does
$roleData = $formData['roles'][$stockKeeperRole->id];
$permissionIds = [];

if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
    $permissionIds = array_filter(array_map('intval', $roleData['permissions']), function($id) {
        return $id > 0;
    });
    $permissionIds = array_unique($permissionIds);
    $permissionIds = array_values($permissionIds);
}

echo "Processed permission IDs: " . implode(', ', $permissionIds) . "\n";

// Validate permissions exist
$validPermissionIds = Permission::whereIn('id', $permissionIds)
    ->where('is_active', true)
    ->pluck('id')
    ->toArray();

echo "Valid permission IDs: " . implode(', ', $validPermissionIds) . "\n";

if (count($validPermissionIds) === count($permissionIds)) {
    echo "✓ TEST PASSED: All permission IDs are valid!\n";
} else {
    echo "✗ TEST FAILED: Some permission IDs are invalid!\n";
    $invalid = array_diff($permissionIds, $validPermissionIds);
    echo "  Invalid IDs: " . implode(', ', $invalid) . "\n";
}

echo "\n";

// Test 4: Check actual current state
echo "=== TEST 4: Current Database State ===\n";
$finalPermissions = DB::table('role_permissions')
    ->where('role_id', $stockKeeperRole->id)
    ->pluck('permission_id')
    ->toArray();

echo "Current permissions in role_permissions table: " . (count($finalPermissions) > 0 ? implode(', ', $finalPermissions) : '(none)') . "\n";

$roleReloaded = Role::find($stockKeeperRole->id);
$roleReloaded->load('permissions');
$relationshipPermissions = $roleReloaded->permissions->pluck('id')->sort()->values()->toArray();

echo "Current permissions via relationship: " . (count($relationshipPermissions) > 0 ? implode(', ', $relationshipPermissions) : '(none)') . "\n";

if ($finalPermissions === $relationshipPermissions) {
    echo "✓ Database and relationship are in sync!\n";
} else {
    echo "✗ Database and relationship are OUT OF SYNC!\n";
    echo "  This could indicate a caching issue.\n";
}

echo "\n=== Test Complete ===\n";

