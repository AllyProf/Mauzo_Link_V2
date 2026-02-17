<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get the admin/owner user (assuming user_id 1 or first user)
        $user = User::first();
        if (!$user) {
            $this->command->error("No user found to assign products to.");
            return;
        }

        // 2. Ensure a default supplier exists
        $supplier = Supplier::firstOrCreate(
            ['company_name' => 'General Supplier', 'user_id' => $user->id],
            ['address' => 'Local Market', 'phone' => '0000000000', 'is_active' => true]
        );

        // 3. Define the product data
        $products = [
            // SODA & WATER (Usually sold whole, Crate/Carton packaging)
            ['name' => 'Bonite Soda', 'category' => 'Soft Drinks', 'brand' => 'Bonite', 'packaging' => 'Crates', 'items' => 24, 'measurement' => '350ml'],
            ['name' => 'M/Water Big', 'category' => 'Water', 'brand' => 'MWater', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '1.5L'],
            ['name' => 'M/Water Small', 'category' => 'Water', 'brand' => 'MWater', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '500ml'],
            ['name' => 'Hill Sparkling Water', 'category' => 'Water', 'brand' => 'Hill', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '500ml'],
            ['name' => 'Ceres Juice', 'category' => 'Juices', 'brand' => 'Ceres', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '1L'],
            ['name' => 'Bavaria Chupa', 'category' => 'Non-Alcoholic Beverages', 'brand' => 'Bavaria', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'], // Assuming bottle
            ['name' => 'Bavaria Can', 'category' => 'Non-Alcoholic Beverages', 'brand' => 'Bavaria', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Baltika', 'category' => 'Non-Alcoholic Beverages', 'brand' => 'Baltika', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            
            // ENERGIZER
            ['name' => 'Red Bull', 'category' => 'Energy Drinks', 'brand' => 'Red Bull', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '250ml'],
            ['name' => 'Grand Malta', 'category' => 'Energy Drinks', 'brand' => 'Grand Malta', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],

            // BEERS/LAGER (Usually Crates)
            ['name' => 'Castle Lite', 'category' => 'Alcoholic Beverages', 'brand' => 'Castle', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '330ml'], // Assuming small
            ['name' => 'Kilimanjaro Ndogo', 'category' => 'Alcoholic Beverages', 'brand' => 'Kilimanjaro', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '330ml'],
            ['name' => 'Kilimanjaro Kubwa', 'category' => 'Alcoholic Beverages', 'brand' => 'Kilimanjaro', 'packaging' => 'Crates', 'items' => 20, 'measurement' => '500ml'],
            ['name' => 'Kilimanjaro Lite', 'category' => 'Alcoholic Beverages', 'brand' => 'Kilimanjaro', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '330ml'],
            ['name' => 'Safari Ndogo', 'category' => 'Alcoholic Beverages', 'brand' => 'Safari', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '330ml'],
            ['name' => 'Safari Kubwa', 'category' => 'Alcoholic Beverages', 'brand' => 'Safari', 'packaging' => 'Crates', 'items' => 20, 'measurement' => '500ml'],
            ['name' => 'Serengeti Lager Ndogo', 'category' => 'Alcoholic Beverages', 'brand' => 'Serengeti', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '330ml'],
            ['name' => 'Serengeti Lager Kubwa', 'category' => 'Alcoholic Beverages', 'brand' => 'Serengeti', 'packaging' => 'Crates', 'items' => 20, 'measurement' => '500ml'],
            ['name' => 'Serengeti Lite', 'category' => 'Alcoholic Beverages', 'brand' => 'Serengeti', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '330ml'],
            ['name' => 'Serengeti Lemon', 'category' => 'Alcoholic Beverages', 'brand' => 'Serengeti', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '330ml'],
            ['name' => 'Castle Lager', 'category' => 'Alcoholic Beverages', 'brand' => 'Castle', 'packaging' => 'Crates', 'items' => 25, 'measurement' => '500ml'], // Verify size
            ['name' => 'Fly Fish', 'category' => 'Alcoholic Beverages', 'brand' => 'Fly Fish', 'packaging' => 'Crates', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Guiness Kubwa', 'category' => 'Alcoholic Beverages', 'brand' => 'Guiness', 'packaging' => 'Crates', 'items' => 20, 'measurement' => '500ml'],
            ['name' => 'Guiness Smooth', 'category' => 'Alcoholic Beverages', 'brand' => 'Guiness', 'packaging' => 'Crates', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Smirnoff Black ICE', 'category' => 'Alcoholic Beverages', 'brand' => 'Smirnoff', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '300ml'],
            ['name' => 'Smirnoff Black Guarana', 'category' => 'Alcoholic Beverages', 'brand' => 'Smirnoff', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Smirnoff Black Pineapple', 'category' => 'Alcoholic Beverages', 'brand' => 'Smirnoff', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Heineken', 'category' => 'Alcoholic Beverages', 'brand' => 'Heineken', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Windhoek', 'category' => 'Alcoholic Beverages', 'brand' => 'Windhoek', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Brutal Apple Ruby', 'category' => 'Alcoholic Beverages', 'brand' => 'Brutal', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '275ml'],
            ['name' => 'Hanson Dry', 'category' => 'Alcoholic Beverages', 'brand' => 'Hanson', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Hanson Lite', 'category' => 'Alcoholic Beverages', 'brand' => 'Hanson', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],
            ['name' => 'Goldbargy', 'category' => 'Alcoholic Beverages', 'brand' => 'Goldbargy', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '330ml'],

            // GIN/SPIRITS (Often sold in Cartons/Boxes)
            ['name' => 'Gordons', 'category' => 'Alcoholic Beverages', 'brand' => 'Gordons', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 3000],
            ['name' => 'Gordons', 'category' => 'Alcoholic Beverages', 'brand' => 'Gordons', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            ['name' => 'Konyagi', 'category' => 'Alcoholic Beverages', 'brand' => 'Konyagi', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            ['name' => 'Konyagi', 'category' => 'Alcoholic Beverages', 'brand' => 'Konyagi', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '500ml', 'tots' => true, 'total_tots' => 16, 'price_tot' => 2000],
            ['name' => 'Konyagi', 'category' => 'Alcoholic Beverages', 'brand' => 'Konyagi', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2000],
            ['name' => 'Hanson Choice', 'category' => 'Alcoholic Beverages', 'brand' => 'Hanson', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            ['name' => 'Hanson Choice', 'category' => 'Alcoholic Beverages', 'brand' => 'Hanson', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2000],
            ['name' => 'K-Vant', 'category' => 'Alcoholic Beverages', 'brand' => 'K-Vant', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2000],
            ['name' => 'K-Vant', 'category' => 'Alcoholic Beverages', 'brand' => 'K-Vant', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],

            // OTHER SPIRITS
            ['name' => 'Magic Moment Green Apple', 'category' => 'Alcoholic Beverages', 'brand' => 'Magic Moment', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2500],
            ['name' => 'Magic Moment Chocolate', 'category' => 'Alcoholic Beverages', 'brand' => 'Magic Moment', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2500],
            ['name' => 'Jameson', 'category' => 'Alcoholic Beverages', 'brand' => 'Jameson', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '1Ltr', 'tots' => true, 'total_tots' => 33, 'price_tot' => 4000],
            ['name' => 'Jameson', 'category' => 'Alcoholic Beverages', 'brand' => 'Jameson', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 4000],
            ['name' => 'Jameson', 'category' => 'Alcoholic Beverages', 'brand' => 'Jameson', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '375ml', 'tots' => true, 'total_tots' => 12, 'price_tot' => 4000],
            ['name' => 'Jameson', 'category' => 'Alcoholic Beverages', 'brand' => 'Jameson', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            ['name' => 'Jameson Black Barrel', 'category' => 'Alcoholic Beverages', 'brand' => 'Jameson', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 5000],
            
            ['name' => 'J&B', 'category' => 'Alcoholic Beverages', 'brand' => 'J&B', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '1Ltr', 'tots' => true, 'total_tots' => 33, 'price_tot' => 3500],
            ['name' => 'J&B', 'category' => 'Alcoholic Beverages', 'brand' => 'J&B', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 3500],
            ['name' => 'J&B', 'category' => 'Alcoholic Beverages', 'brand' => 'J&B', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],

            ['name' => 'Grants', 'category' => 'Alcoholic Beverages', 'brand' => 'Grants', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '1Ltr', 'tots' => true, 'total_tots' => 33, 'price_tot' => 3000],
            ['name' => 'Grants', 'category' => 'Alcoholic Beverages', 'brand' => 'Grants', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 3000],
            
            ['name' => 'Camino Tequila', 'category' => 'Alcoholic Beverages', 'brand' => 'Camino', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 3500],
            
            ['name' => 'Amarula', 'category' => 'Alcoholic Beverages', 'brand' => 'Amarula', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '1Ltr', 'tots' => true, 'total_tots' => 33, 'price_tot' => 3000],
            ['name' => 'Amarula', 'category' => 'Alcoholic Beverages', 'brand' => 'Amarula', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 3000],
            ['name' => 'Amarula', 'category' => 'Alcoholic Beverages', 'brand' => 'Amarula', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '375ml', 'tots' => true, 'total_tots' => 12, 'price_tot' => 3000],

            ['name' => 'Gilbeys Gin', 'category' => 'Alcoholic Beverages', 'brand' => 'Gilbeys', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2500],
            ['name' => 'Gilbeys Gin', 'category' => 'Alcoholic Beverages', 'brand' => 'Gilbeys', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            
            ['name' => 'Captain Morgan', 'category' => 'Alcoholic Beverages', 'brand' => 'Captain Morgan', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 3000],
            ['name' => 'Captain Morgan', 'category' => 'Alcoholic Beverages', 'brand' => 'Captain Morgan', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            
            ['name' => 'Smirnoff Vodka', 'category' => 'Alcoholic Beverages', 'brand' => 'Smirnoff', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2500],
            ['name' => 'Smirnoff Vodka', 'category' => 'Alcoholic Beverages', 'brand' => 'Smirnoff', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '250ml', 'tots' => false],
            
            ['name' => 'Black Label', 'category' => 'Alcoholic Beverages', 'brand' => 'Johnnie Walker', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            ['name' => 'Red Label', 'category' => 'Alcoholic Beverages', 'brand' => 'Johnnie Walker', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],

            ['name' => 'Chrome Gin Smooth', 'category' => 'Alcoholic Beverages', 'brand' => 'Chrome', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2500],
            ['name' => 'Chrome Gin Vodka', 'category' => 'Alcoholic Beverages', 'brand' => 'Chrome', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2500],
            ['name' => 'Chrome Gin Smooth', 'category' => 'Alcoholic Beverages', 'brand' => 'Chrome', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            ['name' => 'Chrome Gin Vodka', 'category' => 'Alcoholic Beverages', 'brand' => 'Chrome', 'packaging' => 'Cartons', 'items' => 24, 'measurement' => '200ml', 'tots' => false],
            
            ['name' => 'Campari', 'category' => 'Alcoholic Beverages', 'brand' => 'Campari', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '1Ltr', 'tots' => true, 'total_tots' => 33, 'price_tot' => 4500],
            ['name' => 'Tanzanite', 'category' => 'Alcoholic Beverages', 'brand' => 'Tanzanite', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 3000],
            
            // Premium
            ['name' => 'Remmy Martin VSOP', 'category' => 'Alcoholic Beverages', 'brand' => 'Remmy Martin', 'packaging' => 'Boxes', 'items' => 6, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 15000],
            ['name' => 'Glenfiddich 15yrs', 'category' => 'Alcoholic Beverages', 'brand' => 'Glenfiddich', 'packaging' => 'Boxes', 'items' => 6, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 20000],
            ['name' => 'Famous Grouse', 'category' => 'Alcoholic Beverages', 'brand' => 'Famous Grouse', 'packaging' => 'Boxes', 'items' => 12, 'measurement' => '350ml', 'tots' => true, 'total_tots' => 12, 'price_tot' => 4000],
            ['name' => 'Highlife', 'category' => 'Alcoholic Beverages', 'brand' => 'Highlife', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => true, 'total_tots' => 25, 'price_tot' => 2500],
            
            // WINE
            ['name' => 'Martin Champaign', 'category' => 'Alcoholic Beverages', 'brand' => 'Martin', 'packaging' => 'Cartons', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Moet Nector Imperial', 'category' => 'Alcoholic Beverages', 'brand' => 'Moet', 'packaging' => 'Boxes', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Moet Rose', 'category' => 'Alcoholic Beverages', 'brand' => 'Moet', 'packaging' => 'Boxes', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Four Cousins Sweet Red', 'category' => 'Alcoholic Beverages', 'brand' => 'Four Cousins', 'packaging' => 'Cartons', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Four Cousins White', 'category' => 'Alcoholic Beverages', 'brand' => 'Four Cousins', 'packaging' => 'Cartons', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Drostdney Hoff Red Claret', 'category' => 'Alcoholic Beverages', 'brand' => 'Drostdney Hoff', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '700ml', 'tots' => false],
            ['name' => 'Pearly Bay Dry Red', 'category' => 'Alcoholic Beverages', 'brand' => 'Pearly Bay', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'KWV Merlot', 'category' => 'Alcoholic Beverages', 'brand' => 'KWV', 'packaging' => 'Cartons', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Lions Hill Sweet Red', 'category' => 'Alcoholic Beverages', 'brand' => 'Lions Hill', 'packaging' => 'Cartons', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Dompo', 'category' => 'Alcoholic Beverages', 'brand' => 'Dompo', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Robertson Sweet Red', 'category' => 'Alcoholic Beverages', 'brand' => 'Robertson', 'packaging' => 'Cartons', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Nederburg Merlot', 'category' => 'Alcoholic Beverages', 'brand' => 'Nederburg', 'packaging' => 'Cartons', 'items' => 6, 'measurement' => '750ml', 'tots' => false],
            ['name' => 'Altar Wine', 'category' => 'Alcoholic Beverages', 'brand' => 'Altar', 'packaging' => 'Cartons', 'items' => 12, 'measurement' => '750ml', 'tots' => false],
        ];

        DB::beginTransaction();
        try {
            foreach ($products as $data) {
                // 1. Create or Find Product
                $product = Product::firstOrCreate([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'brand' => $data['brand'],
                ], [
                    'supplier_id' => $supplier->id,
                    'category' => $data['category'],
                    'description' => "Imported via seeder: " . $data['name'],
                    'is_active' => true,
                ]);

                // 2. Create Variant
                $variant = ProductVariant::updateOrCreate([
                    'product_id' => $product->id,
                    'measurement' => $data['measurement'],
                ], [
                    'packaging' => $data['packaging'],
                    'items_per_package' => $data['items'],
                    'buying_price_per_unit' => 0, // Default
                    'selling_price_per_unit' => 0, // Default
                    'can_sell_in_tots' => $data['tots'] ?? false,
                    'total_tots' => ($data['tots'] ?? false) ? ($data['total_tots'] ?? null) : null,
                    'selling_price_per_tot' => ($data['tots'] ?? false) ? ($data['price_tot'] ?? 0) : null,
                    'is_active' => true,
                ]);

                // 3. Create initial stock location for Warehouse (0 quantity)
                StockLocation::firstOrCreate([
                    'user_id' => $user->id,
                    'product_variant_id' => $variant->id,
                    'location' => 'warehouse',
                ], [
                    'quantity' => 0,
                    'average_buying_price' => 0,
                    'selling_price' => 0,
                ]);

                // 4. Create initial stock location for Counter (0 quantity)
                StockLocation::firstOrCreate([
                    'user_id' => $user->id,
                    'product_variant_id' => $variant->id,
                    'location' => 'counter',
                ], [
                    'quantity' => 0, // No stock initially
                    'average_buying_price' => 0,
                    'selling_price' => 0,
                ]);
            }
            DB::commit();
            $this->command->info('Products seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error seeding products: " . $e->getMessage());
        }
    }
}
