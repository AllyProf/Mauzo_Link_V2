<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;

echo "=== Testing Stock Keeper Settings Permission ===\n\n";

// Find Stock Keeper staff member (assuming staff ID 2 based on the error)
$staff = Staff::find(2);

if (!$staff) {
    echo "ERROR: Staff member not found!\n";
    exit(1);
}

echo "Staff: {$staff->full_name} (ID: {$staff->id})\n";
echo "Email: {$staff->email}\n\n";

// Get the role
$role = $staff->role;

if (!$role) {
    echo "ERROR: Staff has no role assigned!\n";
    exit(1);
}

echo "Role: {$role->name} (ID: {$role->id})\n\n";

// Check for settings.edit permission
$hasSettingsEdit = $role->hasPermission('settings', 'edit');

echo "Has 'settings.edit' permission: " . ($hasSettingsEdit ? 'YES ✓' : 'NO ✗') . "\n\n";

// List all permissions for this role
$permissions = $role->permissions;
echo "All permissions for this role (" . $permissions->count() . "):\n";
foreach ($permissions as $perm) {
    $isSettingsEdit = ($perm->module === 'settings' && $perm->action === 'edit');
    $marker = $isSettingsEdit ? ' <-- THIS ONE' : '';
    echo "  - {$perm->module}: {$perm->action} (#{$perm->id}){$marker}\n";
}

echo "\n=== Test Complete ===\n";








