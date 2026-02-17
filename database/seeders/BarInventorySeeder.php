<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BarInventorySeeder extends Seeder
{
    public function run()
    {
        $owner = User::where('role', 'owner')->first();
        if (!$owner) return;

        $inventory = [
            'Soda' => [
                ['name' => 'Pepsi', 'brand' => 'PepsiCo', 'variants' => [['m' => '350ml', 'p' => 'Bottle']]],
                ['name' => 'Fanta Orange', 'brand' => 'Coca-Cola', 'variants' => [['m' => '350ml', 'p' => 'Bottle']]],
                ['name' => 'Fanta Pineapple', 'brand' => 'Coca-Cola', 'variants' => [['m' => '350ml', 'p' => 'Bottle']]],
                ['name' => 'Coca Cola', 'brand' => 'Coca-Cola', 'variants' => [['m' => '350ml', 'p' => 'Bottle']]],
                ['name' => 'Sprite', 'brand' => 'Coca-Cola', 'variants' => [['m' => '350ml', 'p' => 'Bottle']]],
                ['name' => 'Novida', 'brand' => 'Coca-Cola', 'variants' => [['m' => '300ml', 'p' => 'Bottle']]],
                ['name' => 'Sayona Soda', 'brand' => 'Sayona', 'variants' => [['m' => '350ml', 'p' => 'Bottle']]],
                ['name' => 'Ceres Juice', 'brand' => 'Ceres', 'variants' => [['m' => '1L', 'p' => 'Carton']]],
            ],
            'Water' => [
                ['name' => 'Maji ya Kilimanjaro', 'brand' => 'METL', 'variants' => [['m' => '500ml', 'p' => 'Bottle'], ['m' => '1.5L', 'p' => 'Bottle']]],
                ['name' => 'Maji ya Uhai', 'brand' => 'Bakhresa', 'variants' => [['m' => '500ml', 'p' => 'Bottle'], ['m' => '1.5L', 'p' => 'Bottle']]],
                ['name' => 'Hill Sparkling Water', 'brand' => 'Hill', 'variants' => [['m' => '500ml', 'p' => 'Bottle']]],
            ],
            'Energies' => [
                ['name' => 'Red Bull', 'brand' => 'Red Bull', 'variants' => [['m' => '250ml', 'p' => 'Cans']]],
                ['name' => 'Mo Energy', 'brand' => 'METL', 'variants' => [['m' => '250ml', 'p' => 'Cans']]],
                ['name' => 'Azam Energy', 'brand' => 'Bakhresa', 'variants' => [['m' => '250ml', 'p' => 'Cans']]],
                ['name' => 'Grand Malta', 'brand' => 'TBL', 'variants' => [['m' => '330ml', 'p' => 'Bottle']]],
            ],
            'Beer/Lager' => [
                ['name' => 'Kilimanjaro Premium Lager', 'brand' => 'TBL', 'variants' => [['m' => '500ml', 'p' => 'Bottle'], ['m' => '330ml', 'p' => 'Bottle']]],
                ['name' => 'Safari Lager', 'brand' => 'TBL', 'variants' => [['m' => '500ml', 'p' => 'Bottle']]],
                ['name' => 'Castle Lite', 'brand' => 'TBL', 'variants' => [['m' => '500ml', 'p' => 'Bottle'], ['m' => '330ml', 'p' => 'Bottle']]],
                ['name' => 'Serengeti Premium Lager', 'brand' => 'SBL', 'variants' => [['m' => '500ml', 'p' => 'Bottle']]],
                ['name' => 'Serengeti Lite', 'brand' => 'SBL', 'variants' => [['m' => '500ml', 'p' => 'Bottle']]],
                ['name' => 'Guinness Smooth', 'brand' => 'SBL', 'variants' => [['m' => '330ml', 'p' => 'Bottle']]],
                ['name' => 'Heineken', 'brand' => 'Heineken', 'variants' => [['m' => '330ml', 'p' => 'Bottle']]],
                ['name' => 'Windhoek', 'brand' => 'TBL', 'variants' => [['m' => '330ml', 'p' => 'Bottle']]],
            ],
            'Can Beer' => [
                ['name' => 'Kilimanjaro Can', 'brand' => 'TBL', 'variants' => [['m' => '500ml', 'p' => 'Cans']]],
                ['name' => 'Castle Lite Can', 'brand' => 'TBL', 'variants' => [['m' => '500ml', 'p' => 'Cans']]],
                ['name' => 'Serengeti Can', 'brand' => 'SBL', 'variants' => [['m' => '500ml', 'p' => 'Cans']]],
                ['name' => 'Savanna Dry', 'brand' => 'TBL', 'variants' => [['m' => '330ml', 'p' => 'Bottle']]],
            ],
            'Wine by Bottle' => [
                ['name' => 'Robertson Sweet Red', 'brand' => 'Robertson', 'variants' => [['m' => '750ml', 'p' => 'Bottle']]],
                ['name' => 'Robertson Sweet White', 'brand' => 'Robertson', 'variants' => [['m' => '750ml', 'p' => 'Bottle']]],
                ['name' => 'Drostdy Hof Red', 'brand' => 'Drostdy Hof', 'variants' => [['m' => '750ml', 'p' => 'Bottle']]],
                ['name' => 'Moet & Chandon Imperial', 'brand' => 'Moet', 'variants' => [['m' => '750ml', 'p' => 'Bottle']]],
                ['name' => 'Pearly Bay Sweet Red', 'brand' => 'Pearly Bay', 'variants' => [['m' => '750ml', 'p' => 'Bottle']]],
                ['name' => 'Four Cousins Sweet Red', 'brand' => 'Four Cousins', 'variants' => [['m' => '750ml', 'p' => 'Bottle']]],
            ],
            'Brandy/Whisky/RUM/Gin' => [
                ['name' => 'Hennessy VS', 'brand' => 'Hennessy', 'variants' => [['m' => '700ml', 'p' => 'Bottle'], ['m' => '200ml', 'p' => 'Bottle']]],
                ['name' => 'Hennessy VSOP', 'brand' => 'Hennessy', 'variants' => [['m' => '700ml', 'p' => 'Bottle']]],
                ['name' => 'Jack Daniel\'s Old No. 7', 'brand' => 'Jack Daniel\'s', 'variants' => [['m' => '700ml', 'p' => 'Bottle'], ['m' => '1L', 'p' => 'Bottle']]],
                ['name' => 'Jameson Irish Whiskey', 'brand' => 'Jameson', 'variants' => [['m' => '700ml', 'p' => 'Bottle'], ['m' => '1L', 'p' => 'Bottle']]],
                ['name' => 'Johnnie Walker Red Label', 'brand' => 'Johnnie Walker', 'variants' => [['m' => '700ml', 'p' => 'Bottle'], ['m' => '375ml', 'p' => 'Bottle']]],
                ['name' => 'Johnnie Walker Black Label', 'brand' => 'Johnnie Walker', 'variants' => [['m' => '700ml', 'p' => 'Bottle']]],
                ['name' => 'K-Vant', 'brand' => 'K-Vant', 'variants' => [['m' => '250ml', 'p' => 'Bottle'], ['m' => '500ml', 'p' => 'Bottle']]],
                ['name' => 'Gin Gilbeys', 'brand' => 'Gilbeys', 'variants' => [['m' => '750ml', 'p' => 'Bottle'], ['m' => '250ml', 'p' => 'Bottle']]],
                ['name' => 'Captain Morgan Gold', 'brand' => 'Captain Morgan', 'variants' => [['m' => '750ml', 'p' => 'Bottle']]],
            ],
        ];

        foreach ($inventory as $category => $products) {
            foreach ($products as $pData) {
                $product = Product::create([
                    'user_id' => $owner->id,
                    'name' => $pData['name'],
                    'brand' => $pData['brand'],
                    'category' => $category,
                    'image' => 'products/placeholder.png',
                    'is_active' => true,
                ]);

                foreach ($pData['variants'] as $vData) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $pData['name'] . ' ' . $vData['m'] . ' ' . $vData['p'],
                        'measurement' => $vData['m'],
                        'packaging' => $vData['p'],
                        'items_per_package' => 1,
                        'buying_price_per_unit' => 0,
                        'selling_price_per_unit' => 0,
                        'image' => 'products/placeholder_variant.png',
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
