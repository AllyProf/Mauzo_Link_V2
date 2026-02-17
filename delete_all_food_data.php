<?php

/**
 * Script to delete all food-related data to start fresh
 * This includes: ingredients, receipts, batches, stock movements, recipes, food items, etc.
 * 
 * Usage: php delete_all_food_data.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ingredient;
use App\Models\IngredientReceipt;
use App\Models\IngredientBatch;
use App\Models\IngredientStockMovement;
use App\Models\FoodOrderIngredient;
use App\Models\RecipeIngredient;
use App\Models\Recipe;
use App\Models\FoodItem;
use App\Models\KitchenOrderItem;

echo "========================================\n";
echo "Food Data Cleanup Script\n";
echo "========================================\n\n";

// Check for --force flag
$force = in_array('--force', $argv ?? []);

if (!$force) {
    // Ask for confirmation
    echo "WARNING: This will delete ALL food-related data including:\n";
    echo "  - Food Items\n";
    echo "  - Recipes\n";
    echo "  - Ingredients\n";
    echo "  - Ingredient Receipts\n";
    echo "  - Ingredient Batches\n";
    echo "  - Ingredient Stock Movements\n";
    echo "  - Food Order Ingredients\n";
    echo "  - Recipe Ingredients\n";
    echo "\n";
    echo "This action CANNOT be undone!\n";
    echo "\n";
    echo "Type 'DELETE ALL' to confirm (or run with --force flag): ";

    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));

    if ($line !== 'DELETE ALL') {
        echo "\nOperation cancelled.\n";
        exit(0);
    }

    fclose($handle);
} else {
    echo "Running in FORCE mode (no confirmation required)...\n\n";
}

echo "\nStarting deletion process...\n\n";

DB::beginTransaction();

try {
    $deletedCounts = [];

    // 1. Delete Food Order Ingredients (uses ingredient_id, ingredient_batch_id)
    echo "1. Deleting Food Order Ingredients...\n";
    $count = FoodOrderIngredient::count();
    FoodOrderIngredient::query()->delete();
    $deletedCounts['food_order_ingredients'] = $count;
    echo "   Deleted: {$count} records\n\n";

    // 2. Delete Ingredient Stock Movements (uses ingredient_id, ingredient_batch_id)
    echo "2. Deleting Ingredient Stock Movements...\n";
    $count = IngredientStockMovement::count();
    IngredientStockMovement::query()->delete();
    $deletedCounts['ingredient_stock_movements'] = $count;
    echo "   Deleted: {$count} records\n\n";

    // 3. Delete Ingredient Batches (uses ingredient_id, ingredient_receipt_id)
    echo "3. Deleting Ingredient Batches...\n";
    $count = IngredientBatch::count();
    IngredientBatch::query()->delete();
    $deletedCounts['ingredient_batches'] = $count;
    echo "   Deleted: {$count} records\n\n";

    // 4. Delete Ingredient Receipts (uses ingredient_id)
    echo "4. Deleting Ingredient Receipts...\n";
    $count = IngredientReceipt::count();
    IngredientReceipt::query()->delete();
    $deletedCounts['ingredient_receipts'] = $count;
    echo "   Deleted: {$count} records\n\n";

    // 5. Delete Recipe Ingredients (uses ingredient_id, recipe_id)
    echo "5. Deleting Recipe Ingredients...\n";
    $count = RecipeIngredient::count();
    RecipeIngredient::query()->delete();
    $deletedCounts['recipe_ingredients'] = $count;
    echo "   Deleted: {$count} records\n\n";

    // 6. Delete Recipes (uses food_item_id)
    echo "6. Deleting Recipes...\n";
    $count = Recipe::count();
    Recipe::query()->delete();
    $deletedCounts['recipes'] = $count;
    echo "   Deleted: {$count} records\n\n";

    // 7. Update Kitchen Order Items to remove food_item_id references
    echo "7. Clearing food_item_id from Kitchen Order Items...\n";
    $count = KitchenOrderItem::whereNotNull('food_item_id')->count();
    KitchenOrderItem::query()->update(['food_item_id' => null]);
    $deletedCounts['kitchen_order_items_updated'] = $count;
    echo "   Updated: {$count} records\n\n";

    // 8. Delete Food Items
    echo "8. Deleting Food Items...\n";
    $count = FoodItem::count();
    FoodItem::query()->delete();
    $deletedCounts['food_items'] = $count;
    echo "   Deleted: {$count} records\n\n";

    // 9. Delete Ingredients (base table)
    echo "9. Deleting Ingredients...\n";
    $count = Ingredient::count();
    Ingredient::query()->delete();
    $deletedCounts['ingredients'] = $count;
    echo "   Deleted: {$count} records\n\n";

    DB::commit();

    echo "========================================\n";
    echo "Deletion Complete!\n";
    echo "========================================\n\n";
    echo "Summary:\n";
    foreach ($deletedCounts as $table => $count) {
        echo "  - {$table}: {$count} records\n";
    }
    echo "\nAll food-related data has been deleted successfully.\n";
    echo "You can now start fresh with your food management system.\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n========================================\n";
    echo "ERROR: Deletion failed!\n";
    echo "========================================\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n\n";
    exit(1);
}

