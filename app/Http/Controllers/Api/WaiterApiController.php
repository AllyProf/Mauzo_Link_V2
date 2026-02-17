<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\BarOrder;
use App\Models\ProductVariant;
use App\Models\FoodItem;
use App\Models\BarTable;
use App\Models\OrderItem;
use App\Models\KitchenOrderItem;
use App\Models\WaiterDailyReconciliation;
use App\Models\WaiterNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WaiterApiController extends Controller
{
    /**
     * Waiter Login - Returns API token
     * Accepts either staff_id or email for login
     */
    public function login(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('API Login Request Received', [
            'all_input' => $request->all(),
            'email' => $request->input('email'),
            'staff_id' => $request->input('staff_id'),
            'has_password' => $request->has('password'),
        ]);

        try {
            // More flexible validation - accept either staff_id or email (or both)
            $validated = $request->validate([
                'staff_id' => 'nullable|string',
                'email' => 'nullable|string',
                'password' => 'required|string',
            ]);
            
            // Ensure at least one identifier is provided
            if (empty($validated['staff_id']) && empty($validated['email'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Either staff_id or email must be provided'
                ], 422);
            }
            
            // If email is provided, validate it's a proper email format
            if (!empty($validated['email']) && !filter_var($validated['email'], FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid email format'
                ], 422);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('API Login Validation Failed', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Try to find staff by staff_id or email
        // Also handle case where email is sent in staff_id field
        $staff = null;
        $searchField = null;
        $searchValue = null;
        
        // Check if staff_id was provided
        if (!empty($validated['staff_id'])) {
            $searchValue = $validated['staff_id'];
            
            // Check if the value looks like an email (contains @)
            if (filter_var($validated['staff_id'], FILTER_VALIDATE_EMAIL)) {
                // It's an email, search by email instead
                $searchField = 'email';
                $staff = Staff::where('email', $validated['staff_id'])
                    ->with('role')
                    ->first();
                
                // If not found, try case-insensitive search
                if (!$staff) {
                    $staff = Staff::whereRaw('LOWER(email) = ?', [strtolower($validated['staff_id'])])
                        ->with('role')
                        ->first();
                }
            } else {
                // It's a staff_id, search by staff_id
                $searchField = 'staff_id';
                $staff = Staff::where('staff_id', $validated['staff_id'])
                    ->with('role')
                    ->first();
            }
        }
        
        // If email was provided (and staff not found yet), search by email
        if (!$staff && !empty($validated['email'])) {
            $searchField = 'email';
            $searchValue = $validated['email'];
            // Try exact match first
            $staff = Staff::where('email', $validated['email'])
                ->with('role')
                ->first();
            
            // If not found, try case-insensitive search
            if (!$staff) {
                $staff = Staff::whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
                    ->with('role')
                    ->first();
            }
        }
        
        // If still not found, try searching both fields as fallback
        if (!$staff) {
            $searchValue = $validated['staff_id'] ?? $validated['email'] ?? null;
            // Try email field
            if ($searchValue && filter_var($searchValue, FILTER_VALIDATE_EMAIL)) {
                $searchField = 'email';
                $staff = Staff::whereRaw('LOWER(email) = ?', [strtolower($searchValue)])
                    ->with('role')
                    ->first();
            }
            // Try staff_id field
            if (!$staff && $searchValue) {
                $searchField = 'staff_id';
                $staff = Staff::where('staff_id', $searchValue)
                    ->with('role')
                    ->first();
            }
        }

        // Log the search attempt
        \Log::info('API Login Attempt', [
            'search_field' => $searchField,
            'search_value' => $searchValue,
            'staff_found' => $staff ? true : false,
            'staff_id' => $staff->id ?? null,
            'staff_email' => $staff->email ?? null,
            'is_active' => $staff->is_active ?? null,
            'has_role' => $staff && $staff->role ? true : false,
            'role_name' => $staff && $staff->role ? $staff->role->name : null,
        ]);

        // Check if staff exists
        if (!$staff) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid credentials. Staff account not found.',
                'debug' => [
                    'searched_by' => $searchField,
                    'searched_value' => $searchValue
                ]
            ], 401);
        }

        // Check if staff is active
        if (!$staff->is_active) {
            \Log::info('API Login: Staff account inactive', [
                'staff_id' => $staff->id,
                'email' => $staff->email,
                'is_active' => $staff->is_active
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Account is inactive. Please contact your administrator.'
            ], 401);
        }

        // Check if staff has waiter role
        $role = $staff->role;
        if (!$role) {
            \Log::info('API Login: Staff has no role', [
                'staff_id' => $staff->id,
                'email' => $staff->email
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Account has no role assigned. Please contact your administrator.'
            ], 403);
        }

        if (strtolower($role->name) !== 'waiter') {
            \Log::info('API Login: Staff is not a waiter', [
                'staff_id' => $staff->id,
                'email' => $staff->email,
                'role' => $role->name
            ]);
            return response()->json([
                'success' => false,
                'error' => 'This account is not authorized as a waiter. Current role: ' . $role->name
            ], 403);
        }

        // Verify password
        if (!Hash::check($validated['password'], $staff->password)) {
            \Log::info('API Login: Invalid password', [
                'staff_id' => $staff->id,
                'email' => $staff->email
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Invalid password'
            ], 401);
        }

        // Generate API token (simple token, can be upgraded to Sanctum later)
        $token = Str::random(60);
        $staff->api_token = hash('sha256', $token);
        $staff->api_token_expires_at = now()->addDays(30); // Token valid for 30 days
        $staff->last_login_at = now();
        $staff->save();

        return response()->json([
            'success' => true,
            'token' => $token,
            'waiter' => [
                'id' => $staff->id,
                'staff_id' => $staff->staff_id,
                'name' => $staff->full_name,
                'email' => $staff->email,
                'phone_number' => $staff->phone_number,
            ],
            'expires_at' => $staff->api_token_expires_at->toIso8601String(),
        ]);
    }

    /**
     * Waiter Logout - Revoke API token
     */
    public function logout(Request $request)
    {
        $staff = $request->get('authenticated_staff');
        
        if ($staff) {
            $staff->api_token = null;
            $staff->api_token_expires_at = null;
            $staff->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get Products (Drinks) - Available in counter stock
     */
    public function getProducts(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;

        $variants = ProductVariant::with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)->where('location', 'counter');
        }])
        ->whereHas('stockLocations', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                ->where('location', 'counter')
                ->where('quantity', '>', 0);
        })
        ->get()
        ->map(function($variant) use ($ownerId) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $category = $variant->product->category ?? '';
            $isAlcoholic = stripos($category, 'alcoholic') !== false;
            
            return [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant' => $variant->measurement . ' - ' . $variant->packaging,
                'measurement' => $variant->measurement,
                'packaging' => $variant->packaging,
                'quantity' => $counterStock->quantity ?? 0,
                'selling_price' => $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                'category' => $category,
                'is_alcoholic' => $isAlcoholic,
                'product_image' => $variant->product->image ?? null,
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $variants
        ]);
    }

    /**
     * Get Food Items
     */
    public function getFoodItems(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;

        $foodItems = FoodItem::where('user_id', $ownerId)
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function($item) {
                // Ensure variants is an array and prices are numbers
                $variants = $item->variants ?? [];
                
                // If variants is empty or null, try to create from price field
                if (empty($variants)) {
                    // If we have variant_name and price, use variant_name
                    if ($item->variant_name && $item->price) {
                        $variants = [[
                            'name' => $item->variant_name,
                            'price' => (float)$item->price
                        ]];
                    } 
                    // If we only have price (no variant_name), create a default variant
                    elseif ($item->price) {
                        $variants = [[
                            'name' => 'Standard',
                            'price' => (float)$item->price
                        ]];
                    }
                } else {
                    // Ensure all prices in variants are numbers (not strings)
                    $variants = array_map(function($variant) {
                        if (isset($variant['price'])) {
                            $variant['price'] = (float)$variant['price'];
                        }
                        return $variant;
                    }, $variants);
                }
                
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'variants' => $variants,
                    'image' => $item->image ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'food_items' => $foodItems
        ]);
    }

    /**
     * Get Tables
     */
    public function getTables(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;

        $tables = BarTable::where('user_id', $ownerId)
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

        return response()->json([
            'success' => true,
            'tables' => $tables
        ]);
    }

    /**
     * Create Order
     */
    public function createOrder(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required_without:items.*.food_item_id|nullable|exists:product_variants,id',
            'items.*.food_item_id' => 'required_without:items.*.variant_id|nullable|exists:food_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.product_name' => 'required_if:items.*.food_item_id,!=,null|nullable|string',
            'items.*.variant_name' => 'nullable|string',
            'items.*.notes' => 'nullable|string|max:500',
            'table_id' => 'nullable|exists:bar_tables,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'order_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $orderNumber = BarOrder::generateOrderNumber($ownerId);
            $totalAmount = 0;
            $orderItems = [];
            $kitchenOrderItems = [];
            $foodItemsNotes = [];

            foreach ($validated['items'] as $item) {
                // Handle food items
                if (isset($item['food_item_id']) && $item['food_item_id'] !== null) {
                    $unitPrice = (float)$item['price'];
                    $quantity = (int)$item['quantity'];
                    $itemTotal = $quantity * $unitPrice;
                    $totalAmount += $itemTotal;
                    
                    $kitchenOrderItems[] = [
                        'food_item_id' => $item['food_item_id'], // Link to food_items table
                        'food_item_name' => $item['product_name'] ?? 'Food Item', // Keep for backward compatibility
                        'variant_name' => $item['variant_name'] ?? null,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $itemTotal,
                        'special_instructions' => $item['notes'] ?? null,
                        'status' => 'pending',
                    ];
                    
                    $foodItemNote = $quantity . 'x ' . ($item['product_name'] ?? 'Food Item') . 
                                   ($item['variant_name'] ? ' (' . $item['variant_name'] . ')' : '') . 
                                   ' - Tsh ' . number_format($unitPrice, 0);
                    
                    if (!empty($item['notes'])) {
                        $foodItemNote .= ' [Note: ' . $item['notes'] . ']';
                    }
                    
                    $foodItemsNotes[] = $foodItemNote;
                    continue;
                }
                
                // Handle drink items
                if (!isset($item['variant_id']) || !$item['variant_id']) {
                    continue;
                }
                
                $variant = ProductVariant::with(['product', 'stockLocations' => function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'counter');
                }])->findOrFail($item['variant_id']);

                $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                
                if (!$counterStock || $counterStock->quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$variant->product->name} - {$variant->measurement}");
                }

                $sellingPrice = $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0;
                $itemTotal = $item['quantity'] * $sellingPrice;
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $sellingPrice,
                    'total_price' => $itemTotal,
                ];
            }

            // Build order notes
            $notesParts = [];
            if (!empty($foodItemsNotes)) {
                $notesParts[] = 'FOOD ITEMS: ' . implode(', ', $foodItemsNotes);
            }
            if (!empty($validated['order_notes'])) {
                $notesParts[] = 'ORDER NOTES: ' . $validated['order_notes'];
            }
            $orderNotes = !empty($notesParts) ? implode(' | ', $notesParts) : '';

            // Create order
            $order = BarOrder::create([
                'user_id' => $ownerId,
                'order_number' => $orderNumber,
                'waiter_id' => $staff->id,
                'order_source' => 'mobile',
                'table_id' => $validated['table_id'] ?? null,
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'notes' => $orderNotes,
            ]);

            // Create order items (drinks)
            $transferSaleService = new \App\Services\TransferSaleService();
            
            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
                
                // Attribute sale to transfers using FIFO
                $transferSaleService->attributeSaleToTransfer($orderItem, $ownerId);
            }

            // Create kitchen order items (food)
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

            // Send SMS notifications
            try {
                $smsService = new \App\Services\WaiterSmsService();
                $smsService->sendOrderNotification($order);
                
                if ($order->customer_phone) {
                    $smsService->sendCustomerOrderConfirmation($order);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send SMS notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order->load(['items.productVariant.product', 'kitchenOrderItems', 'table']),
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
    public function getOrderHistory(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $orders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->with(['items.productVariant.product', 'kitchenOrderItems', 'table', 'orderPayments', 'paidByWaiter'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Get Completed and Served Orders
     */
    public function getCompletedOrders(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);

        $orders = BarOrder::where('user_id', $ownerId)
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
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Record Payment
     */
    public function recordPayment(Request $request, $orderId)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;
        $order = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->findOrFail($orderId);

        if ($order->user_id !== $ownerId || $order->waiter_id !== $staff->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$order->canRecordPayment()) {
            $message = $order->getPaymentReadinessMessage();
            return response()->json([
                'success' => false,
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
                'payment_status' => $validated['payment_method'] === 'mobile_money' ? 'pending' : 'verified',
            ]);

            // Send SMS notifications
            try {
                $smsService = new \App\Services\WaiterSmsService();
                $smsService->sendPaymentNotification($order, $validated['payment_method'], $order->total_amount);
                
                if ($order->customer_phone) {
                    $smsService->sendCustomerPaymentThankYou($order, $validated['payment_method'], $order->total_amount);
                }
            } catch (\Exception $e) {
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
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to record payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel Order
     */
    public function cancelOrder(Request $request, $orderId)
    {
        $staff = $request->get('authenticated_staff');
        $ownerId = $staff->user_id;

        $order = BarOrder::where('user_id', $ownerId)
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Order not found'
            ], 404);
        }

        // Verify order belongs to the waiter (if waiter_id is set)
        if ($order->waiter_id && $order->waiter_id !== $staff->id) {
            return response()->json([
                'success' => false,
                'error' => 'You can only cancel your own orders'
            ], 403);
        }

        // Only allow cancellation of pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'error' => 'Only pending orders can be cancelled. Current status: ' . $order->status
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $order->status = 'cancelled';
            $cancelReason = !empty($validated['reason']) 
                ? 'CANCELLED - Reason: ' . $validated['reason'] 
                : 'CANCELLED';
            $order->notes = ($order->notes ? $order->notes . ' | ' : '') . $cancelReason;
            $order->save();

            DB::commit();

            \Log::info('Order cancelled via API', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'waiter_id' => $staff->id,
                'reason' => $validated['reason'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'order' => $order->load(['items.productVariant.product', 'kitchenOrderItems', 'table'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to cancel order via API', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Daily Sales Summary
     */
    public function getDailySales(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;
        $date = $request->get('date', now()->format('Y-m-d'));

        $orders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->whereDate('created_at', $date)
            ->with(['items.productVariant.product', 'kitchenOrderItems', 'table', 'orderPayments'])
            ->get();

        $totalSales = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        
        $cashCollected = $orders->where('payment_method', 'cash')->sum('paid_amount') + 
                        $orders->sum(function($order) {
                            return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                        });
        
        $mobileMoneyCollected = $orders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                               $orders->sum(function($order) {
                                   return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                               });

        return response()->json([
            'success' => true,
            'date' => $date,
            'summary' => [
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'cash_collected' => $cashCollected,
                'mobile_money_collected' => $mobileMoneyCollected,
            ],
            'orders' => $orders->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'created_at' => $order->created_at->toIso8601String(),
                ];
            })
        ]);
    }

    /**
     * Get Order Details
     */
    public function getOrderDetails(Request $request, $orderId)
    {
        $staff = $request->get('authenticated_staff');

        $ownerId = $staff->user_id;
        $order = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->with(['items.productVariant.product', 'kitchenOrderItems', 'table', 'orderPayments', 'paidByWaiter'])
            ->findOrFail($orderId);

        return response()->json([
            'success' => true,
            'order' => $order
        ]);
    }

    /**
     * Get Waiter Reconciliation for a specific date
     */
    public function getReconciliation(Request $request)
    {
        $staff = $request->get('authenticated_staff');
        $date = $request->get('date', now()->format('Y-m-d'));

        $ownerId = $staff->user_id;

        // Get all orders for this waiter on this date
        $orders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $staff->id)
            ->whereDate('created_at', $date)
            ->with(['items', 'kitchenOrderItems', 'table', 'orderPayments'])
            ->get();

        // Separate bar orders (drinks) from food orders
        $barOrders = $orders->filter(function($order) {
            return $order->items && $order->items->count() > 0;
        });

        $foodOrders = $orders->filter(function($order) {
            return $order->kitchenOrderItems && $order->kitchenOrderItems->count() > 0;
        });

        // Calculate bar sales (drinks only)
        $barSales = $barOrders->sum(function($order) {
            return $order->items->sum('total_price');
        });

        // Calculate food sales
        $foodSales = $foodOrders->sum(function($order) {
            return $order->kitchenOrderItems->sum('total_price');
        });

        // Calculate paid amounts
        $paidBarOrders = $barOrders->filter(function($order) {
            return $order->status === 'served' && $order->payment_status === 'paid';
        });

        $paidFoodOrders = $foodOrders->filter(function($order) {
            return $order->status === 'served' && $order->payment_status === 'paid';
        });

        $paidBarAmount = $paidBarOrders->sum(function($order) {
            return $order->items->sum('total_price');
        });

        $paidFoodAmount = $paidFoodOrders->sum(function($order) {
            return $order->kitchenOrderItems->sum('total_price');
        });

        // Payment collection
        $cashCollected = $orders->where('payment_method', 'cash')->sum('paid_amount') + 
                        $orders->sum(function($order) {
                            return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                        });

        $mobileMoneyCollected = $orders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                               $orders->sum(function($order) {
                                   return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                               });

        // Get reconciliation record if exists
        $reconciliation = WaiterDailyReconciliation::where('waiter_id', $staff->id)
            ->where('reconciliation_date', $date)
            ->first();

        // Check for unpaid orders
        $hasUnpaidBarOrders = $barOrders->filter(function($order) {
            return $order->status === 'served' && $order->payment_status !== 'paid';
        })->count() > 0;

        $hasUnpaidFoodOrders = $foodOrders->filter(function($order) {
            return $order->status === 'served' && $order->payment_status !== 'paid';
        })->count() > 0;

        return response()->json([
            'success' => true,
            'date' => $date,
            'reconciliation' => [
                'bar_sales' => $barSales,
                'food_sales' => $foodSales,
                'total_sales' => $barSales + $foodSales,
                'bar_orders_count' => $barOrders->count(),
                'food_orders_count' => $foodOrders->count(),
                'total_orders_count' => $orders->count(),
                'paid_bar_amount' => $paidBarAmount,
                'paid_food_amount' => $paidFoodAmount,
                'total_paid_amount' => $paidBarAmount + $paidFoodAmount,
                'cash_collected' => $cashCollected,
                'mobile_money_collected' => $mobileMoneyCollected,
                'has_unpaid_bar_orders' => $hasUnpaidBarOrders,
                'has_unpaid_food_orders' => $hasUnpaidFoodOrders,
                'status' => $reconciliation ? $reconciliation->status : ($paidBarAmount + $paidFoodAmount > 0 ? 'partial' : 'pending'),
                'submitted_amount' => $reconciliation ? $reconciliation->submitted_amount : ($paidBarAmount + $paidFoodAmount),
                'expected_amount' => $barSales + $foodSales,
                'difference' => ($reconciliation ? $reconciliation->submitted_amount : ($paidBarAmount + $paidFoodAmount)) - ($barSales + $foodSales),
            ],
            'reconciliation_record' => $reconciliation ? [
                'id' => $reconciliation->id,
                'status' => $reconciliation->status,
                'submitted_amount' => $reconciliation->submitted_amount,
                'expected_amount' => $reconciliation->expected_amount,
                'difference' => $reconciliation->difference,
                'submitted_at' => $reconciliation->submitted_at?->toIso8601String(),
                'verified_at' => $reconciliation->verified_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Get Waiter Notifications
     */
    public function getNotifications(Request $request)
    {
        $staff = $request->get('authenticated_staff');
        
        $limit = $request->get('limit', 50);
        $unreadOnly = $request->get('unread_only', false);

        $query = WaiterNotification::where('waiter_id', $staff->id)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $notifications = $query->limit($limit)->get();

        $unreadCount = WaiterNotification::where('waiter_id', $staff->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at->toIso8601String(),
                ];
            })
        ]);
    }

    /**
     * Mark Notification as Read
     */
    public function markNotificationRead(Request $request, $notificationId)
    {
        $staff = $request->get('authenticated_staff');

        $notification = WaiterNotification::where('waiter_id', $staff->id)
            ->findOrFail($notificationId);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark All Notifications as Read
     */
    public function markAllNotificationsRead(Request $request)
    {
        $staff = $request->get('authenticated_staff');

        WaiterNotification::where('waiter_id', $staff->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }
}

