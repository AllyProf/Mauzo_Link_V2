<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\StockTransfer;

echo "--- Products containing 'Bonite' ---\n";
$products = Product::where('brand', 'LIKE', '%bonite%')
    ->orWhere('name', 'LIKE', '%bonite%')
    ->get();

foreach ($products as $p) {
    echo "ID: {$p->id} | Name: {$p->name} | Brand: {$p->brand} | Category: {$p->category}\n";
}

echo "\n--- Recent Stock Transfers ---\n";
$transfers = StockTransfer::with('productVariant.product')->latest()->take(5)->get();
foreach ($transfers as $t) {
    $p = $t->productVariant->product;
    echo "ID: {$t->id} | Prod: " . ($p->name ?? 'N/A') . " | Brand: " . ($p->brand ?? 'N/A') . " | Status: {$t->status}\n";
}
