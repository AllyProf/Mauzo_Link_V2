<?php
/**
 * Create HR Permissions
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;

echo "Creating HR Permissions...\n\n";

$modules = ['hr' => 'Human Resources'];
$actions = [
    'view' => 'View',
    'create' => 'Create',
    'edit' => 'Edit',
    'delete' => 'Delete',
];

$created = 0;
$updated = 0;

foreach ($modules as $module => $moduleName) {
    foreach ($actions as $action => $actionName) {
        $permission = Permission::updateOrCreate(
            [
                'module' => $module,
                'action' => $action,
            ],
            [
                'name' => $actionName . ' ' . $moduleName,
                'description' => 'Permission to ' . strtolower($actionName) . ' ' . strtolower($moduleName),
                'is_active' => true,
            ]
        );
        
        if ($permission->wasRecentlyCreated) {
            echo "✓ Created: {$actionName} {$moduleName} ({$module}.{$action})\n";
            $created++;
        } else {
            echo "✓ Updated: {$actionName} {$moduleName} ({$module}.{$action})\n";
            $updated++;
        }
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  Created: {$created} permissions\n";
echo "  Updated: {$updated} permissions\n";
echo "========================================\n\n";

// Verify
$hrPermissions = Permission::where('module', 'hr')->get();
echo "Total HR permissions in database: {$hrPermissions->count()}\n\n";

if ($hrPermissions->count() > 0) {
    echo "HR Permissions List:\n";
    foreach ($hrPermissions as $perm) {
        echo "  - {$perm->name} ({$perm->module}.{$perm->action})\n";
    }
}

