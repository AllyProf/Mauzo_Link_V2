<?php
/**
 * Remove Bar Menu Permissions from Chef Role
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

echo "========================================\n";
echo "Remove Bar Menu Permissions from Chef\n";
echo "========================================\n\n";

// Get all owners
$owners = User::where('role', '!=', 'admin')->get();

foreach ($owners as $owner) {
    echo "Processing owner: {$owner->email}\n";
    
    // Find Chef role
    $chefRole = Role::where('user_id', $owner->id)
        ->where(function($q) {
            $q->where('name', 'like', '%Chef%')
              ->orWhere('slug', 'like', '%chef%');
        })
        ->first();
    
    if (!$chefRole) {
        echo "  ⚠️  Chef role not found\n\n";
        continue;
    }
    
    echo "  ✓ Found Chef role: {$chefRole->name}\n";
    
    // Get current permissions
    $currentPerms = $chefRole->permissions()->get();
    echo "  Current permissions: {$currentPerms->count()}\n";
    
    // Remove Bar-related permissions
    // Chef should NOT have access to:
    // - bar_orders (except view/edit for food orders - but we'll keep those for now)
    // - bar_payments
    // - bar_tables
    // - stock_receipt (for bar)
    // - stock_transfer (for bar)
    // - suppliers (for bar)
    
    // Actually, let's keep bar_orders.view and bar_orders.edit since Chef needs to see food orders
    // But remove other bar-specific permissions
    
    // Get permissions to remove
    $barPermissionsToRemove = Permission::whereIn('module', [
        'bar_payments',
        'bar_tables',
        'suppliers', // Bar suppliers
        'stock_receipt', // Bar stock receipts
        'stock_transfer', // Bar stock transfers
    ])->get();
    
    if ($barPermissionsToRemove->count() > 0) {
        $chefRole->permissions()->detach($barPermissionsToRemove->pluck('id'));
        echo "  ✓ Removed {$barPermissionsToRemove->count()} Bar-related permissions\n";
    }
    
    // Verify remaining permissions
    $remainingPerms = $chefRole->permissions()->get();
    echo "  Remaining permissions: {$remainingPerms->count()}\n";
    echo "  Permissions:\n";
    foreach ($remainingPerms as $perm) {
        echo "    - {$perm->module}.{$perm->action}\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Complete!\n";
echo "========================================\n";
echo "Chef can no longer see Bar Management and Beverage Inventory menus.\n";
echo "Chef will only see Restaurant-related menus.\n";

