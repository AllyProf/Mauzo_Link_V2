<?php
/**
 * Update Chef Role Permissions
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

echo "========================================\n";
echo "Update Chef Role Permissions\n";
echo "========================================\n\n";

$ownerEmail = $argv[1] ?? 'admin@medalion.com';
$owner = User::where('email', $ownerEmail)->first();

if (!$owner) {
    echo "❌ Owner not found with email: {$ownerEmail}\n";
    exit(1);
}

echo "✓ Found owner: {$owner->name} (ID: {$owner->id})\n\n";

// Find Chef role
$chefRole = Role::where('user_id', $owner->id)
    ->where(function($q) {
        $q->where('name', 'like', '%Chef%')
          ->orWhere('name', 'like', '%chef%')
          ->orWhere('slug', 'like', '%chef%');
    })
    ->first();

if (!$chefRole) {
    echo "❌ Chef role not found.\n";
    exit(1);
}

echo "✓ Found Chef role: {$chefRole->name} (ID: {$chefRole->id})\n\n";

// Get required permissions
$requiredPermissions = [
    // Bar Orders (for viewing dashboard and managing orders)
    ['module' => 'bar_orders', 'action' => 'view'],
    ['module' => 'bar_orders', 'action' => 'edit'],
    // Products (for managing food items)
    ['module' => 'products', 'action' => 'view'],
    ['module' => 'products', 'action' => 'create'],
    ['module' => 'products', 'action' => 'edit'],
    ['module' => 'products', 'action' => 'delete'],
    // Inventory (for managing ingredients)
    ['module' => 'inventory', 'action' => 'view'],
    ['module' => 'inventory', 'action' => 'create'],
    ['module' => 'inventory', 'action' => 'edit'],
    ['module' => 'inventory', 'action' => 'delete'],
];

echo "Adding permissions to Chef role...\n";

$addedCount = 0;
foreach ($requiredPermissions as $perm) {
    $permission = Permission::where('module', $perm['module'])
        ->where('action', $perm['action'])
        ->first();
    
    if ($permission) {
        $exists = $chefRole->permissions()->where('permissions.id', $permission->id)->exists();
        if (!$exists) {
            $chefRole->permissions()->attach($permission->id);
            echo "  ✓ Added: {$perm['module']}.{$perm['action']}\n";
            $addedCount++;
        } else {
            echo "  - Already has: {$perm['module']}.{$perm['action']}\n";
        }
    } else {
        echo "  ⚠️  Permission not found: {$perm['module']}.{$perm['action']}\n";
    }
}

echo "\n✓ Updated Chef role permissions!\n";
echo "  Added {$addedCount} new permission(s)\n\n";

// Verify
echo "Current Chef Role Permissions:\n";
echo str_repeat("-", 60) . "\n";
$permissions = $chefRole->permissions()->get();
$grouped = $permissions->groupBy('module');
foreach ($grouped as $module => $perms) {
    echo "  {$module}:\n";
    foreach ($perms as $perm) {
        echo "    - {$perm->action}\n";
    }
}
echo str_repeat("-", 60) . "\n\n";

echo "========================================\n";
echo "Done! Chef role now has all required permissions.\n";
echo "========================================\n";





