<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$p = \App\Models\MenuItem::where('name', 'Products')->whereNull('parent_id')->first();
echo "Slug: " . ($p->slug ?? 'null') . "\n";
