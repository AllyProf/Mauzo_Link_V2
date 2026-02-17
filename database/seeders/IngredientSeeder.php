<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all owners (non-admin users)
        $owners = User::where('role', '!=', 'admin')->get();

        if ($owners->isEmpty()) {
            $this->command->warn('No owners found. Please create an owner account first.');
            return;
        }

        // Common kitchen ingredients for restaurants
        $commonIngredients = [
            // Vegetables
            ['name' => 'Onions', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 2000],
            ['name' => 'Tomatoes', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 2500],
            ['name' => 'Garlic', 'unit' => 'kg', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 8000],
            ['name' => 'Ginger', 'unit' => 'kg', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 6000],
            ['name' => 'Potatoes', 'unit' => 'kg', 'min_stock_level' => 10, 'max_stock_level' => 100, 'cost_per_unit' => 2000],
            ['name' => 'Carrots', 'unit' => 'kg', 'min_stock_level' => 3, 'max_stock_level' => 30, 'cost_per_unit' => 3000],
            ['name' => 'Bell Peppers', 'unit' => 'kg', 'min_stock_level' => 3, 'max_stock_level' => 30, 'cost_per_unit' => 4000],
            ['name' => 'Cabbage', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 1500],
            ['name' => 'Spinach', 'unit' => 'kg', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 3000],
            ['name' => 'Lettuce', 'unit' => 'kg', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 3500],
            
            // Meat & Protein
            ['name' => 'Chicken Breast', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 12000],
            ['name' => 'Chicken Thighs', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 10000],
            ['name' => 'Beef', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 15000],
            ['name' => 'Fish (Tilapia)', 'unit' => 'kg', 'min_stock_level' => 3, 'max_stock_level' => 30, 'cost_per_unit' => 8000],
            ['name' => 'Eggs', 'unit' => 'pieces', 'min_stock_level' => 30, 'max_stock_level' => 500, 'cost_per_unit' => 200],
            
            // Spices & Seasonings
            ['name' => 'Salt', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 1000],
            ['name' => 'Black Pepper', 'unit' => 'kg', 'min_stock_level' => 1, 'max_stock_level' => 10, 'cost_per_unit' => 15000],
            ['name' => 'Curry Powder', 'unit' => 'kg', 'min_stock_level' => 1, 'max_stock_level' => 10, 'cost_per_unit' => 12000],
            ['name' => 'Turmeric', 'unit' => 'kg', 'min_stock_level' => 1, 'max_stock_level' => 10, 'cost_per_unit' => 10000],
            ['name' => 'Cumin', 'unit' => 'kg', 'min_stock_level' => 0.5, 'max_stock_level' => 5, 'cost_per_unit' => 18000],
            ['name' => 'Coriander Powder', 'unit' => 'kg', 'min_stock_level' => 1, 'max_stock_level' => 10, 'cost_per_unit' => 14000],
            ['name' => 'Paprika', 'unit' => 'kg', 'min_stock_level' => 0.5, 'max_stock_level' => 5, 'cost_per_unit' => 16000],
            ['name' => 'Chili Powder', 'unit' => 'kg', 'min_stock_level' => 1, 'max_stock_level' => 10, 'cost_per_unit' => 13000],
            
            // Oils & Fats
            ['name' => 'Cooking Oil', 'unit' => 'liters', 'min_stock_level' => 10, 'max_stock_level' => 100, 'cost_per_unit' => 3500],
            ['name' => 'Butter', 'unit' => 'kg', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 12000],
            
            // Grains & Starches
            ['name' => 'Rice', 'unit' => 'kg', 'min_stock_level' => 20, 'max_stock_level' => 200, 'cost_per_unit' => 3000],
            ['name' => 'Wheat Flour', 'unit' => 'kg', 'min_stock_level' => 10, 'max_stock_level' => 100, 'cost_per_unit' => 2500],
            ['name' => 'Maize Flour', 'unit' => 'kg', 'min_stock_level' => 10, 'max_stock_level' => 100, 'cost_per_unit' => 2000],
            
            // Dairy
            ['name' => 'Milk', 'unit' => 'liters', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 2500],
            ['name' => 'Cream', 'unit' => 'liters', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 5000],
            ['name' => 'Cheese', 'unit' => 'kg', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 15000],
            
            // Herbs & Fresh
            ['name' => 'Coriander Leaves', 'unit' => 'bunch', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 500],
            ['name' => 'Parsley', 'unit' => 'bunch', 'min_stock_level' => 3, 'max_stock_level' => 30, 'cost_per_unit' => 600],
            ['name' => 'Basil', 'unit' => 'bunch', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 700],
            
            // Sauces & Condiments
            ['name' => 'Tomato Paste', 'unit' => 'kg', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 4000],
            ['name' => 'Soy Sauce', 'unit' => 'liters', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 6000],
            ['name' => 'Vinegar', 'unit' => 'liters', 'min_stock_level' => 2, 'max_stock_level' => 20, 'cost_per_unit' => 3000],
            ['name' => 'Lemon Juice', 'unit' => 'liters', 'min_stock_level' => 1, 'max_stock_level' => 10, 'cost_per_unit' => 5000],
            
            // Stock & Broth
            ['name' => 'Chicken Stock', 'unit' => 'liters', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 4000],
            ['name' => 'Beef Stock', 'unit' => 'liters', 'min_stock_level' => 3, 'max_stock_level' => 30, 'cost_per_unit' => 4500],
            
            // Other
            ['name' => 'Sugar', 'unit' => 'kg', 'min_stock_level' => 5, 'max_stock_level' => 50, 'cost_per_unit' => 2500],
            ['name' => 'Baking Powder', 'unit' => 'kg', 'min_stock_level' => 1, 'max_stock_level' => 10, 'cost_per_unit' => 8000],
            ['name' => 'Yeast', 'unit' => 'kg', 'min_stock_level' => 0.5, 'max_stock_level' => 5, 'cost_per_unit' => 12000],
        ];

        foreach ($owners as $owner) {
            $this->command->info("Creating ingredients for owner: {$owner->email}");

            foreach ($commonIngredients as $ingredientData) {
                Ingredient::firstOrCreate(
                    [
                        'user_id' => $owner->id,
                        'name' => $ingredientData['name'],
                    ],
                    [
                        'unit' => $ingredientData['unit'],
                        'current_stock' => 0, // Start with 0 stock - they need to add receipts
                        'min_stock_level' => $ingredientData['min_stock_level'],
                        'max_stock_level' => $ingredientData['max_stock_level'] ?? null,
                        'cost_per_unit' => $ingredientData['cost_per_unit'],
                        'location' => 'Kitchen',
                        'is_active' => true,
                    ]
                );
            }

            $this->command->info("✓ Created " . count($commonIngredients) . " ingredients for {$owner->email}");
        }

        $this->command->info('Ingredient seeding completed!');
        $this->command->info('Note: All ingredients start with 0 stock. Use "Ingredient Receipts" to add stock.');
    }
}
