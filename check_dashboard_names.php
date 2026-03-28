<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ProductVariant;

$variants = ProductVariant::join('products', 'product_variants.product_id', '=', 'products.id')
    ->select('product_variants.id', 'products.name as p_name', 'product_variants.name as v_name', 'product_variants.measurement', 'product_variants.packaging')
    ->limit(20)
    ->get();

foreach ($variants as $v) {
    $displayName = \App\Helpers\ProductHelper::generateDisplayName($v->p_name, $v->measurement . ' - ' . $v->packaging, $v->v_name);
    echo "ID: {$v->id} | P: {$v->p_name} | V: {$v->v_name} | Display: {$displayName}\n";
}
