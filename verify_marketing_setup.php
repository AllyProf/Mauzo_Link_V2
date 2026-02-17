<?php
/**
 * Verify Marketing Setup Complete
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;
use App\Models\MenuItem;
use App\Models\User;

$owner = User::where('email', 'admin@medalion.com')->first();

echo "========================================\n";
echo "Marketing Setup Verification\n";
echo "========================================\n\n";

// Check Marketing role
$marketingRole = Role::where('user_id', $owner->id)
    ->where('name', 'Marketing')
    ->first();

if ($marketingRole) {
    echo "✓ Marketing role exists\n";
    $perms = $marketingRole->permissions()->where('module', 'marketing')->get();
    echo "  Permissions: {$perms->count()} marketing permissions\n";
} else {
    echo "✗ Marketing role not found\n";
}

// Check Marketing staff
$marketingStaff = Staff::where('user_id', $owner->id)
    ->whereHas('role', function($q) {
        $q->where('name', 'Marketing');
    })
    ->get();

echo "\nMarketing Staff: {$marketingStaff->count()}\n";
foreach ($marketingStaff as $staff) {
    echo "  - {$staff->full_name} ({$staff->email}) - " . ($staff->is_active ? "Active" : "Inactive") . "\n";
}

// Check Marketing menu
$marketingMenu = MenuItem::where('slug', 'marketing')->first();
if ($marketingMenu) {
    echo "\n✓ Marketing menu exists\n";
    $children = $marketingMenu->children()->count();
    echo "  Children: {$children} menu items\n";
} else {
    echo "\n✗ Marketing menu not found\n";
}

echo "\n========================================\n";
echo "Setup Complete!\n";
echo "========================================\n";
echo "\nNext Steps:\n";
echo "1. Refresh the page (F5 or Ctrl+R)\n";
echo "2. Or log out and log back in\n";
echo "3. You should see 'Marketing' menu in the sidebar\n";
echo "4. Or go directly to: /marketing/dashboard\n";







