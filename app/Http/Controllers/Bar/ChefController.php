<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\KitchenOrderItem;
use App\Models\FoodItem;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Staff;
use App\Models\WaiterNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ChefController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Chef Dashboard - View Food Orders
     */
    public function dashboard()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to access chef dashboard.');
        }

        $ownerId = $this->getOwnerId();

        // Get orders with kitchen items grouped by status
        $pendingOrders = $this->getOrdersByKitchenStatus($ownerId, 'pending');
        $preparingOrders = $this->getOrdersByKitchenStatus($ownerId, 'preparing');
        $readyOrders = $this->getOrdersByKitchenStatus($ownerId, 'ready');
        $completedOrders = $this->getOrdersByKitchenStatus($ownerId, 'completed', 10); // Last 10 completed

        // Get statistics
        $stats = [
            'pending_count' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })->where('status', 'pending')->count(),
            'preparing_count' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })->where('status', 'preparing')->count(),
            'ready_count' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })->where('status', 'ready')->count(),
            'today_completed' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)->whereDate('created_at', today());
            })->where('status', 'completed')->count(),
        ];

        return view('bar.chef.dashboard', compact(
            'pendingOrders',
            'preparingOrders',
            'readyOrders',
            'completedOrders',
            'stats'
        ));
    }

    /**
     * Get orders grouped by kitchen item status
     */
    private function getOrdersByKitchenStatus($ownerId, $status, $limit = null)
    {
        $query = BarOrder::where('user_id', $ownerId)
            ->whereHas('kitchenOrderItems', function($q) use ($status) {
                $q->where('status', $status);
            })
            ->with(['kitchenOrderItems' => function($q) use ($status) {
                $q->where('status', $status);
            }, 'waiter', 'table'])
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(function($order) use ($status) {
            return [
                'order' => $order,
                'kitchen_items' => $order->kitchenOrderItems->where('status', $status),
            ];
        });
    }

    /**
     * Update Kitchen Order Item Status
     */
    public function updateItemStatus(Request $request, $kitchenOrderItem)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to update order status.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        // Get the kitchen order item
        $item = KitchenOrderItem::with('order')->findOrFail($kitchenOrderItem);
        
        // Verify order belongs to owner
        if ($item->order->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,completed',
        ]);

        $user = $this->getCurrentUser();

        DB::beginTransaction();
        try {
            $oldStatus = $item->status;
            $item->status = $validated['status'];

            // Set timestamps based on status
            if ($validated['status'] === 'preparing' && !$item->prepared_at) {
                // Check ingredient availability before starting preparation
                $ingredientDeductionService = new \App\Services\IngredientDeductionService();
                $availability = $ingredientDeductionService->checkIngredientAvailability($item);
                
                if (!$availability['available']) {
                    DB::rollBack();
                    $missingList = array_map(function($missing) {
                        return "{$missing['ingredient']}: Need {$missing['required']} {$missing['unit']}, Have {$missing['available']} {$missing['unit']}";
                    }, $availability['missing']);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'Insufficient ingredients available. ' . implode('; ', $missingList),
                        'missing_ingredients' => $availability['missing']
                    ], 400);
                }

                // Deduct ingredients using FIFO
                $deductionResult = $ingredientDeductionService->deductIngredients($item, $ownerId);
                
                if (!$deductionResult['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'error' => $deductionResult['message'],
                    ], 400);
                }

                $item->prepared_at = now();
                $item->prepared_by = $user ? $user->id : null;
            } elseif ($validated['status'] === 'ready' && !$item->ready_at) {
                $item->ready_at = now();
            }

            $item->save();

            // Send SMS notification to waiter and customer when item is marked as ready
            if ($validated['status'] === 'ready' && $oldStatus !== 'ready') {
                try {
                    $smsService = new \App\Services\WaiterSmsService();
                    $smsService->sendFoodReadyNotification($item);
                    
                    // Also send SMS to customer
                    $smsService->sendCustomerFoodReadyNotification($item);
                } catch (\Exception $e) {
                    // Log error but don't fail the status update
                    \Log::error('Failed to send food ready SMS notification', [
                        'item_id' => $item->id,
                        'order_id' => $item->order_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order item status updated successfully',
                'item' => $item->load('order.waiter', 'order.table'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark kitchen order item as taken (completed) - Chef marks when waiter picks up
     */
    public function markItemAsTaken(Request $request, $kitchenOrderItem)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to mark items as taken.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        // Get the kitchen order item
        $item = KitchenOrderItem::with('order')->findOrFail($kitchenOrderItem);
        
        // Verify order belongs to owner
        if ($item->order->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow marking as taken if item is ready
        if ($item->status !== 'ready') {
            return response()->json(['error' => 'Item must be ready before it can be marked as taken'], 400);
        }

        DB::beginTransaction();
        try {
            $item->status = 'completed';
            $item->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item marked as taken successfully',
                'item' => $item->load('order.table', 'order.waiter'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to mark item as taken', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to mark item as taken: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get Latest Kitchen Orders (for real-time updates)
     */
    public function getLatestOrders(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission to view orders.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $lastOrderId = $request->input('last_order_id', 0);

        // Get new pending kitchen orders
        $newOrders = BarOrder::where('user_id', $ownerId)
            ->where('id', '>', $lastOrderId)
            ->whereHas('kitchenOrderItems', function($q) {
                $q->where('status', 'pending');
            })
            ->with(['kitchenOrderItems' => function($q) {
                $q->where('status', 'pending');
            }, 'waiter', 'table'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'waiter_name' => $order->waiter ? $order->waiter->full_name : 'N/A',
                    'table_number' => $order->table ? $order->table->table_number : null,
                    'kitchen_items' => $order->kitchenOrderItems->map(function($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->food_item_name,
                            'variant' => $item->variant_name,
                            'quantity' => $item->quantity,
                            'instructions' => $item->special_instructions,
                        ];
                    })->toArray(),
                    'created_at' => $order->created_at->toDateTimeString(),
                ];
            });

        // Get the latest order ID
        $latestOrderId = BarOrder::where('user_id', $ownerId)
            ->whereHas('kitchenOrderItems')
            ->max('id') ?? 0;

        return response()->json([
            'success' => true,
            'new_orders' => $newOrders,
            'latest_order_id' => $latestOrderId,
        ]);
    }

    /**
     * Kitchen Display Screen (KDS) - Large View for Kitchen
     */
    public function kds()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to access kitchen display screen.');
        }

        $ownerId = $this->getOwnerId();

        // Get all active kitchen orders
        $pendingOrders = $this->getOrdersByKitchenStatus($ownerId, 'pending');
        $preparingOrders = $this->getOrdersByKitchenStatus($ownerId, 'preparing');
        $readyOrders = $this->getOrdersByKitchenStatus($ownerId, 'ready');

        return view('bar.chef.kds', compact(
            'pendingOrders',
            'preparingOrders',
            'readyOrders'
        ));
    }

    // ==================== FOOD ITEMS MANAGEMENT ====================

    /**
     * List all food items
     */
    public function foodItems()
    {
        if (!$this->hasPermission('products', 'view')) {
            abort(403, 'You do not have permission to view food items.');
        }

        $ownerId = $this->getOwnerId();
        $foodItems = FoodItem::where('user_id', $ownerId)
            ->with('recipe')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('bar.chef.food-items.index', compact('foodItems'));
    }

    /**
     * Show form to create food item
     */
    public function createFoodItem()
    {
        if (!$this->hasPermission('products', 'create')) {
            abort(403, 'You do not have permission to create food items.');
        }

        return view('bar.chef.food-items.create');
    }

    /**
     * Store new food item
     */
    public function storeFoodItem(Request $request)
    {
        if (!$this->hasPermission('products', 'create')) {
            abort(403, 'You do not have permission to create food items.');
        }

        $ownerId = $this->getOwnerId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variant_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'price' => 'required|numeric|min:0',
            'prep_time_minutes' => 'nullable|integer|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                // Create directory if it doesn't exist
                if (!file_exists(public_path('storage/food-items'))) {
                    mkdir(public_path('storage/food-items'), 0755, true);
                }
                $image->move(public_path('storage/food-items'), $imageName);
                $imagePath = 'food-items/' . $imageName;
            }

            FoodItem::create([
                'user_id' => $ownerId,
                'name' => $validated['name'],
                'variant_name' => $validated['variant_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'image' => $imagePath,
                'price' => $validated['price'],
                'prep_time_minutes' => $validated['prep_time_minutes'] ?? null,
                'is_available' => $validated['is_available'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            DB::commit();

            return redirect()->route('bar.chef.food-items')
                ->with('success', 'Food item created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create food item: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Show form to edit food item
     */
    public function editFoodItem(FoodItem $foodItem)
    {
        if (!$this->hasPermission('products', 'edit')) {
            abort(403, 'You do not have permission to edit food items.');
        }

        $ownerId = $this->getOwnerId();
        if ($foodItem->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        // Load recipe with ingredients
        $foodItem->load(['recipe.recipeIngredients.ingredient']);
        $ingredients = Ingredient::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('bar.chef.food-items.edit', compact('foodItem', 'ingredients'));
    }

    /**
     * Update food item
     */
    public function updateFoodItem(Request $request, FoodItem $foodItem)
    {
        if (!$this->hasPermission('products', 'edit')) {
            abort(403, 'You do not have permission to edit food items.');
        }

        $ownerId = $this->getOwnerId();
        if ($foodItem->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variant_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'price' => 'required|numeric|min:0',
            'prep_time_minutes' => 'nullable|integer|min:0',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($foodItem->image && file_exists(public_path('storage/' . $foodItem->image))) {
                    @unlink(public_path('storage/' . $foodItem->image));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                if (!file_exists(public_path('storage/food-items'))) {
                    mkdir(public_path('storage/food-items'), 0755, true);
                }
                $image->move(public_path('storage/food-items'), $imageName);
                $validated['image'] = 'food-items/' . $imageName;
            } else {
                unset($validated['image']);
            }

            $foodItem->update($validated);

            // Handle recipe if provided
            if ($request->has('recipe')) {
                $this->saveRecipe($request, $foodItem, $ownerId);
            }

            DB::commit();

            return redirect()->route('bar.chef.food-items')
                ->with('success', 'Food item updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update food item: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Delete food item
     */
    public function destroyFoodItem(FoodItem $foodItem)
    {
        if (!$this->hasPermission('products', 'delete')) {
            abort(403, 'You do not have permission to delete food items.');
        }

        $ownerId = $this->getOwnerId();
        if ($foodItem->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        DB::beginTransaction();
        try {
            // Delete image if exists
            if ($foodItem->image && file_exists(public_path('storage/' . $foodItem->image))) {
                @unlink(public_path('storage/' . $foodItem->image));
            }

            $foodItem->delete();

            DB::commit();

            return redirect()->route('bar.chef.food-items')
                ->with('success', 'Food item deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to delete food item: ' . $e->getMessage()]);
        }
    }

    /**
     * Show recipe management page for a food item
     */
    public function manageRecipe(FoodItem $foodItem)
    {
        if (!$this->hasPermission('products', 'edit')) {
            abort(403, 'You do not have permission to manage recipes.');
        }

        $ownerId = $this->getOwnerId();
        if ($foodItem->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        // Load recipe with ingredients
        $foodItem->load(['recipe.recipeIngredients.ingredient']);
        $ingredients = Ingredient::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('bar.chef.food-items.recipe', compact('foodItem', 'ingredients'));
    }

    /**
     * Save or update recipe for a food item
     */
    public function saveRecipe(Request $request, FoodItem $foodItem)
    {
        if (!$this->hasPermission('products', 'edit')) {
            abort(403, 'You do not have permission to manage recipes.');
        }

        $ownerId = $this->getOwnerId();
        if ($foodItem->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        $recipeData = $request->input('recipe', []);
        
        DB::beginTransaction();
        try {
            // Get or create recipe
            $recipe = Recipe::firstOrCreate(
                ['food_item_id' => $foodItem->id],
                [
                    'user_id' => $ownerId,
                    'name' => $recipeData['name'] ?? $foodItem->name,
                    'description' => $recipeData['description'] ?? null,
                    'instructions' => $recipeData['instructions'] ?? null,
                    'prep_time_minutes' => $recipeData['prep_time_minutes'] ?? null,
                    'cook_time_minutes' => $recipeData['cook_time_minutes'] ?? null,
                    'servings' => $recipeData['servings'] ?? 1,
                    'is_active' => isset($recipeData['is_active']) ? (bool)$recipeData['is_active'] : true,
                ]
            );

            // Update recipe
            $recipe->update([
                'name' => $recipeData['name'] ?? $recipe->name ?? $foodItem->name,
                'description' => $recipeData['description'] ?? $recipe->description,
                'instructions' => $recipeData['instructions'] ?? $recipe->instructions,
                'prep_time_minutes' => $recipeData['prep_time_minutes'] ?? $recipe->prep_time_minutes,
                'cook_time_minutes' => $recipeData['cook_time_minutes'] ?? $recipe->cook_time_minutes,
                'servings' => $recipeData['servings'] ?? $recipe->servings ?? 1,
                'is_active' => isset($recipeData['is_active']) ? (bool)$recipeData['is_active'] : $recipe->is_active,
            ]);

            // Handle recipe ingredients
            if (isset($recipeData['ingredients']) && is_array($recipeData['ingredients'])) {
                // Delete existing recipe ingredients
                RecipeIngredient::where('recipe_id', $recipe->id)->delete();

                // Add new recipe ingredients
                foreach ($recipeData['ingredients'] as $ingredientData) {
                    if (!empty($ingredientData['ingredient_id']) && !empty($ingredientData['quantity_required'])) {
                        RecipeIngredient::create([
                            'recipe_id' => $recipe->id,
                            'ingredient_id' => $ingredientData['ingredient_id'],
                            'quantity_required' => $ingredientData['quantity_required'],
                            'unit' => $ingredientData['unit'] ?? 'g',
                            'notes' => $ingredientData['notes'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('bar.chef.food-items')
                ->with('success', 'Recipe saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to save recipe: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Save or update recipe for a food item (private method for use in updateFoodItem)
     */
    private function saveRecipeInEdit(Request $request, FoodItem $foodItem, $ownerId)
    {
        $recipeData = $request->input('recipe', []);
        
        // If recipe section is empty, don't create/update
        if (empty($recipeData) || (!isset($recipeData['name']) && !isset($recipeData['ingredients']))) {
            return;
        }

        // Get or create recipe
        $recipe = Recipe::firstOrCreate(
            ['food_item_id' => $foodItem->id],
            [
                'user_id' => $ownerId,
                'name' => $recipeData['name'] ?? $foodItem->name,
                'description' => $recipeData['description'] ?? null,
                'instructions' => $recipeData['instructions'] ?? null,
                'prep_time_minutes' => $recipeData['prep_time_minutes'] ?? null,
                'cook_time_minutes' => $recipeData['cook_time_minutes'] ?? null,
                'servings' => $recipeData['servings'] ?? 1,
                'is_active' => isset($recipeData['is_active']) ? (bool)$recipeData['is_active'] : true,
            ]
        );

        // Update recipe if it exists
        if ($recipe->wasRecentlyCreated === false) {
            $recipe->update([
                'name' => $recipeData['name'] ?? $recipe->name ?? $foodItem->name,
                'description' => $recipeData['description'] ?? $recipe->description,
                'instructions' => $recipeData['instructions'] ?? $recipe->instructions,
                'prep_time_minutes' => $recipeData['prep_time_minutes'] ?? $recipe->prep_time_minutes,
                'cook_time_minutes' => $recipeData['cook_time_minutes'] ?? $recipe->cook_time_minutes,
                'servings' => $recipeData['servings'] ?? $recipe->servings ?? 1,
                'is_active' => isset($recipeData['is_active']) ? (bool)$recipeData['is_active'] : $recipe->is_active,
            ]);
        }

        // Handle recipe ingredients
        if (isset($recipeData['ingredients']) && is_array($recipeData['ingredients'])) {
            // Delete existing recipe ingredients
            RecipeIngredient::where('recipe_id', $recipe->id)->delete();

            // Add new recipe ingredients
            foreach ($recipeData['ingredients'] as $ingredientData) {
                if (!empty($ingredientData['ingredient_id']) && !empty($ingredientData['quantity_required'])) {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'ingredient_id' => $ingredientData['ingredient_id'],
                        'quantity_required' => $ingredientData['quantity_required'],
                        'unit' => $ingredientData['unit'] ?? 'g',
                        'notes' => $ingredientData['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    // ==================== INGREDIENTS MANAGEMENT ====================

    /**
     * List all ingredients
     */
    public function ingredients()
    {
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view ingredients.');
        }

        $ownerId = $this->getOwnerId();
        $ingredients = Ingredient::where('user_id', $ownerId)
            ->orderBy('name')
            ->paginate(20);

        return view('bar.chef.ingredients.index', compact('ingredients'));
    }

    /**
     * Show form to create ingredient
     */
    public function createIngredient()
    {
        if (!$this->hasPermission('inventory', 'create')) {
            abort(403, 'You do not have permission to create ingredients.');
        }

        return view('bar.chef.ingredients.create');
    }

    /**
     * Store new ingredient
     */
    public function storeIngredient(Request $request)
    {
        if (!$this->hasPermission('inventory', 'create')) {
            abort(403, 'You do not have permission to create ingredients.');
        }

        $ownerId = $this->getOwnerId();

        // Check if ingredients array is provided (multiple ingredients) or single ingredient
        if ($request->has('ingredients') && is_array($request->ingredients)) {
            // Filter out empty ingredients (in case user added forms but didn't fill them)
            $ingredients = array_filter($request->ingredients, function($ingredient) {
                return !empty($ingredient['name']) && !empty($ingredient['unit']);
            });

            if (empty($ingredients)) {
                return back()->withErrors(['ingredients' => 'At least one ingredient must be filled in.'])->withInput();
            }

            // Multiple ingredients
            $validated = $request->validate([
                'ingredients' => 'required|array|min:1',
                'ingredients.*.name' => 'required|string|max:255',
                'ingredients.*.unit' => 'required|string|max:50',
                'ingredients.*.min_stock_level' => 'required|numeric|min:0',
                'ingredients.*.is_active' => 'nullable|boolean',
            ]);

            $createdCount = 0;
            $errors = [];

            DB::beginTransaction();
            try {
                foreach ($validated['ingredients'] as $index => $ingredientData) {
                    // Skip if name or unit is empty
                    if (empty($ingredientData['name']) || empty($ingredientData['unit'])) {
                        continue;
                    }

                    try {
                        Ingredient::create([
                            'user_id' => $ownerId,
                            'name' => trim($ingredientData['name']),
                            'unit' => trim($ingredientData['unit']),
                            'current_stock' => 0, // Stock will be added via receipts
                            'min_stock_level' => $ingredientData['min_stock_level'] ?? 0,
                            'max_stock_level' => null,
                            'location' => null, // Location will be set in receipts
                            'cost_per_unit' => null, // Cost will be set in receipts
                            'supplier_info' => null, // Suppliers are managed separately
                            'expiry_date' => null, // Expiry date will be set when receiving stock
                            'is_active' => isset($ingredientData['is_active']) && $ingredientData['is_active'] ? true : false,
                        ]);
                        $createdCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Failed to create ingredient '{$ingredientData['name']}': " . $e->getMessage();
                    }
                }

                if (!empty($errors)) {
                    DB::rollBack();
                    return back()->withErrors(['ingredients' => implode('; ', $errors)])->withInput();
                }

                DB::commit();

                $message = $createdCount === 1 
                    ? 'Ingredient created successfully.' 
                    : "{$createdCount} ingredients created successfully.";

                return redirect()->route('bar.chef.ingredients')
                    ->with('success', $message);
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Failed to create ingredients: ' . $e->getMessage()])->withInput();
            }
        } else {
            // Single ingredient (backward compatibility)
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'unit' => 'required|string|max:50',
                'min_stock_level' => 'required|numeric|min:0',
                'is_active' => 'boolean',
            ]);

            Ingredient::create([
                'user_id' => $ownerId,
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'current_stock' => 0, // Stock will be added via receipts
                'min_stock_level' => $validated['min_stock_level'],
                'max_stock_level' => null,
                'location' => null, // Location will be set in receipts
                'cost_per_unit' => null, // Cost will be set in receipts
                'supplier_info' => null, // Suppliers are managed separately
                'expiry_date' => null, // Expiry date will be set when receiving stock
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return redirect()->route('bar.chef.ingredients')
                ->with('success', 'Ingredient created successfully.');
        }
    }

    /**
     * Show form to edit ingredient
     */
    public function editIngredient(Ingredient $ingredient)
    {
        if (!$this->hasPermission('inventory', 'edit')) {
            abort(403, 'You do not have permission to edit ingredients.');
        }

        $ownerId = $this->getOwnerId();
        if ($ingredient->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        return view('bar.chef.ingredients.edit', compact('ingredient'));
    }

    /**
     * Update ingredient
     */
    public function updateIngredient(Request $request, Ingredient $ingredient)
    {
        if (!$this->hasPermission('inventory', 'edit')) {
            abort(403, 'You do not have permission to edit ingredients.');
        }

        $ownerId = $this->getOwnerId();
        if ($ingredient->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'min_stock_level' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // Only update allowed fields - stock, location, cost, and supplier info are managed through receipts
        $ingredient->update([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'min_stock_level' => $validated['min_stock_level'],
            'expiry_date' => $validated['expiry_date'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('bar.chef.ingredients')
            ->with('success', 'Ingredient updated successfully.');
    }

    /**
     * Delete ingredient
     */
    public function destroyIngredient(Ingredient $ingredient)
    {
        if (!$this->hasPermission('inventory', 'delete')) {
            abort(403, 'You do not have permission to delete ingredients.');
        }

        $ownerId = $this->getOwnerId();
        if ($ingredient->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        // Check for related records that prevent deletion
        $hasBatches = $ingredient->batches()->count() > 0;
        $hasReceipts = $ingredient->receipts()->count() > 0;
        $hasStockMovements = $ingredient->stockMovements()->count() > 0;
        $hasFoodOrderIngredients = $ingredient->foodOrderIngredients()->count() > 0;
        $hasRecipes = $ingredient->recipes()->count() > 0;

        if ($hasBatches || $hasReceipts || $hasStockMovements || $hasFoodOrderIngredients || $hasRecipes) {
            $reasons = [];
            if ($hasBatches) $reasons[] = 'ingredient batches';
            if ($hasReceipts) $reasons[] = 'ingredient receipts';
            if ($hasStockMovements) $reasons[] = 'stock movements';
            if ($hasFoodOrderIngredients) $reasons[] = 'food order records';
            if ($hasRecipes) $reasons[] = 'recipes';

            $message = 'Cannot delete ingredient "' . $ingredient->name . '" because it has associated ' . implode(', ', $reasons) . '. ';
            $message .= 'Please remove or update these records first, or deactivate the ingredient instead.';

            return redirect()->route('bar.chef.ingredients')
                ->with('error', $message);
        }

        $ingredient->delete();

        return redirect()->route('bar.chef.ingredients')
            ->with('success', 'Ingredient deleted successfully.');
    }

    // ==================== RESTAURANT REPORTS ====================

    /**
     * Restaurant Reports & Analytics
     */
    /**
     * Chef Reconciliation Page - View waiter reconciliations (focused on food orders)
     */
    public function reconciliation(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view reconciliations.');
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', now()->format('Y-m-d'));

        // Check if current user is accountant (should see all orders across all owners)
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Get all waiters with their food order sales for the date
        $waitersQuery = \App\Models\Staff::query()
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
            });
        
        // If not accountant, filter by owner
        if (!$isAccountant) {
            $waitersQuery->where('user_id', $ownerId);
        }
        
        $waiters = $waitersQuery
            ->with(['dailyReconciliations' => function($q) use ($date) {
                $q->where('reconciliation_date', $date)
                  ->where('reconciliation_type', 'food'); // Only get food reconciliations
            }])
            ->get()
            ->map(function($waiter) use ($ownerId, $date, $isAccountant) {
                // Get all orders for this waiter
                $ordersQuery = BarOrder::query()
                    ->where('waiter_id', $waiter->id);
                
                // If not accountant, filter by owner
                if (!$isAccountant) {
                    $ordersQuery->where('user_id', $ownerId);
                }
                
                $orders = $ordersQuery
                    ->whereDate('created_at', $date)
                    ->with(['items', 'kitchenOrderItems', 'table', 'orderPayments'])
                    ->get();
                
                // Separate food orders from bar orders
                // Food orders: orders that have kitchenOrderItems
                $foodOrders = $orders->filter(function($order) {
                    return $order->kitchenOrderItems && $order->kitchenOrderItems->count() > 0;
                });
                
                // Bar orders: orders that have items (drinks) - may also have food
                $barOrders = $orders->filter(function($order) {
                    return $order->items && $order->items->count() > 0;
                });
                
                // Calculate food sales from kitchenOrderItems only
                $foodSales = $foodOrders->sum(function($order) {
                    return $order->kitchenOrderItems->sum('total_price');
                });
                
                // Calculate bar sales from items (drinks) only
                $barSales = $barOrders->sum(function($order) {
                    return $order->items->sum('total_price');
                });
                
                // For Chef reconciliation: only count food sales
                $totalSales = $foodSales;
                
                // Count orders
                $foodOrdersCount = $foodOrders->count();
                $barOrdersCount = $barOrders->count();
                
                // Check for unpaid served food orders
                $unpaidFoodOrders = $foodOrders->filter(function($order) {
                    return $order->status === 'served' && $order->payment_status !== 'paid';
                });
                $hasUnpaidOrders = $unpaidFoodOrders->count() > 0;
                
                // Calculate total recorded amount from OrderPayments (recorded by waiters)
                // This shows what waiters have recorded, regardless of reconciliation status
                $totalRecordedAmount = $foodOrders->filter(function($order) {
                    return $order->status === 'served' && $order->orderPayments && $order->orderPayments->count() > 0;
                })->sum(function($order) {
                    // Sum all OrderPayments for this order (recorded payments)
                    return $order->orderPayments->sum('amount');
                });
                
                // Calculate total paid amount (only orders that have been reconciled/submitted)
                $totalPaidAmount = $foodOrders->filter(function($order) {
                    return $order->status === 'served' && $order->payment_status === 'paid';
                })->sum(function($order) {
                    // Only sum the food items amount
                    return $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0;
                });
                
                // Payment collection from food orders only
                $cashCollected = $foodOrders->where('payment_method', 'cash')->sum('paid_amount') + 
                               $foodOrders->sum(function($order) {
                                   return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                               });
                $mobileMoneyCollected = $foodOrders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                                      $foodOrders->sum(function($order) {
                                          return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                                      });
                
                // Get food-specific reconciliation record
                $reconciliation = $waiter->dailyReconciliations->first();
                
                // Submitted amount: use food reconciliation if exists, otherwise calculate from paid food orders
                if ($reconciliation && $reconciliation->reconciliation_type === 'food') {
                    $submittedAmount = $reconciliation->submitted_amount;
                } else {
                    // Calculate from food orders that have been marked as paid
                    $submittedAmount = $foodOrders->filter(function($order) {
                        return $order->status === 'served' && $order->payment_status === 'paid';
                    })->sum(function($order) {
                        // Only sum the food items amount (kitchenOrderItems)
                        return $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0;
                    });
                }
                
                // Show submit button if there are food orders and no food-specific reconciliation has been submitted
                // This allows chef to submit payment even if all orders are marked as paid
                $canSubmitPayment = $foodOrdersCount > 0 && (!$reconciliation || $reconciliation->submitted_amount == 0);
                
                // Calculate difference: Submitted - Expected
                // If nothing submitted yet, use recorded amount for difference calculation
                // This shows the shortfall more accurately
                if ($submittedAmount == 0 && $totalRecordedAmount > 0) {
                    $difference = $totalRecordedAmount - $totalSales;
                } else {
                    $difference = $submittedAmount - $totalSales;
                }
                
                // Determine status intelligently
                $status = 'pending';
                if ($reconciliation) {
                    // If reconciliation exists, use its status
                    $status = $reconciliation->status;
                } else {
                    // No reconciliation record - determine status based on payment
                    if ($hasUnpaidOrders) {
                        $status = 'pending'; // Still has unpaid orders
                    } else if ($totalPaidAmount > 0 && abs($difference) < 0.01) {
                        $status = 'paid'; // All orders paid and amounts match
                    } else if ($totalPaidAmount > 0) {
                        $status = 'partial'; // Some orders paid but amounts don't match
                    }
                }
                
                return [
                    'waiter' => $waiter,
                    'total_sales' => $totalSales, // Food sales only
                    'food_sales' => $foodSales,
                    'bar_sales' => $barSales,
                    'food_orders_count' => $foodOrdersCount,
                    'bar_orders_count' => $barOrdersCount,
                    'total_orders' => $foodOrdersCount, // Food orders count only
                    'has_unpaid_orders' => $hasUnpaidOrders,
                    'can_submit_payment' => $canSubmitPayment, // Show submit button if there are food orders
                    'cash_collected' => $cashCollected,
                    'mobile_money_collected' => $mobileMoneyCollected,
                    'expected_amount' => $totalSales, // Expected = food sales only
                    'recorded_amount' => $totalRecordedAmount, // Amount recorded by waiter (from OrderPayments)
                    'submitted_amount' => $submittedAmount, // Amount submitted/reconciled by chef
                    'difference' => $difference, // Always calculate difference
                    'status' => $status,
                    'orders' => $foodOrders, // Only food orders
                    'reconciliation' => $reconciliation
                ];
            })
            ->filter(function($data) {
                return $data['food_orders_count'] > 0; // Only show waiters with food orders
            })
            ->sortByDesc('food_sales') // Sort by food sales (most relevant for chef)
            ->values();

        return view('bar.chef.reconciliation', compact('waiters', 'date'));
    }

    /**
     * Mark all food orders as paid for a waiter after reconciliation verification
     */
    public function markAllFoodOrdersPaid(Request $request)
    {
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to mark orders as paid.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        $validated = $request->validate([
            'waiter_id' => 'required|exists:staff,id',
            'date' => 'required|date',
            'submitted_amount' => 'nullable|numeric|min:0',
        ]);

        // Check if current user is accountant
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Verify waiter belongs to owner (unless accountant)
        $waiterQuery = Staff::where('id', $validated['waiter_id']);
        if (!$isAccountant) {
            $waiterQuery->where('user_id', $ownerId);
        }
        $waiter = $waiterQuery->first();

        if (!$waiter) {
            return response()->json(['error' => 'Waiter not found'], 404);
        }

        // Get all served food orders for this waiter on this date that are not yet paid
        // Chef only marks food orders as paid, not bar orders
        $ordersQuery = BarOrder::query()
            ->where('waiter_id', $waiter->id)
            ->whereDate('created_at', $validated['date'])
            ->where('status', 'served')
            ->where('payment_status', '!=', 'paid')
            ->whereHas('kitchenOrderItems') // Only orders with food items
            ->with('kitchenOrderItems');
        
        // If not accountant, filter by owner
        if (!$isAccountant) {
            $ordersQuery->where('user_id', $ownerId);
        }
        
        $orders = $ordersQuery->get();

        // Calculate expected amount (total food sales for this waiter on this date)
        $expectedOrdersQuery = BarOrder::query()
            ->where('waiter_id', $waiter->id);
        if (!$isAccountant) {
            $expectedOrdersQuery->where('user_id', $ownerId);
        }
        $expectedAmount = $expectedOrdersQuery
            ->whereDate('created_at', $validated['date'])
            ->where('status', 'served')
            ->whereHas('kitchenOrderItems')
            ->with('kitchenOrderItems')
            ->get()
            ->sum(function($order) {
                return $order->kitchenOrderItems->sum('total_price');
            });

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'No unpaid served food orders found for this waiter on this date.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $updatedCount = 0;

            foreach ($orders as $order) {
                // Only mark as paid if order has food items
                if ($order->kitchenOrderItems->count() > 0) {
                    $order->payment_status = 'paid';
                    $order->paid_amount = $order->total_amount;
                    $order->paid_by_waiter_id = $waiter->id;
                    $order->save();
                    
                    $totalAmount += $order->total_amount;
                    $updatedCount++;
                }
            }

            // Check if food reconciliation already exists
            $existingReconciliation = \App\Models\WaiterDailyReconciliation::where('user_id', $ownerId)
                ->where('waiter_id', $waiter->id)
                ->where('reconciliation_date', $validated['date'])
                ->where('reconciliation_type', 'food')
                ->first();
            
            $previousSubmittedAmount = $existingReconciliation ? $existingReconciliation->submitted_amount : 0;
            
            // Use submitted_amount if provided, otherwise calculate from OrderPayments (recorded payments)
            if (isset($validated['submitted_amount'])) {
                // If there's already a submitted amount, add the new amount to it
                $newSubmittedAmount = $validated['submitted_amount'];
                $submittedAmount = $previousSubmittedAmount + $newSubmittedAmount;
            } else {
                // Calculate submitted amount from OrderPayments (what waiters have recorded)
                $allOrdersWithPaymentsQuery = BarOrder::query()
                    ->where('waiter_id', $waiter->id)
                    ->whereDate('created_at', $validated['date'])
                    ->where('status', 'served')
                    ->whereHas('kitchenOrderItems') // Only food orders
                    ->whereHas('orderPayments') // Must have recorded payments
                    ->with(['kitchenOrderItems', 'orderPayments']);
                
                // If not accountant, filter by owner
                if (!$isAccountant) {
                    $allOrdersWithPaymentsQuery->where('user_id', $ownerId);
                }
                
                $calculatedSubmittedAmount = $allOrdersWithPaymentsQuery
                    ->get()
                    ->sum(function($order) {
                        // Sum all OrderPayments (recorded payments) for this order
                        return $order->orderPayments->sum('amount');
                    });
                
                // Add to previous submitted amount if exists
                $submittedAmount = $previousSubmittedAmount + $calculatedSubmittedAmount;
            }
            
            // Ensure submitted amount doesn't exceed expected amount
            $submittedAmount = min($submittedAmount, $expectedAmount);
            
            // Calculate difference
            $difference = $submittedAmount - $expectedAmount;
            
            // Create or update food-specific reconciliation record
            $reconciliation = \App\Models\WaiterDailyReconciliation::updateOrCreate(
                [
                    'user_id' => $ownerId,
                    'waiter_id' => $waiter->id,
                    'reconciliation_date' => $validated['date'],
                    'reconciliation_type' => 'food', // Food-specific reconciliation
                ],
                [
                    'expected_amount' => $expectedAmount,
                    'submitted_amount' => $submittedAmount,
                    'difference' => $difference,
                    'status' => $submittedAmount >= $expectedAmount ? 'submitted' : 'partial',
                    'submitted_at' => now(),
                    'cash_collected' => $foodOrders->where('payment_method', 'cash')->sum('paid_amount') + 
                                       $foodOrders->sum(function($order) {
                                           return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                                       }),
                    'mobile_money_collected' => $foodOrders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                                               $foodOrders->sum(function($order) {
                                                   return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                                               }),
                    'total_sales' => $expectedAmount,
                ]
            );

            DB::commit();

            \Log::info('Bulk mark food orders as paid', [
                'waiter_id' => $waiter->id,
                'date' => $validated['date'],
                'orders_count' => $updatedCount,
                'total_amount' => $totalAmount
            ]);

            // Create notification for waiter
            try {
                WaiterNotification::create([
                    'waiter_id' => $waiter->id,
                    'type' => 'payment_recorded',
                    'title' => 'Food Orders Marked as Paid',
                    'message' => "Chef has marked {$updatedCount} food order(s) as paid for " . \Carbon\Carbon::parse($validated['date'])->format('M d, Y') . ". Total amount: TSh " . number_format($submittedAmount, 0),
                    'data' => [
                        'date' => $validated['date'],
                        'orders_count' => $updatedCount,
                        'total_amount' => $submittedAmount,
                        'order_type' => 'food',
                        'marked_by' => 'chef',
                    ],
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create notification', [
                    'waiter_id' => $waiter->id,
                    'error' => $e->getMessage()
                ]);
            }

            $message = "Successfully marked {$updatedCount} order(s) as paid.";
            if ($submittedAmount < $expectedAmount) {
                $message .= " Submitted amount: TSh " . number_format($submittedAmount, 0) . " (Expected: TSh " . number_format($expectedAmount, 0) . ")";
            } else {
                $message .= " Total: TSh " . number_format($totalAmount, 0);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'orders_count' => $updatedCount,
                'total_amount' => $totalAmount,
                'submitted_amount' => $submittedAmount,
                'expected_amount' => $expectedAmount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to mark all food orders as paid', [
                'waiter_id' => $waiter->id,
                'date' => $validated['date'],
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to mark orders as paid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get waiter's food orders for a specific date (AJAX) - Chef reconciliation only shows food orders
     */
    public function getWaiterFoodOrders(Request $request, Staff $waiter)
    {
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', now()->format('Y-m-d'));

        // Check if current user is accountant
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Verify waiter belongs to owner (unless accountant)
        if (!$isAccountant && $waiter->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Return only food orders (orders with kitchenOrderItems) for chef reconciliation
        $ordersQuery = BarOrder::query()
            ->where('waiter_id', $waiter->id)
            ->whereHas('kitchenOrderItems'); // Only orders with food items
        
        // If not accountant, filter by owner
        if (!$isAccountant) {
            $ordersQuery->where('user_id', $ownerId);
        }
        
        $orders = $ordersQuery
            ->whereDate('created_at', $date)
            ->with(['kitchenOrderItems', 'table', 'orderPayments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    public function reports()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view restaurant reports.');
        }

        $ownerId = $this->getOwnerId();

        // Get food order statistics for last 30 days
        $foodOrdersData = KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('payment_status', 'paid')
                  ->where('created_at', '>=', now()->subDays(30));
        })
        ->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_price) as revenue'),
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('COUNT(*) as orders')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Get top selling food items
        $topFoodItems = KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('payment_status', 'paid')
                  ->where('created_at', '>=', now()->subDays(30));
        })
        ->select(
            'food_item_name',
            'variant_name',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(total_price) as total_revenue')
        )
        ->groupBy('food_item_name', 'variant_name')
        ->orderBy('total_quantity', 'desc')
        ->limit(10)
        ->get();

        // Calculate total revenue from food items
        $totalFoodRevenue = KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('payment_status', 'paid')
                  ->where('created_at', '>=', now()->subDays(30));
        })
        ->sum('total_price');

        // Get total food orders count
        $totalFoodOrders = KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('payment_status', 'paid')
                  ->where('created_at', '>=', now()->subDays(30));
        })
        ->distinct('order_id')
        ->count('order_id');

        // Get kitchen performance metrics
        $kitchenStats = [
            'total_pending' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })->where('status', 'pending')->count(),
            'total_preparing' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })->where('status', 'preparing')->count(),
            'total_ready' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })->where('status', 'ready')->count(),
            'total_completed' => KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)
                      ->where('payment_status', 'paid')
                      ->where('created_at', '>=', now()->subDays(30));
            })->where('status', 'completed')->count(),
        ];

        // Revenue by day of week
        $revenueByDay = KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('payment_status', 'paid')
                  ->where('created_at', '>=', now()->subDays(30));
        })
        ->select(
            DB::raw('DAYNAME(created_at) as day_name'),
            DB::raw('DAYOFWEEK(created_at) as day_number'),
            DB::raw('SUM(total_price) as revenue')
        )
        ->groupBy('day_name', 'day_number')
        ->orderBy('day_number')
        ->get();

        // Get average preparation time (for completed items)
        $avgPrepTime = KitchenOrderItem::whereHas('order', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('payment_status', 'paid')
                  ->where('created_at', '>=', now()->subDays(30));
        })
        ->whereNotNull('prepared_at')
        ->whereNotNull('ready_at')
        ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, prepared_at, ready_at)) as avg_minutes'))
        ->first();

        $avgPrepTimeMinutes = $avgPrepTime ? round($avgPrepTime->avg_minutes) : 0;

        return view('bar.chef.reports', compact(
            'foodOrdersData',
            'topFoodItems',
            'totalFoodRevenue',
            'totalFoodOrders',
            'kitchenStats',
            'revenueByDay',
            'avgPrepTimeMinutes'
        ));
    }

    /**
     * List ingredient stock movements
     */
    public function ingredientStockMovements(Request $request)
    {
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view ingredient stock movements.');
        }

        $ownerId = $this->getOwnerId();
        
        $query = \App\Models\IngredientStockMovement::where('user_id', $ownerId)
            ->with(['ingredient', 'ingredientBatch', 'createdByStaff'])
            ->orderBy('created_at', 'desc');

        // Filter by ingredient if provided
        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->ingredient_id);
        }

        // Filter by movement type if provided
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // Filter by date range if provided
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->paginate(20);
        $ingredients = Ingredient::where('user_id', $ownerId)->where('is_active', true)->orderBy('name')->get();

        return view('bar.chef.ingredient-stock-movements.index', compact('movements', 'ingredients'));
    }

    /**
     * List ingredient batches
     */
    public function ingredientBatches(Request $request)
    {
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view ingredient batches.');
        }

        $ownerId = $this->getOwnerId();
        
        // Load all batches for client-side filtering (no server-side filtering needed)
        $batches = \App\Models\IngredientBatch::where('user_id', $ownerId)
            ->with(['ingredient', 'receipt'])
            ->orderBy('received_date', 'desc')
            ->orderBy('expiry_date', 'asc')
            ->get();

        $ingredients = Ingredient::where('user_id', $ownerId)->where('is_active', true)->orderBy('name')->get();

        return view('bar.chef.ingredient-batches.index', compact('batches', 'ingredients'));
    }
}
