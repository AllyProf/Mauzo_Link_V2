<?php

// Simple script to inspect roles and permissions for a given owner user
//
// Usage (from project root):
//   php test_role_permissions.php            -> uses first non-admin user
//   php test_role_permissions.php 5          -> uses user with ID=5
//   php test_role_permissions.php email you@example.com  -> uses user by email
//
// This helps verify whether permissions you assign in
// `business-configuration/edit` are actually saved in the database.

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MauzoLinkV2 Role & Permission Inspector ===\n\n";

// Resolve target user
$argv = $_SERVER['argv'] ?? [];
$user = null;

if (isset($argv[1]) && $argv[1] === 'email' && isset($argv[2])) {
    $email = $argv[2];
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "User with email '{$email}' not found.\n";
        exit(1);
    }
} elseif (isset($argv[1]) && ctype_digit($argv[1])) {
    $userId = (int) $argv[1];
    $user = User::find($userId);
    if (!$user) {
        echo "User with ID {$userId} not found.\n";
        exit(1);
    }
} else {
    // Default: pick first non-admin user
    $user = User::where('role', '!=', 'admin')->orderBy('id')->first();
    if (!$user) {
        echo "No non-admin users found. Please create a business owner account first.\n";
        exit(1);
    }
}

echo "Inspecting roles for user:\n";
echo "  ID:    {$user->id}\n";
echo "  Name:  {$user->name}\n";
echo "  Email: {$user->email}\n\n";

// Load all owned roles with permissions
$roles = $user->ownedRoles()->with('permissions')->orderBy('id')->get();

if ($roles->isEmpty()) {
    echo "This user has no owned roles.\n";
    exit(0);
}

echo "Found " . $roles->count() . " owned role(s):\n\n";

foreach ($roles as $role) {
    echo "------------------------------------------------------------\n";
    echo "Role ID:   {$role->id}\n";
    echo "Role Name: {$role->name}\n";
    echo "Desc:      " . ($role->description ?: '(none)') . "\n";
    echo "Active:    " . ($role->is_active ? 'yes' : 'no') . "\n";

    $perms = $role->permissions;

    if ($perms->isEmpty()) {
        echo "Permissions: (none)\n";
    } else {
        echo "Permissions (" . $perms->count() . "):\n";
        // Group by module for easier reading
        $grouped = $perms->groupBy('module');
        foreach ($grouped as $module => $modulePerms) {
            $actions = $modulePerms->map(function (Permission $p) {
                return $p->action . " (#{$p->id})";
            })->implode(', ');
            echo "  - {$module}: {$actions}\n";
        }
    }

    echo "\n";
}

echo "=== End of report ===\n";









