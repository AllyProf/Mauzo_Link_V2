<?php

namespace App\Http\Controllers;

use App\Models\BarOrder;
use App\Models\BarTable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\OrderItem;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerOrderController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Get the restaurant owner ID (for Medalion Restaurant)
     * This assumes there's a single restaurant owner
     */
    private function getRestaurantOwnerId()
    {
        // Get the first user with business_type 'bar' or restaurant
        $owner = \App\Models\User::where('business_type', 'bar')
            ->orWhere('business_type', 'restaurant')
            ->orWhere('business_name', 'like', '%Medalion%')
            ->first();
        
        if (!$owner) {
            // Fallback: get first owner user
            $owner = \App\Models\User::where('role', 'owner')->first();
        }
        
        return $owner ? $owner->id : 1; // Default to 1 if no owner found
    }

    /**
     * Show the customer ordering page
     */
    public function index(Request $request)
    {
        // Start session if not already started (don't regenerate token - it causes mismatch)
        if (!$request->hasSession()) {
            $request->session()->start();
        }
        
        $ownerId = $this->getRestaurantOwnerId();

        // Get products with variants that have counter stock
        // Only get products that exist and have valid variants with stock
        $products = Product::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with(['variants' => function($query) {
                $query->where('is_active', true);
            }])
            ->whereHas('variants', function($query) {
                $query->where('is_active', true);
            })
            ->whereHas('variants.stockLocations', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)
                      ->where('location', 'counter')
                      ->where('quantity', '>', 0);
            })
            ->orderBy('category')
            ->orderBy('id', 'asc') // Oldest first (by ID), so new products appear at bottom
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
                'category' => $product->category,
                'description' => $product->description,
                'image' => $product->image,
                'variants' => $variantsWithStock,
            ];
        })->filter(function($product) {
            return $product['variants']->count() > 0;
        })->values();

        // Group by category and maintain order within each category
        // Sort products by ID ascending (oldest first, newest last) before grouping
        $productsWithStock = $productsWithStock->sortBy(function($product) {
            return $product['id'];
        })->values();
        
        // Group by category and maintain order within each category
        $productsByCategory = $productsWithStock->groupBy('category')->map(function($categoryProducts) {
            // Maintain the original order (oldest first, newest last)
            return $categoryProducts->values();
        });
        
        // Separate beverages into Alcoholic and Juice categories
        $alcoholicBeverages = collect();
        $juices = collect();
        
        foreach ($productsByCategory as $category => $products) {
            if (stripos($category, 'alcoholic') !== false) {
                $alcoholicBeverages = $alcoholicBeverages->merge($products);
            } elseif (stripos($category, 'juice') !== false || 
                      stripos($category, 'non-alcoholic') !== false ||
                      stripos($category, 'soft drink') !== false ||
                      stripos($category, 'water') !== false ||
                      stripos($category, 'energy') !== false) {
                $juices = $juices->merge($products);
            }
        }

        // Get active tables for dine-in orders
        $tables = BarTable::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get(['id', 'table_number', 'table_name', 'capacity', 'location']);

        return view('landing.order', compact('productsByCategory', 'productsWithStock', 'alcoholicBeverages', 'juices', 'tables'));
    }

    /**
     * Store customer order
     */
    public function store(Request $request)
    {
        // Log for debugging
        \Log::info('Order submission attempt', [
            'has_token' => $request->has('_token'),
            'token' => $request->input('_token'),
            'session_token' => $request->session()->token(),
            'session_id' => $request->session()->getId(),
        ]);
        
        $ownerId = $this->getRestaurantOwnerId();

        // Get items from JSON
        $itemsJson = $request->input('items_json');
        $items = json_decode($itemsJson, true);
        
        if (!$items || !is_array($items) || count($items) === 0) {
            return back()->withErrors(['items' => 'Please add items to your cart.'])->withInput();
        }

        // Validate order type first to set conditional rules
        $orderType = $request->input('order_type');
        
        // Build validation rules for table_number
        $tableNumberRule = $orderType === 'dine_in' ? 'required|string|max:50|exists:bar_tables,table_number' : 'nullable|string|max:50';
        
        // Add user_id check for table_number validation if dine_in
        if ($orderType === 'dine_in') {
            $tableNumberRule .= ',user_id,' . $ownerId;
        }
        
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_location' => $orderType === 'dine_in' ? 'nullable|string|max:500' : 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'delivery_address' => 'nullable|string|max:500',
            'table_number' => $tableNumberRule,
            'notes' => 'nullable|string',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'promo_code' => 'nullable|string|max:50',
            'payment_preference' => 'nullable|string|in:cash,mobile_money,card,bank_transfer',
        ]);
        
        // Set default location for dine-in orders
        if ($orderType === 'dine_in' && empty($validated['customer_location'])) {
            $validated['customer_location'] = 'Medalion Restaurant - Dine In';
        }
        
        // Validate items manually
        foreach ($items as $item) {
            // Food items have food_item_id, juice items have juice_item_id, regular items have product_variant_id
            $hasVariantId = isset($item['product_variant_id']) && $item['product_variant_id'] !== null;
            $hasFoodItemId = isset($item['food_item_id']) && $item['food_item_id'] !== null;
            $hasJuiceItemId = isset($item['juice_item_id']) && $item['juice_item_id'] !== null;
            
            if ((!$hasVariantId && !$hasFoodItemId && !$hasJuiceItemId) || !isset($item['quantity']) || $item['quantity'] < 1) {
                return back()->withErrors(['items' => 'Invalid item data.'])->withInput();
            }
        }
        
        $validated['items'] = $items;

        DB::beginTransaction();
        try {
            
            $orderNumber = BarOrder::generateOrderNumber($ownerId);

            // Calculate total amount and validate stock
            $totalAmount = 0;
            $orderItems = [];

            foreach ($validated['items'] as $itemData) {
                // Handle food items (hardcoded menu items)
                if (isset($itemData['food_item_id']) && $itemData['food_item_id'] !== null) {
                    $item = [
                        'product_variant_id' => null, // Food items don't have variant IDs
                        'quantity' => $itemData['quantity'],
                        'notes' => $itemData['notes'] ?? null,
                        'food_item_id' => $itemData['food_item_id'],
                        'product_name' => $itemData['product_name'] ?? 'Food Item',
                        'variant_name' => $itemData['variant_name'] ?? '',
                        'price' => $itemData['price'] ?? 0,
                    ];
                    
                    // For food items, use the provided price directly
                    $unitPrice = $item['price'];
                    $itemTotal = $item['quantity'] * $unitPrice;
                    $totalAmount += $itemTotal;
                    
                    // Store food item info for order notes or special handling
                    $orderItems[] = [
                        'product_variant_id' => null,
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? '',
                        'unit_price' => $unitPrice,
                        'total_price' => $itemTotal,
                        'is_food_item' => true,
                        'food_item_name' => $item['product_name'],
                        'food_item_variant' => $item['variant_name'],
                    ];
                    continue;
                }
                
                // Handle juice items (hardcoded items that don't have product_variant_id)
                if (isset($itemData['juice_item_id']) && $itemData['juice_item_id'] !== null) {
                    $item = [
                        'product_variant_id' => null, // Juice items don't have variant IDs
                        'quantity' => $itemData['quantity'],
                        'notes' => $itemData['notes'] ?? null,
                        'juice_item_id' => $itemData['juice_item_id'],
                        'product_name' => $itemData['product_name'] ?? 'Juice Item',
                        'variant_name' => $itemData['variant_name'] ?? '',
                        'price' => $itemData['price'] ?? 0,
                    ];
                    
                    // For juice items, use the provided price directly
                    $unitPrice = $item['price'];
                    $itemTotal = $item['quantity'] * $unitPrice;
                    $totalAmount += $itemTotal;
                    
                    // Store juice item info for order notes or special handling
                    $orderItems[] = [
                        'product_variant_id' => null,
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? '',
                        'unit_price' => $unitPrice,
                        'total_price' => $itemTotal,
                        'is_juice_item' => true,
                        'juice_item_name' => $item['product_name'],
                        'juice_item_variant' => $item['variant_name'],
                    ];
                    continue;
                }
                
                // Handle regular product variants
                $item = [
                    'product_variant_id' => $itemData['product_variant_id'],
                    'quantity' => $itemData['quantity'],
                    'notes' => $itemData['notes'] ?? null,
                ];
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
                    'product_variant_id' => $productVariant->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                    'notes' => $item['notes'],
                    'counter_stock' => $counterStock,
                ];
            }

            // Find table for dine-in orders
            $tableId = null;
            if ($validated['order_type'] === 'dine_in' && !empty($validated['table_number'])) {
                $table = BarTable::where('user_id', $ownerId)
                    ->where('table_number', $validated['table_number'])
                    ->where('is_active', true)
                    ->first();
                
                if ($table) {
                    $tableId = $table->id;
                }
            }
            
            // Create order
            $orderNotes = [];
            if (!empty($validated['notes'])) {
                $orderNotes[] = 'Special Instructions: ' . $validated['notes'];
            }
            $orderNotes[] = 'Order Type: ' . ucfirst(str_replace('_', ' ', $validated['order_type']));
            
            // Add table number for dine-in orders
            if ($validated['order_type'] === 'dine_in' && !empty($validated['table_number'])) {
                $orderNotes[] = 'Table Number: ' . $validated['table_number'];
            }
            
            if (!empty($validated['delivery_address'])) {
                $orderNotes[] = 'Delivery Instructions: ' . $validated['delivery_address'];
            }
            if (!empty($validated['promo_code'])) {
                $orderNotes[] = 'Promo Code: ' . strtoupper($validated['promo_code']);
            }
            if (!empty($validated['payment_preference'])) {
                $paymentMethod = ucfirst(str_replace('_', ' ', $validated['payment_preference']));
                $orderNotes[] = 'Payment Preference: ' . $paymentMethod;
            }
            
            $order = BarOrder::create([
                'user_id' => $ownerId,
                'order_number' => $orderNumber,
                'table_id' => $tableId, // Set table_id for dine-in orders
                'number_of_people' => 1, // Default for customer orders
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_location' => $validated['customer_location'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'created_by' => null, // Customer order, no staff
                'notes' => implode(' | ', $orderNotes),
            ]);

            // Create order items and deduct stock
            $foodItemsNotes = [];
            $juiceItemsNotes = [];
            foreach ($orderItems as $itemData) {
                // Skip food items - they don't have product_variant_id
                if (isset($itemData['is_food_item']) && $itemData['is_food_item']) {
                    $variantInfo = '';
                    if (!empty($itemData['food_item_variant'])) {
                        $variantInfo = $itemData['food_item_variant'];
                    }
                    if (!empty($itemData['notes'])) {
                        $variantInfo = $variantInfo ? $variantInfo . ' - ' . $itemData['notes'] : $itemData['notes'];
                    }
                    $itemNote = $variantInfo ? ' (' . $variantInfo . ')' : '';
                    $foodItemsNotes[] = $itemData['quantity'] . 'x ' . $itemData['food_item_name'] . $itemNote . ' - Tsh ' . number_format($itemData['total_price'], 0);
                    continue;
                }
                
                // Skip juice items - they don't have product_variant_id
                if (isset($itemData['is_juice_item']) && $itemData['is_juice_item']) {
                    $variantInfo = '';
                    if (!empty($itemData['juice_item_variant'])) {
                        $variantInfo = $itemData['juice_item_variant'];
                    }
                    if (!empty($itemData['notes'])) {
                        $variantInfo = $variantInfo ? $variantInfo . ' - ' . $itemData['notes'] : $itemData['notes'];
                    }
                    $itemNote = $variantInfo ? ' (' . $variantInfo . ')' : '';
                    $juiceItemsNotes[] = $itemData['quantity'] . 'x ' . $itemData['juice_item_name'] . $itemNote . ' - Tsh ' . number_format($itemData['total_price'], 0);
                    continue;
                }
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total_price' => $itemData['total_price'],
                    'notes' => $itemData['notes'],
                ]);

                // Deduct stock from counter
                $counterStock = $itemData['counter_stock'];
                $counterStock->decrement('quantity', $itemData['quantity']);

                // Record stock movement
                StockMovement::create([
                    'user_id' => $ownerId,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'movement_type' => 'sale',
                    'from_location' => 'counter',
                    'to_location' => null,
                    'quantity' => $itemData['quantity'],
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'notes' => 'Customer order: ' . $order->order_number,
                ]);
                
                // Attribute sale to transfers using FIFO
                $transferSaleService = new \App\Services\TransferSaleService();
                $transferSaleService->attributeSaleToTransfer($orderItem, $ownerId);
            }
            
            // Update order notes to include food and juice items
            if (!empty($foodItemsNotes)) {
                $order->notes = $order->notes . ' | FOOD ITEMS: ' . implode(', ', $foodItemsNotes);
            }
            if (!empty($juiceItemsNotes)) {
                $order->notes = $order->notes . ' | JUICE ITEMS: ' . implode(', ', $juiceItemsNotes);
            }
            if (!empty($foodItemsNotes) || !empty($juiceItemsNotes)) {
                $order->save();
            }

            DB::commit();

            // Broadcast order creation via WebSocket
            event(new \App\Events\OrderCreated($order));

            // Send SMS notification to customer
            $this->sendOrderConfirmationSms($order, $validated['customer_phone']);

            return redirect()->route('customer.order.success', $order->id)
                ->with('success', 'Your order has been placed successfully! Order Number: ' . $order->order_number . '. A confirmation SMS has been sent to your phone.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to place order. Please try again.'])->withInput();
        }
    }

    /**
     * Send order confirmation SMS to customer
     */
    private function sendOrderConfirmationSms($order, $phoneNumber)
    {
        try {
            $owner = $order->owner;
            $businessName = $owner->business_name ?? 'Medalion Restaurant and Bar';
            
            // Build order items summary
            $itemsSummary = [];
            foreach ($order->items as $item) {
                if ($item->productVariant) {
                    $productName = $item->productVariant->product->name;
                    $variantName = $item->productVariant->measurement;
                    $itemsSummary[] = $item->quantity . 'x ' . $productName . ' (' . $variantName . ')';
                }
            }
            
            // Add food and juice items from notes if available
            if (strpos($order->notes, 'FOOD ITEMS:') !== false || strpos($order->notes, 'JUICE ITEMS:') !== false) {
                $notesParts = explode(' | ', $order->notes);
                foreach ($notesParts as $part) {
                    if (strpos($part, 'FOOD ITEMS:') !== false || strpos($part, 'JUICE ITEMS:') !== false) {
                        $itemsText = str_replace(['FOOD ITEMS: ', 'JUICE ITEMS: '], '', $part);
                        // Parse items from the text (format: "Qtyx Name (variant) - Tsh price")
                        $itemParts = explode(', ', $itemsText);
                        foreach ($itemParts as $itemPart) {
                            if (preg_match('/(\d+)x\s+(.+?)\s+\(/', $itemPart, $matches)) {
                                $itemsSummary[] = $matches[1] . 'x ' . trim($matches[2]);
                            } else {
                                // Fallback: try to extract just the quantity and name
                                if (preg_match('/(\d+)x\s+(.+?)(?:\s+-|$)/', $itemPart, $matches)) {
                                    $itemsSummary[] = $matches[1] . 'x ' . trim($matches[2]);
                                }
                            }
                        }
                    }
                }
            }
            
            $itemsText = !empty($itemsSummary) ? implode(', ', array_slice($itemsSummary, 0, 5)) : 'Various items';
            if (count($itemsSummary) > 5) {
                $itemsText .= ' and ' . (count($itemsSummary) - 5) . ' more';
            }
            
            // Extract order type and table number from notes
            $orderType = 'delivery';
            $tableNumber = null;
            if (!empty($order->notes)) {
                $notesParts = explode(' | ', $order->notes);
                foreach ($notesParts as $part) {
                    if (strpos($part, 'Order Type:') !== false) {
                        $orderType = strtolower(trim(str_replace('Order Type:', '', $part)));
                    }
                    if (strpos($part, 'Table Number:') !== false) {
                        $tableNumber = trim(str_replace('Table Number:', '', $part));
                    }
                }
            }
            
            $message = "HABARI! Asante kwa kuagiza!\n\n";
            $message .= "ODA YAKO IMEKUBALIWA\n\n";
            $message .= "Nambari ya Oda: " . $order->order_number . "\n";
            $message .= "Jina: " . $order->customer_name . "\n";
            $message .= "Simu: " . $order->customer_phone . "\n";
            
            // Add location/table information based on order type
            if ($orderType === 'dine_in' && $tableNumber) {
                $message .= "Meza: " . $tableNumber . "\n";
                $message .= "Mahali: Medalion Restaurant (Dine In)\n\n";
            } elseif ($orderType === 'takeaway') {
                $location = strlen($order->customer_location) > 50 ? substr($order->customer_location, 0, 50) . '...' : $order->customer_location;
                $message .= "Mahali: " . $location . " (Takeaway)\n\n";
            } else {
                // Delivery
                $location = strlen($order->customer_location) > 50 ? substr($order->customer_location, 0, 50) . '...' : $order->customer_location;
                $message .= "Mahali: " . $location . " (Delivery)\n\n";
            }
            
            $message .= "VITU VYA ODA:\n" . $itemsText . "\n\n";
            $message .= "JUMLA: Tsh " . number_format($order->total_amount, 0) . "\n";
            $message .= "Hali: " . strtoupper($order->status) . "\n\n";
            
            // Adjust message based on order type
            if ($orderType === 'dine_in') {
                $message .= "Oda yako itakuja kwenye meza yako hivi karibuni.\n\n";
            } elseif ($orderType === 'takeaway') {
                $message .= "Oda yako itakuwa tayari kwa kuchukua hivi karibuni.\n\n";
            } else {
                $message .= "Tutakupigia simu hivi karibuni kuthibitisha oda yako na kukupatia maelezo ya uwasilishaji.\n\n";
            }
            
            $message .= "Asante kwa kuchagua " . $businessName . "!";
            
            $smsResult = $this->smsService->sendSms($phoneNumber, $message);
            
            // Log SMS result
            \Log::info('Order confirmation SMS sent', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'phone' => $phoneNumber,
                'sms_success' => $smsResult['success'],
                'sms_response' => $smsResult['response'] ?? null
            ]);
            
            return $smsResult;
        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation SMS', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Show order success page
     */
    public function cart(Request $request)
    {
        // Cart items are loaded from localStorage on the frontend
        // This method just renders the cart page
        $ownerId = $this->getRestaurantOwnerId();
        
        // Get active tables for dine-in orders
        $tables = BarTable::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('table_number')
            ->get(['id', 'table_number', 'table_name', 'capacity', 'location']);
        
        return view('landing.cart', compact('tables'));
    }

    public function success($orderId)
    {
        $order = BarOrder::with(['items.productVariant.product', 'owner'])
            ->findOrFail($orderId);

        return view('landing.order-success', compact('order'));
    }
}
