<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\StockMovement;
use App\Models\ProductVariant;

$variant = ProductVariant::where('name', 'like', '%Serengeti Apple%')->first();
if (!$variant) exit;

$m = StockMovement::where('product_variant_id', $variant->id)
    ->where('quantity', 12)
    ->first();

print_r($m ? $m->toArray() : "None");
