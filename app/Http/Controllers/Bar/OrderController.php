<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\BarTable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use HandlesStaffPermissions;
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view orders.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        $type = $request->get('type', 'all'); // all, drinks, food, juice

        $query = BarOrder::where('user_id', $ownerId)
            ->with(['table', 'items.productVariant.product', 'createdBy', 'servedBy']);

        // Filter by order type
        if ($type === 'food') {
            $query->where('notes', 'like', '%FOOD ITEMS:%');
        } elseif ($type === 'juice') {
            $query->where('notes', 'like', '%JUICE ITEMS:%');
        } elseif ($type === 'drinks') {
            // Drinks orders are those that have product_variant_id items (not food/juice)
            // They don't have FOOD ITEMS or JUICE ITEMS in notes, but have OrderItems with product_variant_id
            $query->where(function($q) {
                $q->where('notes', 'not like', '%FOOD ITEMS:%')
                  ->where('notes', 'not like', '%JUICE ITEMS:%');
            })->whereHas('items', function($q) {
                $q->whereNotNull('product_variant_id');
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('bar.orders.index', compact('orders', 'type'));
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'create')) {
            abort(403, 'You do not have permission to create orders.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Get products with variants that have counter stock
        $products = Product::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with(['variants'])
            ->whereHas('variants.stockLocations', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)
                      ->where('location', 'counter')
                      ->where('quantity', '>', 0);
            })
            ->orderBy('name')
            ->get();

        // Process products to include stock information
        $productsWithStock = $products->map(function($product) use ($ownerId) {
            $variantsWithStock = $product->variants->filter(function($variant) use ($ownerId) {
                $counterStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $variant->id)
                    ->where('location', 'counter')
                    ->first();
                return $counterStock && $counterStock->quantity > 0;
            })->map(function($variant) use ($ownerId) {
                $counterStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $variant->id)
                    ->where('location', 'counter')
                    ->first();
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'measurement' => $variant->measurement,
                    'packaging' => $variant->packaging,
                    'items_per_package' => $variant->items_per_package,
                    'counter_quantity' => $counterStock ? $counterStock->quantity : 0,
                    'selling_price' => $counterStock ? $counterStock->selling_price : 0,
                ];
            })->values();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand,
                'variants' => $variantsWithStock,
            ];
        })->filter(function($product) {
            return $product['variants']->count() > 0;
        })->values();

        // Get all active tables
        $tables = BarTable::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get();

        // Prepare products data for JavaScript
        $productsData = $productsWithStock->map(function($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'variants' => $product['variants']->map(function($variant) {
                    return [
                        'id' => $variant['id'],
                        'name' => $variant['name'],
                        'measurement' => $variant['measurement'],
                        'packaging' => $variant['packaging'],
                        'available_quantity' => $variant['counter_quantity'],
                        'selling_price' => $variant['selling_price'],
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        return view('bar.orders.create', compact('productsWithStock', 'tables', 'productsData'));
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'create')) {
            abort(403, 'You do not have permission to create orders.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        $validated = $request->validate([
            'table_id' => 'nullable|exists:bar_tables,id',
            'number_of_people' => 'required|integer|min:1|max:100',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Verify table belongs to owner and check capacity
        if ($validated['table_id']) {
            $table = BarTable::where('id', $validated['table_id'])
                ->where('user_id', $ownerId)
                ->first();
            
            if (!$table) {
                return back()->withErrors(['table_id' => 'Invalid table selected.'])->withInput();
            }

            // Check if table has enough capacity for the number of people
            if (!$table->hasAvailableSeats($validated['number_of_people'])) {
                $remaining = $table->remaining_capacity;
                return back()->withErrors([
                    'number_of_people' => "Table '{$table->table_number}' only has {$remaining} seat(s) available. Capacity: {$table->capacity}, Currently occupied: {$table->current_people}."
                ])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Generate order number
            $orderNumber = BarOrder::generateOrderNumber($ownerId);

            // Calculate total amount and validate stock
            $totalAmount = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $productVariant = ProductVariant::where('id', $item['product_variant_id'])
                    ->whereHas('product', function($query) use ($ownerId) {
                        $query->where('user_id', $ownerId);
                    })
                    ->first();

                if (!$productVariant) {
                    DB::rollBack();
                    return back()->withErrors(['items' => 'Invalid product variant selected.'])->withInput();
                }

                // Check counter stock availability
                $counterStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $productVariant->id)
                    ->where('location', 'counter')
                    ->first();

                if (!$counterStock || $counterStock->quantity < $item['quantity']) {
                    DB::rollBack();
                    $availableQuantity = $counterStock ? $counterStock->quantity : 0;
                    return back()->withErrors([
                        'items' => "Insufficient stock for {$productVariant->product->name} ({$productVariant->measurement}). Available: {$availableQuantity} units."
                    ])->withInput();
                }

                $unitPrice = $counterStock->selling_price;
                $itemTotal = $item['quantity'] * $unitPrice;
                $totalAmount += $itemTotal;

                $orderItems[] = [
                    'product_variant' => $productVariant,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                    'notes' => $item['notes'] ?? null,
                    'counter_stock' => $counterStock,
                ];
            }

            // Create order
            $order = BarOrder::create([
                'user_id' => $ownerId,
                'order_number' => $orderNumber,
                'table_id' => $validated['table_id'] ?? null,
                'number_of_people' => $validated['number_of_people'],
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'created_by' => $user->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items and deduct stock
            $transferSaleService = new \App\Services\TransferSaleService();
            
            foreach ($orderItems as $itemData) {
                // Create order item
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $itemData['product_variant']->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price'],
                    'notes' => $itemData['notes'],
                ]);

                // Deduct from counter stock
                $itemData['counter_stock']->decrement('quantity', $itemData['quantity']);

                // Record stock movement
                StockMovement::create([
                    'user_id' => $ownerId,
                    'product_variant_id' => $itemData['product_variant']->id,
                    'movement_type' => 'sale',
                    'from_location' => 'counter',
                    'to_location' => null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'reference_type' => BarOrder::class,
                    'reference_id' => $order->id,
                    'created_by' => $user->id,
                    'notes' => 'Order: ' . $orderNumber,
                ]);
                
                // Attribute sale to transfers using FIFO
                $transferSaleService->attributeSaleToTransfer($orderItem, $ownerId);
            }

            // Table status will be automatically updated via model events

            DB::commit();

            return redirect()->route('bar.orders.show', $order)
                ->with('success', 'Order created successfully. Stock has been deducted from counter.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create order: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified order.
     */
    public function show(BarOrder $order)
    {
        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Check ownership
        if ($order->user_id !== $ownerId) {
            abort(403, 'You do not have access to this order.');
        }

        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view orders.');
        }

        $order->load(['table', 'items.productVariant.product', 'createdBy', 'servedBy', 'payments']);
        
        // Try to fix old orders that have table number in notes but no table_id
        if (!$order->table_id && $order->notes) {
            if (preg_match('/Table Number:\s*([^\|]+)/i', $order->notes, $matches)) {
                $tableNumber = trim($matches[1]);
                $table = BarTable::where('user_id', $ownerId)
                    ->where('table_number', $tableNumber)
                    ->where('is_active', true)
                    ->first();
                
                if ($table) {
                    $order->table_id = $table->id;
                    $order->save();
                    $order->load('table'); // Reload the relationship
                }
            }
        }

        return view('bar.orders.show', compact('order'));
    }

    /**
     * Get order details as JSON (for modal)
     */
    public function getOrderDetails(BarOrder $order)
    {
        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Check ownership
        if ($order->user_id !== $ownerId) {
            return response()->json(['error' => 'You do not have access to this order.'], 403);
        }

        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission to view orders.'], 403);
        }

        $order->load(['table', 'items.productVariant.product', 'createdBy', 'servedBy', 'paidByWaiter', 'payments']);
        
        // Try to fix old orders that have table number in notes but no table_id
        if (!$order->table_id && $order->notes) {
            if (preg_match('/Table Number:\s*([^\|]+)/i', $order->notes, $matches)) {
                $tableNumber = trim($matches[1]);
                $table = BarTable::where('user_id', $ownerId)
                    ->where('table_number', $tableNumber)
                    ->where('is_active', true)
                    ->first();
                
                if ($table) {
                    $order->table_id = $table->id;
                    $order->save();
                    $order->load('table'); // Reload the relationship
                }
            }
        }

        // Parse food items and juice items from notes
        $foodItems = $this->parseFoodItems($order->notes);
        $juiceItems = $this->parseJuiceItems($order->notes);
        
        // Get other items based on order type
        // For food orders, get drinks/juice items
        // For drinks/juice orders, get food items
        $otherItems = [];
        if (strpos($order->notes, 'FOOD ITEMS:') !== false) {
            // This is a food order, get other items (drinks/juice)
            $otherItems = $this->getOtherItems($order);
        } elseif (strpos($order->notes, 'JUICE ITEMS:') !== false) {
            // This is a juice order, get other items (food/drinks)
            $otherItems = $this->getOtherItemsForDrinks($order);
        } else {
            // This is a drinks order, get other items (food/juice)
            $otherItems = $this->getOtherItemsForDrinks($order);
        }

        return response()->json([
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'mobile_money_number' => $order->mobile_money_number,
                'transaction_reference' => $order->transaction_reference,
                'total_amount' => $order->total_amount,
                'paid_amount' => $order->paid_amount,
                'remaining_amount' => $order->remaining_amount ?? ($order->total_amount - $order->paid_amount),
                'customer_name' => $order->customer_name,
                'customer_phone' => $order->customer_phone,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('M d, Y H:i'),
                'served_at' => $order->served_at ? $order->served_at->format('M d, Y H:i') : null,
                'table' => $order->table ? [
                    'table_number' => $order->table->table_number,
                    'table_name' => $order->table->table_name ?? 'Table ' . $order->table->table_number,
                ] : null,
                'created_by' => $order->createdBy ? $order->createdBy->name : 'N/A',
                'served_by' => $order->servedBy ? $order->servedBy->name : null,
                'paid_by_waiter' => $order->paidByWaiter ? $order->paidByWaiter->full_name : null,
                'food_items' => $foodItems,
                'juice_items' => $juiceItems,
                'other_items' => $otherItems,
                'items' => $order->items->map(function($item) {
                    return [
                        'product_name' => $item->productVariant->product->name ?? 'N/A',
                        'variant' => ($item->productVariant->measurement ?? '') . ' - ' . ($item->productVariant->packaging ?? ''),
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                    ];
                }),
            ]
        ]);
    }

    /**
     * Display food orders (for kitchen staff)
     */
    public function foodOrders()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view orders.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        $orders = BarOrder::where('user_id', $ownerId)
            ->where('notes', 'like', '%FOOD ITEMS:%')
            ->with(['table', 'items.productVariant.product', 'createdBy', 'servedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Process orders to extract food items and other items
        $orders->getCollection()->transform(function($order) {
            $order->food_items = $this->parseFoodItems($order->notes);
            $order->other_items = $this->getOtherItems($order);
            return $order;
        });

        return view('bar.orders.food', compact('orders'));
    }

    /**
     * Display drinks orders (for bar staff)
     */
    public function drinksOrders()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view orders.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Drinks orders are those that have product_variant_id items
        // Include orders that have drinks, even if they also have food/juice
        $orders = BarOrder::where('user_id', $ownerId)
            ->whereHas('items', function($q) {
                $q->whereNotNull('product_variant_id');
            })
            ->with(['table', 'items.productVariant.product', 'createdBy', 'servedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Process orders to extract drink items and other items
        $orders->getCollection()->transform(function($order) {
            $order->drink_items = $this->getDrinkItems($order);
            $order->other_items = $this->getOtherItemsForDrinks($order);
            return $order;
        });

        return view('bar.orders.drinks', compact('orders'));
    }

    /**
     * Display juice orders (for juice station staff)
     */
    public function juiceOrders()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view orders.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        $orders = BarOrder::where('user_id', $ownerId)
            ->where('notes', 'like', '%JUICE ITEMS:%')
            ->with(['table', 'items.productVariant.product', 'createdBy', 'servedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Process orders to extract juice items and other items
        $orders->getCollection()->transform(function($order) {
            $order->juice_items = $this->parseJuiceItems($order->notes);
            $order->other_items = $this->getOtherItems($order);
            return $order;
        });

        return view('bar.orders.juice', compact('orders'));
    }

    /**
     * Parse food items from order notes
     */
    private function parseFoodItems($notes)
    {
        $foodItems = [];
        if (strpos($notes, 'FOOD ITEMS:') !== false) {
            $notesParts = explode(' | ', $notes);
            foreach ($notesParts as $part) {
                if (strpos($part, 'FOOD ITEMS:') !== false) {
                    $itemsText = str_replace('FOOD ITEMS: ', '', $part);
                    $itemParts = explode(', ', $itemsText);
                    foreach ($itemParts as $itemPart) {
                        // Parse format: "Qtyx Name (variant) - Tsh price"
                        if (preg_match('/(\d+)x\s+(.+?)\s+\(([^)]+)\)\s+-\s+Tsh\s+([\d,]+)/', $itemPart, $matches)) {
                            $foodItems[] = [
                                'quantity' => (int)$matches[1],
                                'name' => trim($matches[2]),
                                'variant' => trim($matches[3]),
                                'price' => (float)str_replace(',', '', $matches[4])
                            ];
                        } elseif (preg_match('/(\d+)x\s+(.+?)\s+-\s+Tsh\s+([\d,]+)/', $itemPart, $matches)) {
                            $foodItems[] = [
                                'quantity' => (int)$matches[1],
                                'name' => trim($matches[2]),
                                'variant' => '',
                                'price' => (float)str_replace(',', '', $matches[3])
                            ];
                        }
                    }
                }
            }
        }
        return $foodItems;
    }

    /**
     * Parse juice items from order notes
     */
    private function parseJuiceItems($notes)
    {
        $juiceItems = [];
        if (strpos($notes, 'JUICE ITEMS:') !== false) {
            $notesParts = explode(' | ', $notes);
            foreach ($notesParts as $part) {
                if (strpos($part, 'JUICE ITEMS:') !== false) {
                    $itemsText = str_replace('JUICE ITEMS: ', '', $part);
                    $itemParts = explode(', ', $itemsText);
                    foreach ($itemParts as $itemPart) {
                        // Parse format: "Qtyx Name (variant) - Tsh price"
                        if (preg_match('/(\d+)x\s+(.+?)\s+\(([^)]+)\)\s+-\s+Tsh\s+([\d,]+)/', $itemPart, $matches)) {
                            $juiceItems[] = [
                                'quantity' => (int)$matches[1],
                                'name' => trim($matches[2]),
                                'variant' => trim($matches[3]),
                                'price' => (float)str_replace(',', '', $matches[4])
                            ];
                        } elseif (preg_match('/(\d+)x\s+(.+?)\s+-\s+Tsh\s+([\d,]+)/', $itemPart, $matches)) {
                            $juiceItems[] = [
                                'quantity' => (int)$matches[1],
                                'name' => trim($matches[2]),
                                'variant' => '',
                                'price' => (float)str_replace(',', '', $matches[3])
                            ];
                        }
                    }
                }
            }
        }
        return $juiceItems;
    }

    /**
     * Get drink items (product variants) from order
     */
    private function getDrinkItems($order)
    {
        $drinkItems = [];
        foreach ($order->items as $item) {
            if ($item->productVariant && $item->productVariant->product) {
                $drinkItems[] = [
                    'quantity' => $item->quantity,
                    'name' => $item->productVariant->product->name,
                    'variant' => $item->productVariant->measurement,
                    'price' => (float)$item->total_price
                ];
            }
        }
        return $drinkItems;
    }

    /**
     * Get other items (non-food) for food/juice orders
     */
    private function getOtherItems($order)
    {
        $otherItems = [];
        
        // Get drink items (product variants)
        foreach ($order->items as $item) {
            if ($item->productVariant && $item->productVariant->product) {
                $otherItems[] = [
                    'type' => 'drink',
                    'quantity' => $item->quantity,
                    'name' => $item->productVariant->product->name,
                    'variant' => $item->productVariant->measurement,
                    'price' => (float)$item->total_price
                ];
            }
        }
        
        // Get juice items if this is a food order
        if (strpos($order->notes, 'JUICE ITEMS:') !== false) {
            $juiceItems = $this->parseJuiceItems($order->notes);
            foreach ($juiceItems as $item) {
                $otherItems[] = [
                    'type' => 'juice',
                    'quantity' => $item['quantity'],
                    'name' => $item['name'],
                    'variant' => $item['variant'],
                    'price' => $item['price']
                ];
            }
        }
        
        // Get food items if this is a juice order
        if (strpos($order->notes, 'FOOD ITEMS:') !== false) {
            $foodItems = $this->parseFoodItems($order->notes);
            foreach ($foodItems as $item) {
                $otherItems[] = [
                    'type' => 'food',
                    'quantity' => $item['quantity'],
                    'name' => $item['name'],
                    'variant' => $item['variant'],
                    'price' => $item['price']
                ];
            }
        }
        
        return $otherItems;
    }

    /**
     * Get other items (non-drinks) for drinks orders
     */
    private function getOtherItemsForDrinks($order)
    {
        $otherItems = [];
        
        // Get food items
        if (strpos($order->notes, 'FOOD ITEMS:') !== false) {
            $foodItems = $this->parseFoodItems($order->notes);
            foreach ($foodItems as $item) {
                $otherItems[] = [
                    'type' => 'food',
                    'quantity' => $item['quantity'],
                    'name' => $item['name'],
                    'variant' => $item['variant'],
                    'price' => $item['price']
                ];
            }
        }
        
        // Get juice items
        if (strpos($order->notes, 'JUICE ITEMS:') !== false) {
            $juiceItems = $this->parseJuiceItems($order->notes);
            foreach ($juiceItems as $item) {
                $otherItems[] = [
                    'type' => 'juice',
                    'quantity' => $item['quantity'],
                    'name' => $item['name'],
                    'variant' => $item['variant'],
                    'price' => $item['price']
                ];
            }
        }
        
        return $otherItems;
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, BarOrder $order)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit')) {
            abort(403, 'You do not have permission to update orders.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Verify order belongs to owner
        if ($order->user_id !== $ownerId) {
            abort(403, 'You do not have access to this order.');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,served,cancelled'
        ]);

        $order->status = $validated['status'];
        $order->save();

        // Broadcast order update via WebSocket
        event(new \App\Events\OrderUpdated($order));

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'order' => $order->load(['table', 'items.productVariant.product'])
        ]);
    }
}
