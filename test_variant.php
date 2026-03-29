<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Variants count: " . \App\Models\ProductVariant::count() . "\n";
echo "Products count: " . \App\Models\Product::count() . "\n";
