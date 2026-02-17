<?php

/**
 * Test Form Submission Data Structure
 * 
 * This script simulates what happens when the business configuration edit form is submitted
 * and checks if the data structure matches what the controller expects.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

echo "=== Form Submission Data Structure Test ===\n\n";

// Find the user
$user = User::where('role', '!=', 'admin')->first();
if (!$user) {
    echo "ERROR: No user found!\n";
    exit(1);
}

// Find Stock Keeper role
$stockKeeperRole = Role::where('user_id', $user->id)
    ->where('name', 'Stock Keeper')
    ->where('is_active', true)
    ->first();

if (!$stockKeeperRole) {
    echo "ERROR: Stock Keeper role not found!\n";
    exit(1);
}

echo "Testing with Stock Keeper role (ID: {$stockKeeperRole->id})\n\n";

// Get all permissions
$allPermissions = Permission::where('is_active', true)->get()->groupBy('module');

// Simulate what the form SHOULD send when user checks/unchecks permissions
echo "=== Simulating Form Submission ===\n\n";

// Scenario 1: User modifies Stock Keeper permissions
// Let's say user wants to add "bar_orders.view" and remove "stock_transfer.delete"

$currentPerms = $stockKeeperRole->permissions()->pluck('permissions.id')->toArray();
echo "Current permissions: " . implode(', ', $currentPerms) . "\n";

// Get bar_orders.view permission
$barOrdersView = Permission::where('module', 'bar_orders')->where('action', 'view')->first();
$stockTransferDelete = Permission::where('module', 'stock_transfer')->where('action', 'delete')->first();

if (!$barOrdersView) {
    echo "ERROR: bar_orders.view permission not found!\n";
    exit(1);
}

// New permissions: keep all current, add bar_orders.view, remove stock_transfer.delete
$newPerms = $currentPerms;
if ($stockTransferDelete && in_array($stockTransferDelete->id, $newPerms)) {
    $newPerms = array_diff($newPerms, [$stockTransferDelete->id]);
}
if (!in_array($barOrdersView->id, $newPerms)) {
    $newPerms[] = $barOrdersView->id;
}
$newPerms = array_values($newPerms);

echo "New permissions (after modification): " . implode(', ', $newPerms) . "\n\n";

// Simulate form data structure
$formData = [
    'roles' => [
        (string)$stockKeeperRole->id => [
            'name' => 'Stock Keeper',
            'description' => '',
            'permissions' => $newPerms
        ]
    ]
];

echo "Form data that would be submitted:\n";
echo json_encode($formData, JSON_PRETTY_PRINT) . "\n\n";

// Process like controller does
echo "=== Processing Like Controller ===\n\n";

$validated = $formData; // In real scenario, this would go through validation

foreach ($validated['roles'] as $roleKey => $roleData) {
    echo "Processing role key: {$roleKey}\n";
    echo "  Name: {$roleData['name']}\n";
    echo "  Has permissions key: " . (isset($roleData['permissions']) ? 'yes' : 'no') . "\n";
    
    if (isset($roleData['permissions'])) {
        echo "  Permissions type: " . gettype($roleData['permissions']) . "\n";
        echo "  Permissions count: " . (is_array($roleData['permissions']) ? count($roleData['permissions']) : 0) . "\n";
        echo "  Permissions: " . (is_array($roleData['permissions']) ? implode(', ', $roleData['permissions']) : 'not array') . "\n";
        
        // Process like controller
        $permissionIds = [];
        if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
            $permissionIds = array_filter(array_map('intval', $roleData['permissions']), function($id) {
                return $id > 0;
            });
            $permissionIds = array_unique($permissionIds);
            $permissionIds = array_values($permissionIds);
        }
        
        echo "  Processed permission IDs: " . implode(', ', $permissionIds) . "\n";
        
        // Validate
        $validPermissionIds = Permission::whereIn('id', $permissionIds)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
        
        echo "  Valid permission IDs: " . implode(', ', $validPermissionIds) . "\n";
        
        if (count($validPermissionIds) === count($permissionIds)) {
            echo "  ✓ All permissions are valid!\n";
        } else {
            echo "  ✗ Some permissions are invalid!\n";
        }
    }
}

echo "\n=== Testing Actual Update ===\n\n";

// Now actually test the update
DB::beginTransaction();
try {
    echo "Updating Stock Keeper role with new permissions...\n";
    
    // This is what the controller does
    $role = Role::find($stockKeeperRole->id);
    
    $permissionIds = [];
    if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
        $permissionIds = array_filter(array_map('intval', $roleData['permissions']), function($id) {
            return $id > 0;
        });
        $permissionIds = array_unique($permissionIds);
        $permissionIds = array_values($permissionIds);
    }
    
    $validPermissionIds = Permission::whereIn('id', $permissionIds)
        ->where('is_active', true)
        ->pluck('id')
        ->toArray();
    
    echo "Syncing " . count($validPermissionIds) . " permissions...\n";
    $role->permissions()->sync($validPermissionIds);
    
    // Verify
    $afterSync = DB::table('role_permissions')
        ->where('role_id', $role->id)
        ->pluck('permission_id')
        ->toArray();
    
    sort($afterSync);
    sort($validPermissionIds);
    
    echo "Permissions after sync: " . implode(', ', $afterSync) . "\n";
    echo "Expected permissions: " . implode(', ', $validPermissionIds) . "\n";
    
    if ($afterSync === $validPermissionIds) {
        echo "✓ UPDATE SUCCESSFUL: Permissions saved correctly!\n";
    } else {
        echo "✗ UPDATE FAILED: Permissions mismatch!\n";
    }
    
    DB::rollBack();
    echo "\n(Transaction rolled back to preserve original state)\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "✗ UPDATE FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";








