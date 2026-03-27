<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$perms = \App\Models\Permission::whereIn('module', ['inventory', 'suppliers'])->get();
$roles = \App\Models\Role::where('slug', 'counter')->get();

foreach ($roles as $role) {
    $role->permissions()->syncWithoutDetaching($perms->pluck('id'));
}
echo "Updated Counter permissions successfully.\n";
