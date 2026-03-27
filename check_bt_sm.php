<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$sm = \App\Models\MenuItem::where('slug', 'bar-stock-mgmt')->first();
print_r($sm->businessTypes->pluck('name', 'slug')->toArray());
