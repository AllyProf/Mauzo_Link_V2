<?php
/**
 * Attach HR Permissions to a Role
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

// Try to find owner by email, or use first available owner
$ownerEmail = $argv[1] ?? 'admin@mauzolink.com';
$roleName = $argv[2] ?? 'Manager';

$owner = User::where('email', $ownerEmail)->first();

if (!$owner) {
    // Try first available owner
    $owner = User::first();
    if ($owner) {
        echo "⚠️  Owner with email '{$ownerEmail}' not found. Using first available owner:\n";
        echo "  Email: {$owner->email}\n";
        echo "  Name: {$owner->name}\n\n";
    }
}

if (!$owner) {
    echo "❌ No owner found in the system.\n";
    exit(1);
}

$role = Role::where('user_id', $owner->id)
    ->where(function($q) use ($roleName) {
        $q->where('name', $roleName)
          ->orWhere('name', 'like', '%' . $roleName . '%')
          ->orWhere('slug', 'like', '%' . strtolower($roleName) . '%');
    })
    ->first();

if (!$role) {
    echo "⚠️  Role '{$roleName}' not found. Available roles:\n";
    $availableRoles = Role::where('user_id', $owner->id)->get(['id', 'name', 'slug']);
    foreach ($availableRoles as $r) {
        echo "  - {$r->name} ({$r->slug})\n";
    }
    echo "\nPlease specify a valid role name as the second argument.\n";
    exit(1);
}

echo "✓ Found role: {$role->name}\n\n";

// Get all HR permissions
$permissions = Permission::where('module', 'hr')->get();

if ($permissions->count() === 0) {
    echo "❌ No HR permissions found. Run: php artisan db:seed --class=PermissionSeeder\n";
    exit(1);
}

echo "Found {$permissions->count()} HR permissions:\n";
foreach ($permissions as $perm) {
    echo "  - {$perm->name} ({$perm->module}.{$perm->action})\n";
}

// Attach permissions
$role->permissions()->syncWithoutDetaching($permissions->pluck('id'));

echo "\n✓ All HR permissions attached to {$role->name} role!\n";
echo "\nThe role can now access:\n";
echo "  - HR Dashboard\n";
echo "  - Attendance Management\n";
echo "  - Leave Management\n";
echo "  - Payroll Management\n";
echo "  - Performance Reviews\n";

