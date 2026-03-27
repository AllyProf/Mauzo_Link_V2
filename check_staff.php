<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$staff = \App\Models\Staff::where('full_name', 'like', '%Counter Staff%')->with('role')->first();
echo "Role Name: " . ($staff->role->name ?? 'null') . "\n";
echo "Role Slug: " . ($staff->role->slug ?? 'null') . "\n";
