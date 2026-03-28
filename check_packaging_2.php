<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ProductVariant;

$variants = ProductVariant::whereIn('id', [103, 104])->get(['id', 'name', 'packaging', 'items_per_package']);

foreach ($variants as $v) {
    echo "ID: {$v->id} | Name: {$v->name} | Pkg: {$v->packaging} | Items/Pkg: {$v->items_per_package}\n";
}
