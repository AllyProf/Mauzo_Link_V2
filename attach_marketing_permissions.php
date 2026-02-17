<?php
/**
 * Attach Marketing Permissions to Marketing Role
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

// Try to find owner by email, or use first available owner
$ownerEmail = $argv[1] ?? 'admin@mauzolink.com';
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

$marketingRole = Role::where('user_id', $owner->id)
    ->where(function($q) {
        $q->where('name', 'Marketing')
          ->orWhere('name', 'like', '%Marketing%')
          ->orWhere('slug', 'like', '%marketing%');
    })
    ->first();

if (!$marketingRole) {
    echo "⚠️  Marketing role not found. Creating it...\n";
    $marketingRole = Role::create([
        'user_id' => $owner->id,
        'name' => 'Marketing',
        'slug' => 'marketing',
        'description' => 'Marketing staff with access to campaigns and customer database',
    ]);
    echo "✓ Marketing role created!\n\n";
}

echo "✓ Found Marketing role: {$marketingRole->name}\n\n";

// Get all Marketing permissions
$permissions = Permission::where('module', 'marketing')->get();

if ($permissions->count() === 0) {
    echo "❌ No Marketing permissions found. Run: php artisan db:seed --class=PermissionSeeder\n";
    exit(1);
}

echo "Found {$permissions->count()} Marketing permissions:\n";
foreach ($permissions as $perm) {
    echo "  - {$perm->name} ({$perm->module}.{$perm->action})\n";
}

// Attach permissions
$marketingRole->permissions()->sync($permissions->pluck('id'));

echo "\n✓ All Marketing permissions attached to Marketing role!\n";
echo "\nMarketing staff can now access:\n";
echo "  - Marketing Dashboard\n";
echo "  - Customer Database\n";
echo "  - Create Campaigns\n";
echo "  - View Campaign History\n";
echo "  - Manage Templates\n";






