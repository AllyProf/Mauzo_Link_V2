<?php
/**
 * Create Sample Food Items and Ingredients
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FoodItem;
use App\Models\Ingredient;
use App\Models\User;

echo "========================================\n";
echo "Create Food Items and Ingredients\n";
echo "========================================\n\n";

$ownerEmail = $argv[1] ?? 'admin@medalion.com';
$owner = User::where('email', $ownerEmail)->first();

if (!$owner) {
    echo "❌ Owner not found with email: {$ownerEmail}\n";
    exit(1);
}

echo "✓ Found owner: {$owner->name} (ID: {$owner->id})\n\n";

// Sample Food Items
$foodItems = [
    // Main Dishes
    ['name' => 'Grilled Chicken', 'variant_name' => 'Regular', 'price' => 15000, 'prep_time_minutes' => 25, 'description' => 'Tender grilled chicken served with rice and vegetables'],
    ['name' => 'Grilled Chicken', 'variant_name' => 'Large', 'price' => 20000, 'prep_time_minutes' => 25, 'description' => 'Large portion of tender grilled chicken served with rice and vegetables'],
    ['name' => 'Beef Steak', 'variant_name' => 'Regular', 'price' => 18000, 'prep_time_minutes' => 30, 'description' => 'Juicy beef steak with mashed potatoes and vegetables'],
    ['name' => 'Beef Steak', 'variant_name' => 'Large', 'price' => 25000, 'prep_time_minutes' => 30, 'description' => 'Large portion of juicy beef steak with mashed potatoes'],
    ['name' => 'Fish Fillet', 'variant_name' => 'Regular', 'price' => 12000, 'prep_time_minutes' => 20, 'description' => 'Fresh fish fillet with chips and salad'],
    ['name' => 'Fish Fillet', 'variant_name' => 'Large', 'price' => 16000, 'prep_time_minutes' => 20, 'description' => 'Large portion of fresh fish fillet with chips'],
    
    // Rice Dishes
    ['name' => 'Pilau Rice', 'variant_name' => 'Regular', 'price' => 8000, 'prep_time_minutes' => 15, 'description' => 'Aromatic spiced rice with meat'],
    ['name' => 'Pilau Rice', 'variant_name' => 'Large', 'price' => 12000, 'prep_time_minutes' => 15, 'description' => 'Large portion of aromatic spiced rice with meat'],
    ['name' => 'Fried Rice', 'variant_name' => 'Regular', 'price' => 7000, 'prep_time_minutes' => 12, 'description' => 'Delicious fried rice with vegetables and choice of meat'],
    ['name' => 'Plain Rice', 'variant_name' => 'Regular', 'price' => 3000, 'prep_time_minutes' => 10, 'description' => 'Steamed white rice'],
    
    // Pasta
    ['name' => 'Spaghetti Bolognese', 'variant_name' => 'Regular', 'price' => 10000, 'prep_time_minutes' => 18, 'description' => 'Classic spaghetti with meat sauce'],
    ['name' => 'Spaghetti Carbonara', 'variant_name' => 'Regular', 'price' => 11000, 'prep_time_minutes' => 20, 'description' => 'Creamy spaghetti carbonara'],
    ['name' => 'Macaroni Cheese', 'variant_name' => 'Regular', 'price' => 9000, 'prep_time_minutes' => 15, 'description' => 'Creamy macaroni with cheese'],
    
    // Snacks & Appetizers
    ['name' => 'Chicken Wings', 'variant_name' => '6 pieces', 'price' => 8000, 'prep_time_minutes' => 20, 'description' => 'Crispy fried chicken wings'],
    ['name' => 'Chicken Wings', 'variant_name' => '12 pieces', 'price' => 15000, 'prep_time_minutes' => 25, 'description' => 'Crispy fried chicken wings - large portion'],
    ['name' => 'French Fries', 'variant_name' => 'Regular', 'price' => 4000, 'prep_time_minutes' => 10, 'description' => 'Crispy golden french fries'],
    ['name' => 'French Fries', 'variant_name' => 'Large', 'price' => 6000, 'prep_time_minutes' => 12, 'description' => 'Large portion of crispy golden french fries'],
    ['name' => 'Chicken Burger', 'variant_name' => 'Regular', 'price' => 10000, 'prep_time_minutes' => 15, 'description' => 'Juicy chicken burger with fries'],
    ['name' => 'Beef Burger', 'variant_name' => 'Regular', 'price' => 12000, 'prep_time_minutes' => 18, 'description' => 'Classic beef burger with fries'],
    
    // Soups
    ['name' => 'Chicken Soup', 'variant_name' => 'Regular', 'price' => 6000, 'prep_time_minutes' => 15, 'description' => 'Hot and hearty chicken soup'],
    ['name' => 'Beef Soup', 'variant_name' => 'Regular', 'price' => 7000, 'prep_time_minutes' => 15, 'description' => 'Hot and hearty beef soup'],
    ['name' => 'Vegetable Soup', 'variant_name' => 'Regular', 'price' => 5000, 'prep_time_minutes' => 12, 'description' => 'Fresh vegetable soup'],
    
    // Salads
    ['name' => 'Garden Salad', 'variant_name' => 'Regular', 'price' => 5000, 'prep_time_minutes' => 8, 'description' => 'Fresh mixed garden salad'],
    ['name' => 'Chicken Salad', 'variant_name' => 'Regular', 'price' => 8000, 'prep_time_minutes' => 10, 'description' => 'Fresh salad with grilled chicken'],
    
    // Local Dishes
    ['name' => 'Ugali & Fish', 'variant_name' => 'Regular', 'price' => 10000, 'prep_time_minutes' => 20, 'description' => 'Traditional ugali with fried fish'],
    ['name' => 'Ugali & Beef', 'variant_name' => 'Regular', 'price' => 12000, 'prep_time_minutes' => 20, 'description' => 'Traditional ugali with beef stew'],
    ['name' => 'Wali & Kuku', 'variant_name' => 'Regular', 'price' => 11000, 'prep_time_minutes' => 25, 'description' => 'Rice with chicken curry'],
    ['name' => 'Chips Mayai', 'variant_name' => 'Regular', 'price' => 6000, 'prep_time_minutes' => 12, 'description' => 'French fries mixed with eggs'],
];

// Sample Ingredients
$ingredients = [
    // Proteins
    ['name' => 'Chicken Breast', 'unit' => 'kg', 'current_stock' => 50, 'min_stock_level' => 10, 'max_stock_level' => 100, 'location' => 'Freezer', 'cost_per_unit' => 8000],
    ['name' => 'Beef', 'unit' => 'kg', 'current_stock' => 30, 'min_stock_level' => 10, 'max_stock_level' => 80, 'location' => 'Freezer', 'cost_per_unit' => 12000],
    ['name' => 'Fish Fillet', 'unit' => 'kg', 'current_stock' => 25, 'min_stock_level' => 5, 'max_stock_level' => 50, 'location' => 'Freezer', 'cost_per_unit' => 10000],
    ['name' => 'Eggs', 'unit' => 'piece', 'current_stock' => 200, 'min_stock_level' => 50, 'max_stock_level' => 500, 'location' => 'Fridge', 'cost_per_unit' => 200],
    
    // Grains & Starches
    ['name' => 'Rice', 'unit' => 'kg', 'current_stock' => 100, 'min_stock_level' => 20, 'max_stock_level' => 200, 'location' => 'Pantry', 'cost_per_unit' => 3000],
    ['name' => 'Maize Flour', 'unit' => 'kg', 'current_stock' => 50, 'min_stock_level' => 10, 'max_stock_level' => 100, 'location' => 'Pantry', 'cost_per_unit' => 2500],
    ['name' => 'Spaghetti', 'unit' => 'kg', 'current_stock' => 20, 'min_stock_level' => 5, 'max_stock_level' => 50, 'location' => 'Pantry', 'cost_per_unit' => 4000],
    ['name' => 'Macaroni', 'unit' => 'kg', 'current_stock' => 15, 'min_stock_level' => 5, 'max_stock_level' => 40, 'location' => 'Pantry', 'cost_per_unit' => 3500],
    ['name' => 'Potatoes', 'unit' => 'kg', 'current_stock' => 40, 'min_stock_level' => 10, 'max_stock_level' => 80, 'location' => 'Storage', 'cost_per_unit' => 2000],
    
    // Vegetables
    ['name' => 'Onions', 'unit' => 'kg', 'current_stock' => 15, 'min_stock_level' => 5, 'max_stock_level' => 30, 'location' => 'Storage', 'cost_per_unit' => 1500],
    ['name' => 'Tomatoes', 'unit' => 'kg', 'current_stock' => 20, 'min_stock_level' => 5, 'max_stock_level' => 40, 'location' => 'Storage', 'cost_per_unit' => 2000],
    ['name' => 'Carrots', 'unit' => 'kg', 'current_stock' => 10, 'min_stock_level' => 3, 'max_stock_level' => 25, 'location' => 'Fridge', 'cost_per_unit' => 1800],
    ['name' => 'Cabbage', 'unit' => 'piece', 'current_stock' => 8, 'min_stock_level' => 2, 'max_stock_level' => 15, 'location' => 'Fridge', 'cost_per_unit' => 1500],
    ['name' => 'Lettuce', 'unit' => 'piece', 'current_stock' => 5, 'min_stock_level' => 2, 'max_stock_level' => 10, 'location' => 'Fridge', 'cost_per_unit' => 2000],
    
    // Spices & Seasonings
    ['name' => 'Salt', 'unit' => 'kg', 'current_stock' => 10, 'min_stock_level' => 2, 'max_stock_level' => 20, 'location' => 'Pantry', 'cost_per_unit' => 1000],
    ['name' => 'Black Pepper', 'unit' => 'kg', 'current_stock' => 2, 'min_stock_level' => 0.5, 'max_stock_level' => 5, 'location' => 'Pantry', 'cost_per_unit' => 15000],
    ['name' => 'Curry Powder', 'unit' => 'kg', 'current_stock' => 3, 'min_stock_level' => 0.5, 'max_stock_level' => 8, 'location' => 'Pantry', 'cost_per_unit' => 12000],
    ['name' => 'Garlic', 'unit' => 'kg', 'current_stock' => 5, 'min_stock_level' => 1, 'max_stock_level' => 10, 'location' => 'Storage', 'cost_per_unit' => 5000],
    ['name' => 'Ginger', 'unit' => 'kg', 'current_stock' => 3, 'min_stock_level' => 0.5, 'max_stock_level' => 8, 'location' => 'Storage', 'cost_per_unit' => 6000],
    
    // Oils & Fats
    ['name' => 'Cooking Oil', 'unit' => 'liter', 'current_stock' => 30, 'min_stock_level' => 5, 'max_stock_level' => 60, 'location' => 'Pantry', 'cost_per_unit' => 4000],
    ['name' => 'Butter', 'unit' => 'kg', 'current_stock' => 5, 'min_stock_level' => 1, 'max_stock_level' => 15, 'location' => 'Fridge', 'cost_per_unit' => 8000],
    
    // Dairy
    ['name' => 'Milk', 'unit' => 'liter', 'current_stock' => 20, 'min_stock_level' => 5, 'max_stock_level' => 40, 'location' => 'Fridge', 'cost_per_unit' => 3000],
    ['name' => 'Cheese', 'unit' => 'kg', 'current_stock' => 8, 'min_stock_level' => 2, 'max_stock_level' => 20, 'location' => 'Fridge', 'cost_per_unit' => 12000],
    
    // Other
    ['name' => 'Flour', 'unit' => 'kg', 'current_stock' => 25, 'min_stock_level' => 5, 'max_stock_level' => 50, 'location' => 'Pantry', 'cost_per_unit' => 2500],
    ['name' => 'Sugar', 'unit' => 'kg', 'current_stock' => 15, 'min_stock_level' => 3, 'max_stock_level' => 30, 'location' => 'Pantry', 'cost_per_unit' => 3000],
    ['name' => 'Bread', 'unit' => 'loaf', 'current_stock' => 10, 'min_stock_level' => 3, 'max_stock_level' => 20, 'location' => 'Pantry', 'cost_per_unit' => 2500],
];

echo "Creating Food Items...\n";
echo str_repeat("-", 60) . "\n";

$foodItemsCreated = 0;
$foodItemsUpdated = 0;
$sortOrder = 1;

foreach ($foodItems as $itemData) {
    $foodItem = FoodItem::where('user_id', $owner->id)
        ->where('name', $itemData['name'])
        ->where('variant_name', $itemData['variant_name'])
        ->first();
    
    if ($foodItem) {
        $foodItem->update([
            'price' => $itemData['price'],
            'prep_time_minutes' => $itemData['prep_time_minutes'],
            'description' => $itemData['description'],
            'is_available' => true,
            'sort_order' => $sortOrder,
        ]);
        $foodItemsUpdated++;
        echo "  ✓ Updated: {$itemData['name']} ({$itemData['variant_name']}) - TSh " . number_format($itemData['price'], 0) . "\n";
    } else {
        FoodItem::create([
            'user_id' => $owner->id,
            'name' => $itemData['name'],
            'variant_name' => $itemData['variant_name'],
            'price' => $itemData['price'],
            'prep_time_minutes' => $itemData['prep_time_minutes'],
            'description' => $itemData['description'],
            'is_available' => true,
            'sort_order' => $sortOrder,
        ]);
        $foodItemsCreated++;
        echo "  ✓ Created: {$itemData['name']} ({$itemData['variant_name']}) - TSh " . number_format($itemData['price'], 0) . "\n";
    }
    $sortOrder++;
}

echo "\n";
echo "Creating Ingredients...\n";
echo str_repeat("-", 60) . "\n";

$ingredientsCreated = 0;
$ingredientsUpdated = 0;

foreach ($ingredients as $ingData) {
    $ingredient = Ingredient::where('user_id', $owner->id)
        ->where('name', $ingData['name'])
        ->first();
    
    if ($ingredient) {
        $ingredient->update([
            'unit' => $ingData['unit'],
            'current_stock' => $ingData['current_stock'],
            'min_stock_level' => $ingData['min_stock_level'],
            'max_stock_level' => $ingData['max_stock_level'] ?? null,
            'location' => $ingData['location'] ?? null,
            'cost_per_unit' => $ingData['cost_per_unit'] ?? null,
            'is_active' => true,
        ]);
        $ingredientsUpdated++;
        echo "  ✓ Updated: {$ingData['name']} ({$ingData['current_stock']} {$ingData['unit']})\n";
    } else {
        Ingredient::create([
            'user_id' => $owner->id,
            'name' => $ingData['name'],
            'unit' => $ingData['unit'],
            'current_stock' => $ingData['current_stock'],
            'min_stock_level' => $ingData['min_stock_level'],
            'max_stock_level' => $ingData['max_stock_level'] ?? null,
            'location' => $ingData['location'] ?? null,
            'cost_per_unit' => $ingData['cost_per_unit'] ?? null,
            'is_active' => true,
        ]);
        $ingredientsCreated++;
        echo "  ✓ Created: {$ingData['name']} ({$ingData['current_stock']} {$ingData['unit']})\n";
    }
}

echo "\n";
echo "========================================\n";
echo "Summary:\n";
echo "  Food Items Created: {$foodItemsCreated}\n";
echo "  Food Items Updated: {$foodItemsUpdated}\n";
echo "  Ingredients Created: {$ingredientsCreated}\n";
echo "  Ingredients Updated: {$ingredientsUpdated}\n";
echo "========================================\n";
echo "\n✓ Food items and ingredients created successfully!\n";
echo "  You can now update the images for food items in the chef dashboard.\n";
echo "  Go to: /bar/chef/food-items\n";





