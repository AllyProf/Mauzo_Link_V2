<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ProductVariant;
use App\Models\StockLocation;

$variants = ProductVariant::with('product')->get();
foreach ($variants as $v) {
    if (stripos($v->display_name, 'Wine') !== false || ($v->product && stripos($v->product->name, 'Wine') !== false)) {
        $stocks = StockLocation::where('product_variant_id', $v->id)->where('location', 'counter')->get();
        foreach ($stocks as $s) {
            echo "ID: {$v->id} | Name: {$v->product->name} | Var: {$v->display_name} | Qty: {$s->quantity} | Loc ID: {$s->id}\n";
        }
    }
}
