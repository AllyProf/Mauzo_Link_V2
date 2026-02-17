<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\ProductVariant;
use App\Models\BarOrder;
use App\Models\OrderItem;
use App\Models\KitchenOrderItem;
use App\Models\FoodItem;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaiterController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Waiter Dashboard - View Counter Stock
     */
    public function dashboard()
    {
        // Check if user is a waiter (staff with waiter role)
        $staff = $this->getCurrentStaff();
        
        if (!$staff || !$staff->is_active) {
            abort(403, 'You must be logged in as an active waiter to access this page.');
        }

        // Check if staff has waiter role
        $role = $staff->role;
        if (!$role || strtolower($role->name) !== 'waiter') {
            abort(403, 'You do not have permission to access the waiter dashboard.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get all products with counter stock
        $variants = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where(function($q) {
                      $q->where('category', 'like', '%beverage%')
                        ->orWhere('category', 'like', '%drink%')
                        ->orWhere('category', 'like', '%alcohol%')
                        ->orWhere('category', 'like', '%beer%')
                        ->orWhere('category', 'like', '%wine%')
                        ->orWhere('category', 'like', '%spirit%');
                  });
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('location', 'counter');
        }])
        ->get()
        ->filter(function($variant) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            return $counterStock && $counterStock->quantity > 0;
        })
        ->map(function($variant) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $category = $variant->product->category ?? '';
            // Determine if alcoholic based on category
            $isAlcoholic = stripos($category, 'alcoholic') !== false;
            
            return [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant' => $variant->measurement . ' - ' . $variant->packaging,
                'quantity' => $counterStock->quantity,
                'selling_price' => $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                'selling_price_per_tot' => $counterStock->selling_price_per_tot ?? $variant->selling_price_per_tot ?? 0,
                'can_sell_in_tots' => $variant->can_sell_in_tots,
                'total_tots' => $variant->total_tots,
                'items_per_package' => $variant->items_per_package ?? 1,
                'packaging_type' => $variant->packaging ?? 'Packages',
                'category' => $category,
                'is_alcoholic' => $isAlcoholic,
                'product_image' => $variant->product->image ?? null,
            ];
        });

        // Get waiter's recent orders
        $recentOrders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->with(['items.productVariant.product', 'table'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get all active tables with availability information
        $tables = \App\Models\BarTable::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with('activeOrders')
            ->orderBy('table_number')
            ->get()
            ->map(function($table) {
                return [
                    'id' => $table->id,
                    'table_number' => $table->table_number,
                    'table_name' => $table->table_name,
                    'capacity' => $table->capacity,
                    'current_people' => $table->current_people,
                    'remaining_capacity' => $table->remaining_capacity,
                    'location' => $table->location ?? 'N/A',
                    'status' => $table->status,
                ];
            });

        // Get all active food items
        $foodItems = FoodItem::where('user_id', $ownerId)
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Get completed and served orders (items taken or orders served) - Waiter only sees history
        $completedOrders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->where(function($query) {
                $query->where('status', 'served')
                    ->orWhereHas('kitchenOrderItems', function($q) {
                        $q->where('status', 'completed');
                    });
            })
            ->with(['kitchenOrderItems' => function($query) {
                $query->where('status', 'completed')->orderBy('updated_at', 'desc');
            }, 'items.productVariant.product', 'table', 'waiter'])
            ->orderBy('updated_at', 'desc')
            ->limit(20)
            ->get();

        return view('bar.waiter.dashboard', compact('variants', 'foodItems', 'recentOrders', 'staff', 'tables', 'completedOrders'));
    }

    /**
     * Create Order from Waiter
     */
    public function createOrder(Request $request)
    {
        $ownerId = $this->getOwnerId();
        
        // Check if order is from kiosk (use session waiter) or web (use current staff)
        $orderSource = $request->input('order_source', 'web');
        $staff = null;
        
        if ($orderSource === 'kiosk') {
            // Get waiter from kiosk session
            $waiterId = session('kiosk_waiter_id');
            if (!$waiterId) {
                return response()->json(['error' => 'Please login first'], 401);
            }
            $staff = \App\Models\Staff::find($waiterId);
            if (!$staff || !$staff->is_active) {
                return response()->json(['error' => 'Invalid waiter session'], 401);
            }
            // Verify waiter role
            $role = $staff->role;
            if (!$role || strtolower($role->name) !== 'waiter') {
                return response()->json(['error' => 'This account is not authorized as a waiter'], 403);
            }
            // Verify owner matches
            if ($staff->user_id !== $ownerId) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } else {
            // Web order - use current staff
            $staff = $this->getCurrentStaff();
            if (!$staff || !$staff->is_active) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // Log raw request data for debugging
        \Log::info('Raw request data received', [
            'all_input' => $request->all(),
            'items_raw' => $request->input('items'),
            'content_type' => $request->header('Content-Type'),
            'is_json' => $request->isJson(),
            'raw_content' => $request->getContent(),
        ]);
        
        // If JSON request, manually parse if needed
        if ($request->isJson() && empty($request->input('items'))) {
            $jsonData = json_decode($request->getContent(), true);
            if ($jsonData && isset($jsonData['items'])) {
                $request->merge($jsonData);
                \Log::info('Manually parsed JSON data', ['parsed_data' => $request->all()]);
            }
        }
        
        // Get items BEFORE validation to preserve all fields
        $itemsBeforeValidation = $request->input('items', []);
        \Log::info('Items before validation', ['items' => $itemsBeforeValidation]);
        
        // Validate items - can be either variant_id (for drinks) or food_item_id (for food)
        // IMPORTANT: Don't validate items.* fields individually as it strips other fields
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'table_id' => 'nullable|exists:bar_tables,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'order_source' => 'required|in:web,kiosk',
            'order_notes' => 'nullable|string|max:1000',
        ]);
        
        // Manually validate items and preserve all fields
        $validatedItems = [];
        foreach ($itemsBeforeValidation as $index => $item) {
            if (!isset($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] < 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be at least 1.'
                ]);
            }
            // Preserve ALL fields from the original item
            $validatedItems[] = $item;
        }
        
        // Replace validated items with the full item data
        $validated['items'] = $validatedItems;
        
        // Additional validation for items - each item must have either variant_id or food_item_id
        \Log::info('Validating order items (after preserving fields)', [
            'items_count' => count($validated['items']), 
            'items' => $validated['items'],
            'first_item_keys' => !empty($validated['items'][0]) ? array_keys($validated['items'][0]) : [],
            'first_item_full' => !empty($validated['items'][0]) ? $validated['items'][0] : null
        ]);
        
        foreach ($validated['items'] as $index => $item) {
            $hasVariantId = isset($item['variant_id']) && $item['variant_id'] !== null && $item['variant_id'] !== '';
            $hasFoodItemId = isset($item['food_item_id']) && $item['food_item_id'] !== null && $item['food_item_id'] !== '';
            
            \Log::info("Item {$index} validation", [
                'item' => $item,
                'hasVariantId' => $hasVariantId,
                'hasFoodItemId' => $hasFoodItemId,
                'variant_id_value' => $item['variant_id'] ?? 'not set',
                'food_item_id_value' => $item['food_item_id'] ?? 'not set',
            ]);
            
            if (!$hasVariantId && !$hasFoodItemId) {
                \Log::error("Item {$index} missing both variant_id and food_item_id", ['item' => $item]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "items.{$index}" => 'Each item must have either variant_id (for drinks) or food_item_id (for food items).'
                ]);
            }
            
            if ($hasFoodItemId) {
                // Validate food item fields
                if (empty($item['product_name']) || !isset($item['price']) || $item['price'] <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}" => 'Food items must include product_name and a valid price greater than 0.'
                    ]);
                }
                // Validate food_item_id exists in database
                $foodItemId = (int)$item['food_item_id'];
                if (!FoodItem::where('id', $foodItemId)->where('user_id', $ownerId)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.food_item_id" => 'Invalid food item ID.'
                    ]);
                }
                // Notes are optional for food items, but if provided, validate length
                if (isset($item['notes']) && strlen($item['notes']) > 500) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.notes" => 'Special instructions cannot exceed 500 characters.'
                    ]);
                }
            } else {
                // Validate variant exists
                $variantId = (int)$item['variant_id'];
                if (!\App\Models\ProductVariant::where('id', $variantId)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.{$index}.variant_id" => 'Invalid product variant ID.'
                    ]);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Generate order number
            $orderNumber = BarOrder::generateOrderNumber($ownerId);

            // Calculate total
            $totalAmount = 0;
            $orderItems = [];
            $kitchenOrderItems = [];
            $foodItemsNotes = [];
            $orderNotes = '';

            foreach ($validated['items'] as $item) {
                // Handle food items - create kitchen order items
                if (isset($item['food_item_id']) && $item['food_item_id'] !== null) {
                    $unitPrice = isset($item['price']) ? (float)$item['price'] : 0;
                    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                    $itemTotal = $quantity * $unitPrice;
                    $totalAmount += $itemTotal;
                    
                    // Store food item info for kitchen_order_items table
                    $kitchenOrderItems[] = [
                        'food_item_id' => $item['food_item_id'], // Link to food_items table
                        'food_item_name' => $item['product_name'] ?? 'Food Item', // Keep for backward compatibility
                        'variant_name' => (!empty($item['variant_name'])) ? $item['variant_name'] : null,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $itemTotal,
                        'special_instructions' => (!empty($item['notes'])) ? $item['notes'] : null,
                        'status' => 'pending', // Will be managed by chef
                    ];
                    
                    // Also keep in notes for backward compatibility
                    $foodItemNote = $item['quantity'] . 'x ' . ($item['product_name'] ?? 'Food Item') . 
                                   ($item['variant_name'] ? ' (' . $item['variant_name'] . ')' : '') . 
                                   ' - Tsh ' . number_format($unitPrice, 0);
                    
                    if (!empty($item['notes'])) {
                        $foodItemNote .= ' [Note: ' . $item['notes'] . ']';
                    }
                    
                    $foodItemsNotes[] = $foodItemNote;
                    continue;
                }
                
                // Handle regular product variants (drinks)
                if (!isset($item['variant_id']) || !$item['variant_id']) {
                    continue; // Skip invalid items
                }
                
                $sellType = $item['sell_type'] ?? 'unit';
                $variant = ProductVariant::with(['product', 'stockLocations' => function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'counter');
                }])->findOrFail($item['variant_id']);

                $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                
                if (!$counterStock) {
                    throw new \Exception("Counter stock not found for {$variant->product->name}");
                }

                // Accurate stock check for shots vs units
                if ($sellType === 'tot') {
                    $totsPerBottle = $variant->total_tots ?: 1;
                    $openBottle = \App\Models\OpenBottle::where('user_id', $ownerId)
                        ->where('product_variant_id', $variant->id)
                        ->first();
                    
                    $totalTotsAvailable = ($counterStock->quantity * $totsPerBottle) + ($openBottle ? $openBottle->tots_remaining : 0);
                    
                    if ($totalTotsAvailable < $item['quantity']) {
                        throw new \Exception("Insufficient shots for {$variant->product->name}. Available: {$totalTotsAvailable} shots.");
                    }
                } else {
                    if ($counterStock->quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for {$variant->product->name} - {$variant->measurement}");
                    }
                }

                $sellingPrice = $sellType === 'tot' 
                    ? ($counterStock->selling_price_per_tot ?? $variant->selling_price_per_tot ?? 0)
                    : ($counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0);

                $itemTotal = $item['quantity'] * $sellingPrice;
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'product_variant_id' => $variant->id,
                    'sell_type' => $sellType,
                    'quantity' => $item['quantity'],
                    'unit_price' => $sellingPrice,
                    'total_price' => $itemTotal,
                ];
            }
            
            // Build order notes with food items
            $notesParts = [];
            if (!empty($foodItemsNotes)) {
                $notesParts[] = 'FOOD ITEMS: ' . implode(', ', $foodItemsNotes);
            }
            
            // Add general order notes if provided
            if (!empty($validated['order_notes'])) {
                $notesParts[] = 'ORDER NOTES: ' . $validated['order_notes'];
            }
            
            $orderNotes = !empty($notesParts) ? implode(' | ', $notesParts) : '';

            // Create order - payment will be recorded later in Order History after customer finishes
            $order = BarOrder::create([
                'user_id' => $ownerId,
                'order_number' => $orderNumber,
                'waiter_id' => $staff->id,
                'order_source' => $validated['order_source'],
                'table_id' => $validated['table_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'notes' => $orderNotes,
                // Payment method will be set later when payment is recorded
            ]);

            // Create order items (drinks)
            $transferSaleService = new \App\Services\TransferSaleService();
            
            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'sell_type' => $item['sell_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
                
                // Attribute sale to transfers using FIFO
                $transferSaleService->attributeSaleToTransfer($orderItem, $ownerId);
            }

            // Create kitchen order items (food) - automatically routed to chef dashboard
            foreach ($kitchenOrderItems as $item) {
                KitchenOrderItem::create([
                    'order_id' => $order->id,
                    'food_item_id' => $item['food_item_id'] ?? null, // Link to food_items table
                    'food_item_name' => $item['food_item_name'],
                    'variant_name' => $item['variant_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'special_instructions' => $item['special_instructions'],
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            // Send SMS notification to waiter (async to not block response)
            try {
                $smsService = new \App\Services\WaiterSmsService();
                $smsService->sendOrderNotification($order);
                
                // Send SMS to customer if phone number provided
                if ($order->customer_phone) {
                    $smsService->sendCustomerOrderConfirmation($order);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the order creation
                \Log::error('Failed to send SMS notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order->load(['items.productVariant.product', 'table']),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get Order History
     */
    public function orderHistory()
    {
        $staff = $this->getCurrentStaff();
        
        if (!$staff || !$staff->is_active) {
            abort(403, 'You must be logged in as an active waiter.');
        }

        $ownerId = $this->getOwnerId();

        $orders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->with(['items.productVariant.product', 'kitchenOrderItems', 'table', 'orderPayments', 'paidByWaiter'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('bar.waiter.order-history', compact('orders', 'staff'));
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request, BarOrder $order)
    {
        $staff = $this->getCurrentStaff();
        
        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify order belongs to the same business
        if ($order->user_id !== $staff->user_id) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Only allow cancellation of pending orders
        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Only pending orders can be cancelled'], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $order->status = 'cancelled';
            $cancelReason = !empty($validated['reason']) ? 'CANCELLED - Reason: ' . $validated['reason'] : 'CANCELLED';
            $order->notes = ($order->notes ? $order->notes . ' | ' : '') . $cancelReason;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to cancel order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Record payment for an order (after customer finishes)
     */
    public function recordPayment(Request $request, BarOrder $order)
    {
        $staff = $this->getCurrentStaff();
        
        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ownerId = $this->getOwnerId();

        // Verify order belongs to the same business and waiter
        if ($order->user_id !== $ownerId) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->waiter_id !== $staff->id) {
            return response()->json(['error' => 'You can only record payment for your own orders'], 403);
        }

        // Check if payment can be recorded using the model's validation logic
        if (!$order->canRecordPayment()) {
            $message = $order->getPaymentReadinessMessage();
            return response()->json([
                'error' => $message ?: 'Payment cannot be recorded at this time'
            ], 400);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,mobile_money',
            'mobile_money_number' => 'required_if:payment_method,mobile_money|nullable|string|max:20',
            'transaction_reference' => 'required_if:payment_method,mobile_money|nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            // Update order with payment information
            // NOTE: Keep payment_status as 'pending' - it will be set to 'paid' when counter/chief submits reconciliation
            $order->update([
                'payment_method' => $validated['payment_method'],
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payment_status' => 'pending', // Keep as pending until counter/chief submits
                'paid_amount' => 0, // Will be set when counter submits
                'paid_by_waiter_id' => $staff->id,
            ]);

            // Create payment record (this is the "recorded" payment)
            $payment = \App\Models\OrderPayment::create([
                'order_id' => $order->id,
                'payment_method' => $validated['payment_method'],
                'amount' => $order->total_amount,
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payment_status' => $validated['payment_method'] === 'mobile_money' ? 'pending' : 'verified', // Mobile money needs verification
            ]);

            // Send payment notification SMS to waiter and customer
            try {
                $smsService = new \App\Services\WaiterSmsService();
                $smsService->sendPaymentNotification($order, $validated['payment_method'], $order->total_amount);
                
                // Send thank you SMS to customer
                if ($order->customer_phone) {
                    $smsService->sendCustomerPaymentThankYou($order, $validated['payment_method'], $order->total_amount);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the payment recording
                \Log::error('Failed to send payment SMS notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'status' => 'recorded',
                'payment_status' => 'recorded',
                'order' => $order->load(['items.productVariant.product', 'table', 'orderPayments']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment recording failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to record payment: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Print receipt for an order
     */
    public function printReceipt(BarOrder $order)
    {
        $staff = $this->getCurrentStaff();
        
        if (!$staff || !$staff->is_active) {
            abort(403, 'Unauthorized');
        }

        // Verify order belongs to the same business
        if ($order->user_id !== $staff->user_id) {
            abort(404, 'Order not found');
        }

        $order->load(['items.productVariant.product', 'table', 'waiter']);

        return view('bar.waiter.receipt', compact('order'));
    }

    /**
     * Kiosk Interface - Public Product Display
     */
    public function kiosk(Request $request)
    {
        // Try to get owner ID from authenticated user/staff first
        $ownerId = $this->getOwnerId();
        
        // If no authenticated user, try to get from request parameter
        if (!$ownerId) {
            $ownerId = $request->input('user_id');
        }
        
        // If still no owner ID, try to auto-detect from business
        if (!$ownerId) {
            // Get the first user with business_type 'bar' or restaurant
            $owner = \App\Models\User::where(function($query) {
                $query->where('business_type', 'bar')
                      ->orWhere('business_type', 'restaurant');
            })->first();
            
            if ($owner) {
                $ownerId = $owner->id;
            } else {
                // Fallback: get first user
                $owner = \App\Models\User::first();
                $ownerId = $owner ? $owner->id : null;
            }
            
            // If still no owner ID, show error
            if (!$ownerId) {
                abort(403, 'No business found. Please contact administrator.');
            }
        }
        
        // Get all products with counter stock (public view, no login required)
        $variants = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where(function($q) {
                      $q->where('category', 'like', '%beverage%')
                        ->orWhere('category', 'like', '%drink%')
                        ->orWhere('category', 'like', '%alcohol%')
                        ->orWhere('category', 'like', '%beer%')
                        ->orWhere('category', 'like', '%wine%')
                        ->orWhere('category', 'like', '%spirit%');
                  });
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('location', 'counter');
        }])
        ->get()
        ->filter(function($variant) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            return $counterStock && $counterStock->quantity > 0;
        })
        ->map(function($variant) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            return [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant' => $variant->measurement . ' - ' . $variant->packaging,
                'quantity' => $counterStock->quantity,
                'selling_price' => $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                'items_per_package' => $variant->items_per_package ?? 1,
                'packaging_type' => $variant->packaging ?? 'Packages',
            ];
        });

        return view('bar.waiter.kiosk', compact('variants'));
    }

    /**
     * Kiosk Login - Authenticate Waiter for Order
     */
    public function kioskLogin(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $staff = \App\Models\Staff::where('staff_id', $validated['staff_id'])
            ->where('is_active', true)
            ->first();

        if (!$staff) {
            return response()->json(['error' => 'Invalid waiter ID'], 401);
        }

        // Check if staff has waiter role
        $role = $staff->role;
        if (!$role || strtolower($role->name) !== 'waiter') {
            return response()->json(['error' => 'This account is not authorized as a waiter'], 403);
        }

        // Verify password
        if (!\Hash::check($validated['password'], $staff->password)) {
            return response()->json(['error' => 'Invalid password'], 401);
        }

        // Create session for kiosk
        session([
            'kiosk_waiter_id' => $staff->id,
            'kiosk_waiter_name' => $staff->full_name,
            'kiosk_waiter_staff_id' => $staff->staff_id,
        ]);

        return response()->json([
            'success' => true,
            'waiter' => [
                'id' => $staff->id,
                'name' => $staff->full_name,
                'staff_id' => $staff->staff_id,
            ],
        ]);
    }

    /**
     * Kiosk Logout
     */
    public function kioskLogout()
    {
        session()->forget(['kiosk_waiter_id', 'kiosk_waiter_name', 'kiosk_waiter_staff_id']);
        return response()->json(['success' => true]);
    }
}
