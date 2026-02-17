<?php
/**
 * Check HR Permissions
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;

echo "========================================\n";
echo "Checking HR Permissions\n";
echo "========================================\n\n";

$hrPermissions = Permission::where('module', 'hr')->get();

if ($hrPermissions->count() === 0) {
    echo "❌ No HR permissions found!\n\n";
    echo "Creating HR permissions...\n";
    
    // Create HR permissions manually
    $modules = ['hr' => 'Human Resources'];
    $actions = [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ];

    foreach ($modules as $module => $moduleName) {
        foreach ($actions as $action => $actionName) {
            Permission::updateOrCreate(
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
            echo "✓ Created: {$actionName} {$moduleName}\n";
        }
    }
    
    echo "\n✅ HR permissions created successfully!\n";
} else {
    echo "✅ Found {$hrPermissions->count()} HR permissions:\n\n";
    foreach ($hrPermissions as $perm) {
        echo "  - {$perm->name} ({$perm->module}.{$perm->action})\n";
    }
}

echo "\n========================================\n";

