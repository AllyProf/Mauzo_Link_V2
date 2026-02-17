<?php

namespace App\Services;

use App\Models\KitchenOrderItem;
use App\Models\Ingredient;
use App\Models\IngredientBatch;
use App\Models\FoodOrderIngredient;
use App\Models\IngredientStockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IngredientDeductionService
{
    /**
     * Deduct ingredients for a kitchen order item based on its recipe.
     * Uses FIFO (First In First Out) for ingredient batches.
     * 
     * @param KitchenOrderItem $kitchenOrderItem
     * @param int $ownerId
     * @return array ['success' => bool, 'message' => string, 'ingredients_used' => array]
     */
    public function deductIngredients(KitchenOrderItem $kitchenOrderItem, $ownerId)
    {
        // Check if food item has a recipe
        if (!$kitchenOrderItem->foodItem) {
            return [
                'success' => false,
                'message' => 'Food item not found or not linked to kitchen order item.',
                'ingredients_used' => []
            ];
        }

        $foodItem = $kitchenOrderItem->foodItem;
        $recipe = $foodItem->recipe;

        if (!$recipe) {
            return [
                'success' => false,
                'message' => "No recipe found for food item: {$foodItem->name}. Ingredients cannot be deducted.",
                'ingredients_used' => []
            ];
        }

        // Get recipe ingredients
        $recipeIngredients = $recipe->recipeIngredients()->with('ingredient')->get();

        if ($recipeIngredients->isEmpty()) {
            return [
                'success' => false,
                'message' => "Recipe for {$foodItem->name} has no ingredients defined.",
                'ingredients_used' => []
            ];
        }

        $ingredientsUsed = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($recipeIngredients as $recipeIngredient) {
                $ingredient = $recipeIngredient->ingredient;
                $quantityRequired = $recipeIngredient->quantity_required * $kitchenOrderItem->quantity; // Multiply by order quantity
                $unit = $recipeIngredient->unit;

                // Check if ingredient has available stock
                $availableBatches = $ingredient->availableBatches()->get();
                $totalAvailable = $availableBatches->sum('remaining_quantity');

                if ($totalAvailable < $quantityRequired) {
                    $errors[] = "Insufficient stock for {$ingredient->name}. Required: {$quantityRequired} {$unit}, Available: {$totalAvailable} {$unit}";
                    continue;
                }

                // Use FIFO: deduct from oldest batches first
                $remainingNeeded = $quantityRequired;
                $batchesUsed = [];

                foreach ($availableBatches as $batch) {
                    if ($remainingNeeded <= 0) {
                        break;
                    }

                    $quantityFromBatch = min($remainingNeeded, $batch->remaining_quantity);
                    $costAtTime = $batch->cost_per_unit;
                    $totalCost = $quantityFromBatch * $costAtTime;

                    // Create food order ingredient record
                    $foodOrderIngredient = FoodOrderIngredient::create([
                        'kitchen_order_item_id' => $kitchenOrderItem->id,
                        'ingredient_id' => $ingredient->id,
                        'ingredient_batch_id' => $batch->id,
                        'quantity_used' => $quantityFromBatch,
                        'unit' => $unit,
                        'cost_at_time' => $costAtTime,
                        'total_cost' => $totalCost,
                    ]);

                    // Update batch remaining quantity
                    $batch->remaining_quantity -= $quantityFromBatch;
                    
                    // Update batch status if depleted
                    if ($batch->remaining_quantity <= 0) {
                        $batch->status = 'depleted';
                    }
                    $batch->save();

                    // Update ingredient current stock
                    $ingredient->current_stock -= $quantityFromBatch;
                    $ingredient->save();

                    // Create stock movement record
                    IngredientStockMovement::create([
                        'user_id' => $ownerId,
                        'ingredient_id' => $ingredient->id,
                        'ingredient_batch_id' => $batch->id,
                        'movement_type' => 'usage',
                        'quantity' => -$quantityFromBatch, // Negative for usage
                        'unit' => $unit,
                        'from_location' => $batch->location ?? 'kitchen',
                        'to_location' => null,
                        'reference_type' => KitchenOrderItem::class,
                        'reference_id' => $kitchenOrderItem->id,
                        'notes' => "Used for {$foodItem->name} (Order: {$kitchenOrderItem->order->order_number})",
                        'created_by' => session('staff_id'),
                    ]);

                    $batchesUsed[] = [
                        'batch_id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'quantity' => $quantityFromBatch,
                        'cost' => $totalCost,
                    ];

                    $remainingNeeded -= $quantityFromBatch;
                }

                $ingredientsUsed[] = [
                    'ingredient_id' => $ingredient->id,
                    'ingredient_name' => $ingredient->name,
                    'quantity_used' => $quantityRequired,
                    'unit' => $unit,
                    'batches' => $batchesUsed,
                ];
            }

            if (!empty($errors)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => implode('; ', $errors),
                    'ingredients_used' => []
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Ingredients deducted successfully.',
                'ingredients_used' => $ingredientsUsed
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deduct ingredients', [
                'kitchen_order_item_id' => $kitchenOrderItem->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to deduct ingredients: ' . $e->getMessage(),
                'ingredients_used' => []
            ];
        }
    }

    /**
     * Check if ingredients are available for a kitchen order item.
     * 
     * @param KitchenOrderItem $kitchenOrderItem
     * @return array ['available' => bool, 'missing' => array]
     */
    public function checkIngredientAvailability(KitchenOrderItem $kitchenOrderItem)
    {
        if (!$kitchenOrderItem->foodItem || !$kitchenOrderItem->foodItem->recipe) {
            return [
                'available' => false,
                'missing' => ['No recipe found for this food item.']
            ];
        }

        $recipe = $kitchenOrderItem->foodItem->recipe;
        $recipeIngredients = $recipe->recipeIngredients()->with('ingredient')->get();
        $missing = [];

        foreach ($recipeIngredients as $recipeIngredient) {
            $ingredient = $recipeIngredient->ingredient;
            $quantityRequired = $recipeIngredient->quantity_required * $kitchenOrderItem->quantity;
            $unit = $recipeIngredient->unit;

            $availableBatches = $ingredient->availableBatches()->get();
            $totalAvailable = $availableBatches->sum('remaining_quantity');

            if ($totalAvailable < $quantityRequired) {
                $missing[] = [
                    'ingredient' => $ingredient->name,
                    'required' => $quantityRequired,
                    'available' => $totalAvailable,
                    'unit' => $unit,
                ];
            }
        }

        return [
            'available' => empty($missing),
            'missing' => $missing
        ];
    }
}

