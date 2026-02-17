<?php
/**
 * Complete HR Menu Fix
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Staff;

$hrStaff = Staff::where('email', 'hr@mauzo.com')->first();
if (!$hrStaff) die("HR staff not found\n");

$owner = $hrStaff->owner;
$role = $hrStaff->role;

echo "HR Staff: {$hrStaff->full_name}\n";
echo "Role: " . ($role ? $role->name : 'None') . "\n\n";

// 1. Create HR menu
$hrMenu = MenuItem::where('slug', 'hr')->first();
if (!$hrMenu) {
    $hrMenu = MenuItem::create([
        'name' => 'HR',
        'slug' => 'hr',
        'icon' => 'fa-users',
        'route' => 'hr.dashboard',
        'sort_order' => 50,
        'is_active' => true,
    ]);
    echo "Created HR menu\n";
} else {
    echo "HR menu exists\n";
}

// 2. Create submenus
$subs = [
    ['name' => 'HR Dashboard', 'slug' => 'hr-dashboard', 'icon' => 'fa-dashboard', 'route' => 'hr.dashboard'],
    ['name' => 'Attendance', 'slug' => 'hr-attendance', 'icon' => 'fa-check-circle', 'route' => 'hr.attendance'],
    ['name' => 'Leaves', 'slug' => 'hr-leaves', 'icon' => 'fa-calendar', 'route' => 'hr.leaves'],
    ['name' => 'Payroll', 'slug' => 'hr-payroll', 'icon' => 'fa-money', 'route' => 'hr.payroll'],
    ['name' => 'Performance Reviews', 'slug' => 'hr-performance-reviews', 'icon' => 'fa-star', 'route' => 'hr.performance-reviews'],
];

foreach ($subs as $sub) {
    if (!MenuItem::where('slug', $sub['slug'])->exists()) {
        MenuItem::create([
            'name' => $sub['name'],
            'slug' => $sub['slug'],
            'icon' => $sub['icon'],
            'route' => $sub['route'],
            'parent_id' => $hrMenu->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        echo "Created {$sub['name']}\n";
    }
}

// 3. Attach to business types
$bts = BusinessType::all();
foreach ($bts as $bt) {
    if (!$hrMenu->businessTypes()->where('business_type_id', $bt->id)->exists()) {
        $hrMenu->businessTypes()->attach($bt->id, ['is_enabled' => true, 'sort_order' => 50]);
    }
}
echo "Attached to business types\n";

// 4. Attach HR permissions
if ($role) {
    $perms = Permission::where('module', 'hr')->get();
    if ($perms->count() > 0) {
        $role->permissions()->syncWithoutDetaching($perms->pluck('id'));
        echo "Attached {$perms->count()} HR permissions\n";
    }
}

echo "\nDone! Logout and login again to see HR menu.\n";

