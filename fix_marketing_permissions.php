<?php
/**
 * Fix Marketing Role Permissions and Menu Access
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\MenuItem;
use App\Models\BusinessType;

$owner = User::where('email', 'admin@medalion.com')->first();

if (!$owner) {
    echo "❌ Owner not found\n";
    exit(1);
}

echo "========================================\n";
echo "Fix Marketing Role Permissions\n";
echo "========================================\n\n";

// Get Marketing role
$marketingRole = Role::where('user_id', $owner->id)
    ->where('name', 'Marketing')
    ->first();

if (!$marketingRole) {
    echo "❌ Marketing role not found\n";
    exit(1);
}

echo "✓ Found Marketing role: {$marketingRole->name} (ID: {$marketingRole->id})\n\n";

// Get all Marketing permissions
$marketingPermissions = Permission::where('module', 'marketing')->get();

if ($marketingPermissions->count() === 0) {
    echo "❌ No Marketing permissions found!\n";
    echo "   Run: php artisan db:seed --class=PermissionSeeder\n";
    exit(1);
}

echo "Found {$marketingPermissions->count()} Marketing permissions:\n";
foreach ($marketingPermissions as $perm) {
    echo "  - {$perm->name} ({$perm->module}.{$perm->action})\n";
}

// Attach all marketing permissions
$marketingRole->permissions()->sync($marketingPermissions->pluck('id'));
echo "\n✓ All Marketing permissions attached!\n\n";

// Also add some basic permissions for dashboard access
$basicPermissions = Permission::whereIn('module', ['reports', 'customers'])
    ->where('action', 'view')
    ->get();

if ($basicPermissions->count() > 0) {
    $marketingRole->permissions()->syncWithoutDetaching($basicPermissions->pluck('id'));
    echo "✓ Added basic view permissions (reports, customers)\n\n";
}

// Verify permissions
$rolePermissions = $marketingRole->permissions()->where('module', 'marketing')->get();
echo "Current Marketing permissions on role:\n";
foreach ($rolePermissions as $perm) {
    echo "  ✓ {$perm->action} ({$perm->module})\n";
}

echo "\n========================================\n";
echo "Permissions Fixed!\n";
echo "========================================\n";
echo "\nThe Marketing staff should now have access to:\n";
echo "  - Marketing Dashboard\n";
echo "  - Customer Database\n";
echo "  - Create Campaigns\n";
echo "  - Campaign History\n";
echo "  - Templates\n";
echo "\nPlease refresh the page or log out and log back in.\n";







