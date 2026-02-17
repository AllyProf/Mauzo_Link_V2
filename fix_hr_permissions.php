<?php
/**
 * Fix HR Permissions - Creates HR permissions if they don't exist
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;

print "========================================\n";
print "Creating HR Permissions\n";
print "========================================\n\n";

// Check existing
$existing = Permission::where('module', 'hr')->count();
print "Current HR permissions: {$existing}\n\n";

if ($existing == 0) {
    print "Creating HR permissions...\n";
    
    $permissions = [
        ['module' => 'hr', 'action' => 'view', 'name' => 'View Human Resources', 'description' => 'Permission to view human resources'],
        ['module' => 'hr', 'action' => 'create', 'name' => 'Create Human Resources', 'description' => 'Permission to create human resources'],
        ['module' => 'hr', 'action' => 'edit', 'name' => 'Edit Human Resources', 'description' => 'Permission to edit human resources'],
        ['module' => 'hr', 'action' => 'delete', 'name' => 'Delete Human Resources', 'description' => 'Permission to delete human resources'],
    ];
    
    foreach ($permissions as $perm) {
        Permission::updateOrCreate(
            [
                'module' => $perm['module'],
                'action' => $perm['action'],
            ],
            [
                'name' => $perm['name'],
                'description' => $perm['description'],
                'is_active' => true,
            ]
        );
        print "✓ Created: {$perm['name']}\n";
    }
    
    print "\n✅ Successfully created 4 HR permissions!\n";
} else {
    print "✅ HR permissions already exist!\n";
}

// Verify
$count = Permission::where('module', 'hr')->count();
print "\nTotal HR permissions: {$count}\n";

print "\n========================================\n";
print "Done!\n";
print "========================================\n";

