<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ProductVariant;

foreach (ProductVariant::with('product')->get() as $v) {
    if (in_array($v->id, [14, 15, 16, 17, 18])) {
        echo "ID: {$v->id} | P_Name: {$v->product->name} | V_Name: {$v->name} | Disp: {$v->display_name} | Qty: " . ($v->stockLocations()->where('location', 'counter')->first()->quantity ?? 'None') . "\n";
    }
}
