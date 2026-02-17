<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Permission;

$perms = [
    ['module' => 'hr', 'action' => 'view'],
    ['module' => 'hr', 'action' => 'create'],
    ['module' => 'hr', 'action' => 'edit'],
    ['module' => 'hr', 'action' => 'delete'],
];

foreach ($perms as $p) {
    Permission::updateOrCreate(
        ['module' => $p['module'], 'action' => $p['action']],
        [
            'name' => ucfirst($p['action']) . ' Human Resources',
            'description' => 'Permission to ' . $p['action'] . ' human resources',
            'is_active' => true,
        ]
    );
}

echo "HR permissions created!\n";

