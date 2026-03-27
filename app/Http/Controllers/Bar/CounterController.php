<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\Staff;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StockTransfer;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\KitchenOrderItem;
use App\Models\FoodItem;
use App\Services\TransferSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CounterController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * View Waiter Orders
     */
    public function waiterOrders()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view waiter orders.');
        }

        $ownerId = $this->getOwnerId();

        // Get all orders from waiters
        $orders = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->with(['waiter', 'items.productVariant.product', 'table', 'paidByWaiter', 'orderPayments'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get order counts by status
        $pendingCount = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->where('status', 'pending')
            ->count();

        $servedCount = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->where('status', 'served')
            ->where('payment_status', 'pending')
            ->count();

        // Get all waiters for payment tracking
        $waiters = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->where('name', 'Waiter');
            })
            ->get();

        return view('bar.counter.waiter-orders', compact('orders', 'pendingCount', 'servedCount', 'waiters'));
    }

    /**
     * Update Order Status
     */
    public function updateOrderStatus(Request $request, BarOrder $order)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to update orders.'], 403);
        }

        $ownerId = $this->getOwnerId();
        if ($order->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,served',
        ]);

        DB::beginTransaction();
        try {
            $order->update([
                'status' => $validated['status'],
            ]);

            if ($validated['status'] === 'served') {
                $order->update([
                    'served_at' => now(),
                    'served_by' => $this->getCurrentUser() ? $this->getCurrentUser()->id : null,
                ]);

                // Deduct stock from counter when order is marked as served
                // Only deduct if stock hasn't been deducted for the specific item
                $order->load('items.productVariant');
                
                $transferSaleService = new TransferSaleService();
                $currentUser = $this->getCurrentUser();

                foreach ($order->items as $orderItem) {
                    // Skip if this is a food item (handled by chef) or already served
                    if (!$orderItem->productVariant || !$orderItem->productVariant->product || $orderItem->is_served) {
                        continue;
                    }

                        // Get counter stock for this variant
                        $counterStock = StockLocation::where('user_id', $ownerId)
                            ->where('product_variant_id', $orderItem->product_variant_id)
                            ->where('location', 'counter')
                            ->first();

                        if (!$counterStock) {
                            Log::warning("Counter stock not found for variant {$orderItem->product_variant_id} when serving order {$order->id}");
                            continue;
                        }

                        // Handle stock deduction based on sell type
                        if (($orderItem->sell_type ?? 'unit') === 'tot') {
                            $variant = $orderItem->productVariant;
                            $totsPerBottle = $variant->total_tots ?: 1;
                            $totsNeeded = $orderItem->quantity;
                            
                            // 1. Check for open bottles
                            $openBottle = \App\Models\OpenBottle::where('user_id', $ownerId)
                                ->where('product_variant_id', $orderItem->product_variant_id)
                                ->first();
                                
                            if ($openBottle) {
                                if ($openBottle->tots_remaining >= $totsNeeded) {
                                    // Current open bottle is enough
                                    $openBottle->decrement('tots_remaining', $totsNeeded);
                                    if ($openBottle->tots_remaining <= 0) {
                                        $openBottle->delete();
                                    }
                                    $totsNeeded = 0;
                                } else {
                                    // Use up current open bottle, then need more
                                    $totsNeeded -= $openBottle->tots_remaining;
                                    $openBottle->delete();
                                }
                            }
                            
                            // 2. Open new bottles if needed
                            while ($totsNeeded > 0) {
                                if ($counterStock->quantity < 1) {
                                    DB::rollBack();
                                    return response()->json([
                                        'error' => "Insufficient stock for {$variant->product->name}. No bottles left to open for shots.",
                                    ], 400);
                                }
                                
                                // Open one bottle
                                $counterStock->decrement('quantity', 1);
                                
                                if ($totsNeeded >= $totsPerBottle) {
                                    $totsNeeded -= $totsPerBottle;
                                } else {
                                    // Bottle has remaining tots
                                    \App\Models\OpenBottle::create([
                                        'user_id' => $ownerId,
                                        'product_variant_id' => $variant->id,
                                        'tots_remaining' => $totsPerBottle - $totsNeeded,
                                    ]);
                                    $totsNeeded = 0;
                                }

                                // Record bottle opening as a internal movement or note
                                StockMovement::create([
                                    'user_id' => $ownerId,
                                    'product_variant_id' => $variant->id,
                                    'movement_type' => 'usage',
                                    'from_location' => 'counter',
                                    'to_location' => null,
                                    'quantity' => 1,
                                    'unit_price' => $orderItem->unit_price,
                                    'reference_type' => BarOrder::class,
                                    'reference_id' => $order->id,
                                    'created_by' => $currentUser ? $currentUser->id : null,
                                    'notes' => 'Bottle opened for shots: ' . $order->order_number,
                                ]);
                            }
                        } else {
                            // Standard unit/bottle deduction
                            if ($counterStock->quantity < $orderItem->quantity) {
                                DB::rollBack();
                                return response()->json([
                                    'error' => "Insufficient stock for {$orderItem->productVariant->product->name}. Available: {$counterStock->quantity}, Required: {$orderItem->quantity}",
                                ], 400);
                            }

                            $counterStock->decrement('quantity', $orderItem->quantity);

                            // Record stock movement
                            StockMovement::create([
                                'user_id' => $ownerId,
                                'product_variant_id' => $orderItem->product_variant_id,
                                'movement_type' => 'sale',
                                'from_location' => 'counter',
                                'to_location' => null,
                                'quantity' => $orderItem->quantity,
                                'unit_price' => $orderItem->unit_price,
                                'reference_type' => BarOrder::class,
                                'reference_id' => $order->id,
                                'created_by' => $currentUser ? $currentUser->id : null,
                                'notes' => 'Order served: ' . $order->order_number,
                            ]);
                        }

                        // Attribute sale to transfers using FIFO
                        $transferSaleService->attributeSaleToTransfer($orderItem, $ownerId);

                        // Mark item as served to prevent double deduction
                        $orderItem->update(['is_served' => true]);

                        // Check low stock and notify
                        $this->notifyLowStock($orderItem->product_variant_id, (float)$counterStock->quantity, $ownerId);
                    }
                }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $validated['status'] === 'served' 
                    ? 'Order marked as served. Stock has been deducted from counter.' 
                    : 'Order status updated successfully',
                'order' => $order->load(['waiter', 'items.productVariant.product', 'table']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order status: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update order status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark Order as Paid
     */
    public function markAsPaid(Request $request, BarOrder $order)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to mark orders as paid.'], 403);
        }

        $ownerId = $this->getOwnerId();
        if ($order->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0|max:' . $order->total_amount,
            'waiter_id' => 'nullable|exists:staff,id', // Waiter who collected payment (optional for customer orders)
        ]);

        $paidAmount = $validated['paid_amount'];
        $remainingAmount = $order->total_amount - $paidAmount;

        $updateData = [
            'paid_amount' => $paidAmount,
            'payment_status' => $remainingAmount <= 0 ? 'paid' : 'partial',
        ];

        // Only set paid_by_waiter_id if provided (for waiter orders)
        if (isset($validated['waiter_id']) && $validated['waiter_id']) {
            $updateData['paid_by_waiter_id'] = $validated['waiter_id'];
        }

        $order->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'order' => $order->load(['waiter', 'paidByWaiter', 'items.productVariant.product', 'table']),
        ]);
    }

    /**
     * Get Orders by Status (for filtering)
     */
    public function getOrdersByStatus(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission to view orders.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $status = $request->input('status', 'all');

        $query = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->with(['waiter', 'items.productVariant.product', 'table', 'paidByWaiter']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Get Latest Orders for Real-time Updates
     */
    public function getLatestOrders(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission to view orders.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $lastOrderId = $request->input('last_order_id', 0);

        // Get new orders (pending status only for announcements)
        $newOrders = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->where('status', 'pending')
            ->where('id', '>', $lastOrderId)
            ->with(['waiter', 'items.productVariant.product', 'table'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'waiter_name' => $order->waiter ? $order->waiter->full_name : 'N/A',
                    'table_number' => $order->table ? $order->table->table_number : null,
                    'items' => $order->items->map(function($item) {
                        $productName = $item->productVariant->product->name ?? 'N/A';
                        return [
                            'name' => $productName,
                            'quantity' => $item->quantity,
                        ];
                    })->toArray(),
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at->toDateTimeString(),
                ];
            });

        // Get the latest order ID
        $latestOrderId = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->max('id') ?? 0;

        return response()->json([
            'success' => true,
            'new_orders' => $newOrders,
            'latest_order_id' => $latestOrderId,
        ]);
    }

    /**
     * Counter Dashboard
     */
    public function dashboard()
    {
        // Check permission - allow both bar_orders and inventory permissions
        if (!$this->hasPermission('bar_orders', 'view') && !$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to access counter dashboard.');
        }

        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();

        // Check for active shift
        $activeShift = \App\Models\StaffShift::where('staff_id', $staff->id)
            ->where('status', 'open')
            ->first();

        if (!$activeShift) {
            // Get detailed counter stock similar to counterStock() method
            $counterVariantIds = \App\Models\StockLocation::where('user_id', $ownerId)
                ->where('location', 'counter')
                ->pluck('product_variant_id');

            $variants = ProductVariant::whereIn('id', $counterVariantIds)
                ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'counter');
                }])
                ->get()
                ->map(function($variant) use ($ownerId) {
                    $stock = $variant->stockLocations->where('location', 'counter')->first();
                    $qty = $stock ? (float)$stock->quantity : 0.0;
                    
                    // Add open bottle contents if applicable for precise verification
                    if ($variant->can_sell_in_tots && $variant->total_tots > 0) {
                        $openBottle = \App\Models\OpenBottle::where('user_id', $ownerId)
                            ->where('product_variant_id', $variant->id)
                            ->first();
                        if ($openBottle) {
                            $qty += ($openBottle->tots_remaining / (float)$variant->total_tots);
                        }
                    }

                    return [
                        'id' => $variant->id,
                        'product_name' => $variant->product->name,
                        'variant_name' => $variant->display_name,
                        'variant' => ($variant->measurement ?? '') . ($variant->unit ?? '') . ' - ' . ($variant->packaging ?? ''),
                        'brand' => $variant->product->brand ?? 'N/A',
                        'category' => $variant->product->category ?? 'General',
                        'measurement' => $variant->measurement,
                        'unit' => $variant->unit ?? '',
                        'portion_unit_name' => $variant->portion_unit_name,
                        'quantity' => $qty,
                        'formatted_quantity' => $variant->formatUnits($qty),
                        'quantity_in_tots' => round($qty * ($variant->total_tots ?? 1)),
                        'selling_price' => $stock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                        'selling_price_per_tot' => $stock->selling_price_per_tot ?? $variant->selling_price_per_tot ?? 0,
                        'can_sell_in_tots' => $variant->can_sell_in_tots,
                        'total_tots' => $variant->total_tots,
                        'items_per_package' => $variant->items_per_package ?? 1,
                        'packaging_type' => $variant->packaging ?: 'Bottle',
                        'product_image' => $variant->product->image ?? null,
                        'is_low_stock' => $qty < 10,
                    ];
                })
                ->groupBy(function($v) {
                    return $v['variant_name'] . '|' . $v['measurement'] . '|' . $v['unit'] . '|' . $v['category'];
                })
                ->map(function($group) {
                    $first = $group->first();
                    $totalQty = $group->sum('quantity');
                    $totalTots = $group->sum('quantity_in_tots');
                    
                    // We need a real variant instance to call formatUnits on the total sum
                    $dummyVariant = \App\Models\ProductVariant::find($first['id']);
                    
                    return array_merge($first, [
                        'quantity' => $totalQty,
                        'formatted_quantity' => $dummyVariant ? $dummyVariant->formatUnits($totalQty) : $first['formatted_quantity'],
                        'quantity_in_tots' => $totalTots,
                        'is_low_stock' => $totalQty < 10,
                    ]);
                })
                ->values();

            // Normalize categories to avoid near-duplicate filter pills
            $catNorm = function(string $raw): string {
                $map = [
                    'water' => 'Water', 'drinking water' => 'Water', 'mineral water' => 'Water',
                    'still water' => 'Water', 'sparkling water' => 'Water',
                    'energy' => 'Energy Drinks', 'energies' => 'Energy Drinks',
                    'energy drink' => 'Energy Drinks', 'energy drinks' => 'Energy Drinks',
                    'soft drink' => 'Soft Drinks', 'soft drinks' => 'Soft Drinks',
                    'soda' => 'Soft Drinks', 'sodas' => 'Soft Drinks', 'carbonated' => 'Soft Drinks',
                    'juice' => 'Juice', 'juices' => 'Juice', 'fresh juice' => 'Juice',
                    'beer' => 'Beer', 'beers' => 'Beer', 'lager' => 'Beer',
                    'wine' => 'Wines', 'wines' => 'Wines', 'wine collection' => 'Wines',
                    'red wine' => 'Wines', 'white wine' => 'Wines',
                    'spirit' => 'Spirits', 'spirits' => 'Spirits', 'whiskey' => 'Spirits',
                    'whisky' => 'Spirits', 'vodka' => 'Spirits', 'gin' => 'Spirits',
                    'rum' => 'Spirits', 'tequila' => 'Spirits', 'brandy' => 'Spirits', 'liqueur' => 'Spirits',
                ];
                return $map[strtolower(trim($raw))] ?? ucwords($raw);
            };
            $variants = $variants->map(function($v) use ($catNorm) {
                $v['category'] = $catNorm($v['category']);
                return $v;
            });
            // Define keywords to filter out of brands (avoid duplicates)
            $catKeywords = ['water', 'drinking water', 'mineral water', 'energy', 'energies', 'energizer', 'energizers', 'soft drink', 'soda', 'beer', 'wine', 'spirit', 'juice'];
            
            $categories = $variants->pluck('category')->unique()->sort()->values();
            $brands = $variants->pluck('brand')->unique()->filter(function($b) use ($catKeywords) {
                if(empty($b) || strtolower($b) == 'n/a') return false;
                if(stripos($b, 'bonite') !== false) return false;
                if(in_array(strtolower(trim($b)), $catKeywords)) return false;
                return true;
            })->sort()->values();

            // Get last shift for reference (opening balance)
            $lastShift = \App\Models\StaffShift::where('staff_id', $staff->id)
                ->where('status', 'closed')
                ->latest()
                ->first();
                
            return view('bar.counter.dashboard', [
                'needs_shift' => true,
                'last_closing_balance' => $lastShift ? $lastShift->closing_balance : 0,
                'variants' => $variants,
                'categories' => $categories,
                'brands' => $brands,
                'pendingOrders' => 0,
                'todayRevenue' => 0,
                'counterStockItems' => count($variants),
                'lowStockItems' => 0,
                'lowStockItemsList' => collect([]),
                'tables' => [],
                'waiters' => [],
            ]);
        }

        // Shift stats were moved to closeShiftPage() for a dedicated closer view
        $shiftRevenue = 0;
        $shiftOrderCount = 0;
        $shiftWaiterBreakdown = collect([]);
        $shiftStockRemains = collect([]);

        $todayOrders = BarOrder::where('user_id', $ownerId)
            ->whereDate('created_at', today())
            ->count();

        $pendingOrders = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->whereIn('status', ['pending', 'served'])
            ->where('payment_status', '!=', 'paid')
            ->count();

        $todayRevenue = BarOrder::where('user_id', $ownerId)
            ->whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // Get counter stock statistics
        $counterStockItems = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->whereHas('stockLocations', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)->where('location', 'counter')->where('quantity', '>', 0);
        })
        ->count();

        // Get low stock threshold from settings
        $lowStockThreshold = \App\Models\SystemSetting::get('low_stock_threshold_' . $ownerId, 10);
        
        $lowStockItems = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->whereHas('stockLocations', function($query) use ($ownerId, $lowStockThreshold) {
            $query->where('user_id', $ownerId)
                  ->where('location', 'counter')
                  ->where('quantity', '>', 0)
                  ->where('quantity', '<', $lowStockThreshold);
        })
        ->count();

        // Get low stock threshold from settings
        $lowStockThreshold = \App\Models\SystemSetting::get('low_stock_threshold_' . $ownerId, 10);
        $criticalStockThreshold = \App\Models\SystemSetting::get('critical_stock_threshold_' . $ownerId, 5);
        
        // Removed: Warehouse Stock and Pending Transfers (as warehouse is deprecated for counter model)
        $pendingTransfers = 0;
        $warehouseStockItems = 0;

        // Get low stock items (both warehouse and counter)
        $lowStockItemsList = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        }])
        ->get()
        ->filter(function($variant) use ($lowStockThreshold) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $counterQty = $counterStock ? $counterStock->quantity : 0;
            return $counterQty > 0 && $counterQty < $lowStockThreshold;
        })
        ->take(10)
        ->map(function($variant) use ($ownerId, $criticalStockThreshold) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $counterQty = $counterStock ? $counterStock->quantity : 0;
            
            return [
                'id' => $variant->id,
                'product_name' => $variant->display_name,
                'variant' => $variant->measurement,
                'counter_qty' => $counterQty,
                'is_critical' => $counterQty < $criticalStockThreshold,
            ];
        });

        // Calculate Shift Specific Stats
        $shiftRevenue = BarOrder::where('user_id', $ownerId)
            ->where('shift_id', $activeShift->id)
            ->where('payment_status', 'paid')
            ->sum('total_amount');
            
        $shiftOrderCount = BarOrder::where('user_id', $ownerId)
            ->where('shift_id', $activeShift->id)
            ->count();

        // Get waiters for POS selection
        $waiters = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->whereIn('slug', ['waiter', 'counter']);
            })
            ->get();
        
        $tables = \App\Models\BarTable::where('user_id', $ownerId)->get();
        $variants = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })->with('product')->get();



        // Recent stock transfer requests (Deprecated for this version)
        $recentTransferRequests = collect([]);


        // Recent orders
        $recentOrders = BarOrder::where('user_id', $ownerId)
            ->with(['waiter', 'items.productVariant.product', 'table'])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // --- NEW POS DATA FOR COUNTER ---
        // Get all products with counter stock
        $variants = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('location', 'counter');
        }])
        ->get()
        ->map(function($variant) use ($ownerId) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $category = $variant->product->category ?? '';
            $isAlcoholic = stripos($category, 'alcoholic') !== false;
            
            $fullBottles = $counterStock ? (float)$counterStock->quantity : 0.0;
            $openTotsRemaining = 0;
            
            // Add open bottle contents if applicable
            if ($variant->can_sell_in_tots && $variant->total_tots > 0) {
                $openBottle = \App\Models\OpenBottle::where('user_id', $ownerId)
                    ->where('product_variant_id', $variant->id)
                    ->first();
                if ($openBottle) {
                    $openTotsRemaining = $openBottle->tots_remaining;
                    $fullBottles += ($openTotsRemaining / (float)$variant->total_tots);
                }
            }

            $totalTots = round($fullBottles * ($variant->total_tots ?? 1));

            return [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'variant' => ($variant->measurement ?? '') . ($variant->unit ?? '') . ' - ' . ($variant->packaging ?? ''),
                'measurement' => $variant->measurement,
                'unit' => $variant->unit ?? '',
                'portion_unit_name' => $variant->portion_unit_name,
                'quantity' => $fullBottles,
                'formatted_quantity' => $variant->formatUnits($fullBottles),
                'quantity_in_tots' => $totalTots,
                'selling_price' => $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                'selling_price_per_tot' => $counterStock->selling_price_per_tot ?? $variant->selling_price_per_tot ?? 0,
                'can_sell_in_tots' => $variant->can_sell_in_tots,
                'total_tots' => $variant->total_tots,
                'items_per_package' => $variant->items_per_package ?? 1,
                'packaging_type' => $variant->packaging ?: 'Bottle',
                'category' => $category,
                'is_alcoholic' => $isAlcoholic,
                'product_image' => $variant->product->image ?? null,
                'low_stock_threshold' => $variant->low_stock_threshold ?? 10,
                'is_low_stock' => $fullBottles < ($variant->low_stock_threshold ?? 10),
            ];
        })
        ->filter(function($v) {
            // Hide if total stock (decimal) is 0. 
            // This also keeps partial bottles (e.g. 0.2 bottles/1 glass) visible.
            return $v['quantity'] > 0;
        });

        // Get all active tables
        $tables = \App\Models\BarTable::where('user_id', $ownerId)
            ->where('is_active', true)
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
        // Counter only handles drinks, no food items
        $foodItems = collect([]);

        // Get completed and served orders (for history view in POS)
        $completedOrders = BarOrder::where('user_id', $ownerId)
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
            ->limit(10)
            ->get();

        $staff = $this->getCurrentStaff();

        $waiters = \App\Models\Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->where('name', 'Waiter');
            })
            ->get();

        return view('bar.counter.dashboard', compact(
            'todayOrders',
            'pendingOrders',
            'todayRevenue',
            'counterStockItems',
            'lowStockItems',
            'pendingTransfers',
            'warehouseStockItems',
            'lowStockItemsList',
            'recentTransferRequests',
            'recentOrders',
            'variants',
            'foodItems',
            'tables',
            'completedOrders',
            'staff',
            'waiters',
            'activeShift',
            'shiftRevenue',
            'shiftOrderCount',
            'shiftWaiterBreakdown',
            'shiftStockRemains'
        ));
    }

    /**
     * Store New Shift
     */
    public function storeShift(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();

        $validated = $request->validate([
            'opening_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Check if shift already open
        $existing = \App\Models\StaffShift::where('staff_id', $staff->id)->where('status', 'open')->first();
        if ($existing) {
            return redirect()->back()->with('error', 'You already have an active shift.');
        }

        $openingBalance = $validated['opening_balance'] ?? null;
        
        // If opening balance not provided, use last shift's closing balance
        if ($openingBalance === null) {
            $lastShift = \App\Models\StaffShift::where('staff_id', $staff->id)
                ->where('status', 'closed')
                ->latest()
                ->first();
            $openingBalance = $lastShift ? $lastShift->closing_balance : 0;
        }

        $shift = \App\Models\StaffShift::create([
            'user_id' => $ownerId,
            'staff_id' => $staff->id,
            'shift_number' => \App\Models\StaffShift::generateShiftNumber($ownerId),
            'opening_balance' => $openingBalance,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $validated['notes'],
        ]);

        $this->notifyShiftEvent($shift, 'open');

        return redirect()->route('bar.counter.dashboard')->with('success', 'Shift opened successfully.');
    }

    /**
     * Dedicated Close Shift Page
     */
    public function closeShiftPage()
    {
        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();
        
        $activeShift = \App\Models\StaffShift::where('staff_id', $staff->id)
            ->where('status', 'open')
            ->first();

        if (!$activeShift) {
            return redirect()->route('bar.counter.dashboard')->with('error', 'No active shift found to close.');
        }

        // Calculate stats (revenue, orders, waiters, stock)
        $shiftOrders = BarOrder::where('shift_id', $activeShift->id)->get();
        $shiftRevenue = $shiftOrders->where('payment_status', 'paid')->sum('total_amount');
        $shiftOrderCount = $shiftOrders->count();

        // Waiter Breakdown
        $shiftWaiterBreakdown = $shiftOrders->groupBy('waiter_id')->map(function($orders) use ($staff) {
            $waiterId = $orders->first()->waiter_id;
            $waiter = \App\Models\Staff::find($waiterId);
            return [
                'name' => ($waiterId == $staff->id) ? 'Counter/Self' : ($waiter ? $waiter->full_name : 'Counter/Self'),
                'orders' => $orders->count(),
                'amount' => $orders->where('payment_status', 'paid')->sum('total_amount')
            ];
        })->values();

        // Counter Stock Remains
        $shiftStockRemains = ProductVariant::whereHas('stockLocations', function($q) use ($ownerId) {
            $q->where('user_id', $ownerId)->where('location', 'counter');
        })
        ->with(['product', 'stockLocations' => function($q) use ($ownerId) {
            $q->where('user_id', $ownerId)->where('location', 'counter');
        }])
        ->get()
        ->map(function($v) {
            $s = $v->stockLocations->where('location', 'counter')->first();
            return [
                'name' => $v->display_name,
                'quantity' => $s ? $s->quantity : 0
            ];
        });

        return view('bar.counter.close_shift', compact(
            'activeShift',
            'shiftRevenue',
            'shiftOrderCount',
            'shiftWaiterBreakdown',
            'shiftStockRemains',
            'staff'
        ));
    }

    /**
     * Close Active Shift
     */
    public function closeShift(Request $request)
    {
        $staff = $this->getCurrentStaff();
        $shift = \App\Models\StaffShift::where('staff_id', $staff->id)->where('status', 'open')->first();

        if (!$shift) {
            return redirect()->back()->with('error', 'No active shift found.');
        }

        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Calculate sales during shift
        $orders = \App\Models\BarOrder::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->with('orderPayments')
            ->get();

        $cashSales = 0;
        $digitalSales = 0;

        foreach ($orders as $order) {
            foreach ($order->orderPayments as $payment) {
                if ($payment->payment_method === 'cash') {
                    $cashSales += $payment->amount;
                } else {
                    $digitalSales += $payment->amount;
                }
            }
        }

        $expectedClosing = $shift->opening_balance + $cashSales;

        $shift->update([
            'closing_balance' => $validated['closing_balance'],
            'total_sales_cash' => $cashSales,
            'total_sales_digital' => $digitalSales,
            'expected_closing_balance' => $expectedClosing,
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => ($shift->notes ? $shift->notes . "\n" : "") . "Closure Notes: " . $validated['notes'],
        ]);

        $this->notifyShiftEvent($shift, 'close');

        // Shift history remains accessible, but core workflow now drops directly to Manager Handover view
        return redirect()->route('bar.counter.reconciliation')->with('alert_success', 'Shift #' . $shift->shift_number . ' closed successfully! You can now verify the Waiters and submit your final handover to the Manager.');
    }

    /**
     * Print Shift Reconciliation
     */
    public function printShift($id)
    {
        $ownerId = $this->getOwnerId();
        $shift = \App\Models\StaffShift::where('user_id', $ownerId)->findOrFail($id);
        
        // Fetch all orders for the shift with waiter details
        $orders = \App\Models\BarOrder::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->with(['waiter', 'orderPayments'])
            ->get();

        // Find the specific financial handover created for this shift
        // First try finding directly by staff_shift_id linkage
        $handover = \App\Models\FinancialHandover::where('department', 'bar')
            ->where('user_id', $ownerId)
            ->where('staff_shift_id', $shift->id)
            ->first();
            
        // Fallback for legacy shifts that didn't record staff_shift_id
        if (!$handover) {
            $handover = \App\Models\FinancialHandover::where('department', 'bar')
                ->where('user_id', $ownerId)
                ->whereBetween('handover_date', [
                    $shift->opened_at,
                    $shift->closed_at ? $shift->closed_at->addMinutes(120) : now()->addMinutes(120)
                ])
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return view('bar.counter.print_shift', compact('shift', 'orders', 'handover'));
    }


    /**
     * View Warehouse Stock (available products from stock keeper)
     */
    /**
     * View Shift History (for managers/owners)
     */
    public function shiftHistory(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();
        
        // Managers/Owners with 'financial_reports' permission can see ALL shifts
        $canViewAll = $this->hasPermission('financial_reports', 'view') || !session('is_staff');

        // All staff are allowed to see THEIR OWN shift history
        $query = \App\Models\StaffShift::where('user_id', $ownerId)
            ->with('staff')
            ->orderBy('opened_at', 'desc');

        // If not a manager/owner, force filter to only their own shifts
        if (!$canViewAll) {
            $query->where('staff_id', $staff->id);
        }

        // Apply additional filters if provided (and allowed)
        if ($canViewAll && $request->has('staff_id') && $request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->has('date') && $request->date) {
            $query->whereDate('opened_at', $request->date);
        }

        $shifts = $query->paginate(20);
        $allStaff = \App\Models\Staff::where('user_id', $ownerId)->get();

        // Load manager handover records for displayed shifts (to show audit status)
        $shiftIds = $shifts->pluck('id')->filter()->values()->toArray();
        $handoversByShift = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->whereIn('staff_shift_id', $shiftIds)
            ->get()
            ->keyBy('staff_shift_id');

        // Load manager reconciliation status per shift
        $reconciliationsByShift = \App\Models\WaiterDailyReconciliation::where('user_id', $ownerId)
            ->whereIn('staff_shift_id', $shiftIds)
            ->get()
            ->groupBy('staff_shift_id')
            ->map(function($group) use ($handoversByShift) {
                $shiftId = $group->first()->staff_shift_id;
                $handover = $handoversByShift[$shiftId] ?? null;

                // Priority: Manager's finished audit
                if ($handover && $handover->status === 'verified') {
                    $status = 'verified';
                } else {
                    // MIN(status) check
                    $status = $group->every(fn($r) => $r->status === 'verified') ? 'verified' : $group->first()->status;
                }

                $totalExpected = $group->sum('expected_amount');
                $totalSubmitted = $group->sum('submitted_amount');
                return [
                    'status'    => $status,
                    'expected'  => $totalExpected,
                    'submitted' => $totalSubmitted,
                    'shortage'  => $totalExpected - $totalSubmitted,
                ];
            });

        return view('bar.counter.shift_history', compact('shifts', 'allStaff', 'canViewAll', 'handoversByShift', 'reconciliationsByShift'));
    }

    public function warehouseStock()
    {
        // Check permission - allow inventory view or stock_transfer view, or counter/stock keeper roles
        $canView = $this->hasPermission('inventory', 'view') || $this->hasPermission('stock_transfer', 'view');
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canView && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canView = true;
                }
            }
        }
        
        if (!$canView) {
            abort(403, 'You do not have permission to view warehouse stock.');
        }

        $ownerId = $this->getOwnerId();

        $variants = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        }])
        ->get()
        ->filter(function($variant) {
            $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
            return $warehouseStock && $warehouseStock->quantity > 0;
        });
        $variants = ProductVariant::whereIn('id', $variants->pluck('id'))
            ->get()
            ->map(function($variant) use ($ownerId) {
                $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->where('user_id', $ownerId)->first();
                $counterStock = $variant->stockLocations->where('location', 'counter')->where('user_id', $ownerId)->first();
                return [
                    'id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'brand' => $variant->product->brand ?? 'N/A',
                    'category' => $variant->product->category ?? 'General',
                    'variant' => $variant->measurement,
                    'packaging' => $variant->packaging,
                    'items_per_package' => $variant->items_per_package ?? 1,
                    'product_image' => $variant->product->image,
                    'warehouse_quantity' => $warehouseStock->quantity,
                    'counter_quantity' => $counterStock ? $counterStock->quantity : 0,
                    'buying_price' => $warehouseStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0,
                    'selling_price' => $variant->selling_price_per_unit ?? 0,
                ];
            });

        $categories = $variants->pluck('category')->unique()->sort()->values();
        $brands = $variants->pluck('brand')->unique()->filter()->sort()->values();

        return view('bar.counter.warehouse-stock', compact('variants', 'categories', 'brands'));
    }

    /**
     * View Counter Stock (current counter inventory)
     */
    public function counterStock()
    {
        // Check permission - allow inventory view or stock_transfer view, or counter/stock keeper roles
        $canView = $this->hasPermission('inventory', 'view') || $this->hasPermission('stock_transfer', 'view');
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canView && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canView = true;
                }
            }
        }
        
        if (!$canView) {
            abort(403, 'You do not have permission to view counter stock.');
        }

        $ownerId = $this->getOwnerId();

        // Get all product_variant_ids that have a counter stock entry for this owner
        $counterVariantIds = \App\Models\StockLocation::where('user_id', $ownerId)
            ->where('location', 'counter')
            ->pluck('product_variant_id');

        $variants = ProductVariant::whereIn('id', $counterVariantIds)
            ->whereHas('product', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })
            ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)->where('location', 'counter');
            }])
            ->get()
            ->map(function($variant) use ($ownerId) {
                $counterStock    = $variant->stockLocations->where('location', 'counter')->first();
                $itemsPerPackage = $variant->items_per_package ?? 1;
                $packaging       = $variant->packaging ?? 'Package';
                $fullBottles     = $counterStock ? (float)$counterStock->quantity : 0.0;
                
                // Add open bottle contents if applicable
                $openTotsValue = 0;
                if ($variant->can_sell_in_tots && $variant->total_tots > 0) {
                    $openBottle = \App\Models\OpenBottle::where('user_id', $ownerId)
                        ->where('product_variant_id', $variant->id)
                        ->first();
                    if ($openBottle) {
                        $openTotsValue = $openBottle->tots_remaining;
                        $fullBottles += ($openTotsValue / $variant->total_tots);
                    }
                }

                $quantity         = $fullBottles;
                $packages         = ($itemsPerPackage > 1 && $quantity >= 1) ? floor($quantity / $itemsPerPackage) : 0;
                $remainingBottles = ($itemsPerPackage > 1) ? ($quantity % $itemsPerPackage) : $quantity;

                return [
                    'id'                   => $variant->id,
                    'product_name'         => $variant->product->name,
                    'variant_name'         => $variant->name,
                    'product_image'        => $variant->product->image,
                    'brand'                => $variant->product->brand ?? 'N/A',
                    'category'             => (stripos($variant->product->category ?? '', 'Wine Collection') !== false || strtolower($variant->product->category ?? '') === 'wine') ? 'Wines' : ($variant->product->category ?? 'General'),
                    'variant'              => $variant->measurement,
                    'unit'                 => $variant->unit ?? '',
                    'quantity'             => $quantity,
                    'formatted_quantity'   => $variant->formatUnits($quantity),
                    'items_per_package'    => $itemsPerPackage,
                    'packaging'            => $packaging,
                    'packages'             => (int)$packages,
                    'remaining_bottles'    => $remainingBottles,
                    'selling_price'        => $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                    'selling_price_per_tot'=> $counterStock->selling_price_per_tot ?? $variant->selling_price_per_tot ?? 0,
                    'quantity_in_tots'     => round($quantity * ($variant->total_tots ?? 1)),
                    'can_sell_in_tots'     => $variant->can_sell_in_tots && ($variant->total_tots > 0),
                    'buying_price'         => $counterStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0,
                    'packaging_type'       => $variant->packaging ?: 'Bottle',
                    'portion_unit_name'    => $variant->portion_unit_name,
                    'low_stock_threshold'  => $variant->low_stock_threshold ?? 10,
                    'is_low_stock'         => $quantity < ($variant->low_stock_threshold ?? 10),
                ];
            });

        // Normalize categories so near-identical names (e.g. "Water"/"Drinking Water") are merged
        $categoryMap = [
            // Water variants
            'water'           => 'Water',
            'drinking water'  => 'Water',
            'mineral water'   => 'Water',
            'still water'     => 'Water',
            'sparkling water' => 'Water',
            // Energy drinks
            'energy'          => 'Energies',
            'energies'        => 'Energies',
            'energizer'       => 'Energies',
            'energizers'      => 'Energies',
            'energy drink'    => 'Energies',
            'energy drinks'   => 'Energies',
            // Soft drinks / sodas
            'soft drink'      => 'Soft Drinks',
            'soft drinks'     => 'Soft Drinks',
            'soda'            => 'Soft Drinks',
            'sodas'           => 'Soft Drinks',
            'carbonated'      => 'Soft Drinks',
            // Juice
            'juice'           => 'Juice',
            'juices'          => 'Juice',
            'fresh juice'     => 'Juice',
            // Beer
            'beer'            => 'Beers',
            'beers'           => 'Beers',
            'lager'           => 'Beers',
            // Wines
            'wine'            => 'Wines',
            'wines'           => 'Wines',
            'wine collection' => 'Wines',
            'red wine'        => 'Wines',
            'white wine'      => 'Wines',
            // Spirits / Whiskey
            'spirit'          => 'Spirits',
            'spirits'         => 'Spirits',
            'whiskey'         => 'Spirits',
            'whisky'          => 'Spirits',
            'vodka'           => 'Spirits',
            'gin'             => 'Spirits',
            'rum'             => 'Spirits',
            'tequila'         => 'Spirits',
            'brandy'          => 'Spirits',
            'liqueur'         => 'Spirits',
        ];

        $normalizeCategory = function(string $raw) use ($categoryMap): string {
            $lower = strtolower(trim($raw));
            return $categoryMap[$lower] ?? ucwords($raw);
        };

        // Apply normalization to each variant's category
        $variants = $variants->map(function($v) use ($normalizeCategory) {
            $v['category'] = $normalizeCategory($v['category']);
            return $v;
        });

        // Filter variants to only include those with stock (quantity > 0)
        // This ensures "received products" are shown and empty categories are hidden
        $variants = $variants->filter(function($v) {
            return $v['quantity'] > 0;
        });

        $totalValue = $variants->sum(fn($v) => $v['quantity'] * $v['selling_price']);

        // Get unique normalized categories for filter pills based on items in stock
        $categories = $variants->pluck('category')->unique()->sort()->values();
        
        // Define a list of "dirty" brand descriptors that should be ignored if they're just categories
        $categoryKeywords = array_map('strtolower', array_keys($categoryMap));
        $categoryValues   = array_map('strtolower', array_values($categoryMap));
        $allBadKeywords   = array_unique(array_merge($categoryKeywords, $categoryValues));

        $brands = $variants->pluck('brand')->unique()->filter(function($b) use ($allBadKeywords) {
            if (empty($b) || strtolower($b) == 'n/a') return false;
            // Exclude 'bonite' as per existing logic
            if (stripos($b, 'bonite') !== false) return false;
            // Exclude brands that are just category names (e.g. "Drinking Water", "Energizers")
            if (in_array(strtolower(trim($b)), $allBadKeywords)) return false;
            return true;
        })->sort()->values();

        return view('bar.counter.counter-stock', compact('variants', 'totalValue', 'categories', 'brands'));
    }

    /**
     * Delete / Zero-out a product variant from Counter Stock
     */
    public function deleteCounterStock(Request $request, $variantId)
    {
        $ownerId = $this->getOwnerId();

        $stockLocation = StockLocation::where('user_id', $ownerId)
            ->where('product_variant_id', $variantId)
            ->where('location', 'counter')
            ->first();

        if (!$stockLocation) {
            return response()->json(['success' => false, 'message' => 'Product not found in counter stock.'], 404);
        }

        $stockLocation->delete();

        return response()->json(['success' => true, 'message' => 'Product removed from counter stock successfully.']);
    }

    /**
     * Update low stock threshold for a product variant
     */
    public function updateLowStockThreshold(Request $request, $variantId)
    {
        $ownerId = $this->getOwnerId();
        
        $variant = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })->findOrFail($variantId);

        $validated = $request->validate([
            'threshold' => 'required|integer|min:0',
        ]);

        $variant->update([
            'low_stock_threshold' => $validated['threshold'],
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Low stock threshold updated successfully.',
            'threshold' => $variant->low_stock_threshold,
        ]);
    }



    public function requestStockTransfer(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('stock_transfer', 'create')) {
            return response()->json(['error' => 'You do not have permission to request stock transfers.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();

        $validated = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity_requested' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $variant = ProductVariant::with(['product', 'stockLocations' => function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            }])->findOrFail($validated['variant_id']);

            $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
            
            if (!$warehouseStock || $warehouseStock->quantity < ($validated['quantity_requested'] * ($variant->items_per_package ?? 1))) {
                throw new \Exception("Insufficient warehouse stock for {$variant->product->name}");
            }

            // Calculate total units
            $totalUnits = $validated['quantity_requested'] * ($variant->items_per_package ?? 1);

            // Generate transfer number
            $transferNumber = StockTransfer::generateTransferNumber($ownerId);

            // Get owner user ID
            $ownerUser = $this->getCurrentUser();
            
            // Create stock transfer request
            $transfer = StockTransfer::create([
                'user_id' => $ownerId,
                'product_variant_id' => $variant->id,
                'transfer_number' => $transferNumber,
                'quantity_requested' => $validated['quantity_requested'],
                'total_units' => $totalUnits,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'requested_by' => $ownerUser ? $ownerUser->id : null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock transfer request submitted successfully',
                'transfer' => $transfer->load('productVariant.product'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Product Trends and Revenue Analytics
     */
    public function analytics()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view analytics.');
        }

        $ownerId = $this->getOwnerId();

        // Get sales data for last 30 days
        $salesData = BarOrder::where('user_id', $ownerId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get top selling products
        $topProducts = OrderItem::whereHas('order', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where('payment_status', 'paid')
                  ->where('created_at', '>=', now()->subDays(30));
        })
        ->select(
            'product_variant_id',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(total_price) as total_revenue')
        )
        ->groupBy('product_variant_id')
        ->orderBy('total_quantity', 'desc')
        ->limit(10)
        ->with('productVariant.product')
        ->get();

        // Calculate expected revenue (based on counter stock)
        $counterStock = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->whereHas('stockLocations', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)->where('location', 'counter')->where('quantity', '>', 0);
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)->where('location', 'counter');
        }])
        ->get()
        ->map(function($variant) {
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $sellingPrice = $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0;
            return [
                'product_name' => $variant->product->name,
                'quantity' => $counterStock->quantity,
                'selling_price' => $sellingPrice,
                'potential_revenue' => $counterStock->quantity * $sellingPrice,
            ];
        });

        $expectedRevenue = $counterStock->sum('potential_revenue');

        // Revenue by day of week
        $revenueByDay = BarOrder::where('user_id', $ownerId)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DAYNAME(created_at) as day_name'),
                DB::raw('DAYOFWEEK(created_at) as day_number'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('day_name', 'day_number')
            ->orderBy('day_number')
            ->get();

        return view('bar.counter.analytics', compact(
            'salesData',
            'topProducts',
            'counterStock',
            'expectedRevenue',
            'revenueByDay'
        ));
    }

    /**
     * View Customer Orders (direct orders from customers)
     */
    public function customerOrders()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view customer orders.');
        }

        $ownerId = $this->getOwnerId();

        // Get orders without waiter_id (direct customer orders)
        $orders = BarOrder::where('user_id', $ownerId)
            ->whereNull('waiter_id')
            ->with(['items.productVariant.product', 'table'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get order counts
        $pendingCount = BarOrder::where('user_id', $ownerId)
            ->whereNull('waiter_id')
            ->where('status', 'pending')
            ->count();

        $preparedCount = BarOrder::where('user_id', $ownerId)
            ->whereNull('waiter_id')
            ->where('status', 'prepared')
            ->count();

        $servedCount = BarOrder::where('user_id', $ownerId)
            ->whereNull('waiter_id')
            ->where('status', 'served')
            ->where('payment_status', 'pending')
            ->count();

        return view('bar.counter.customer-orders', compact('orders', 'pendingCount', 'preparedCount', 'servedCount'));
    }

    /**
     * View Pending Stock Transfer Requests
     */
    public function stockTransferRequests()
    {
        // Check permission
        if (!$this->hasPermission('stock_transfer', 'view')) {
            abort(403, 'You do not have permission to view stock transfer requests.');
        }

        $ownerId = $this->getOwnerId();

        $transfers = StockTransfer::where('user_id', $ownerId)
            ->with(['productVariant.product', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('bar.counter.stock-transfer-requests', compact('transfers'));
    }

    /**
     * Show voice recording page
     */
    public function recordVoice()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to record voice announcements.');
        }

        return view('bar.counter.record-voice');
    }

    /**
     * Save voice clip
     */
    public function saveVoiceClip(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission.'], 403);
        }

        // Validate based on whether it's a file upload or base64
        if ($request->hasFile('audio_file')) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|in:static,number,waiter,product',
                'audio_file' => 'required|file|mimes:mp3,wav,ogg,webm,m4a,aac|max:10240', // 10MB max
            ]);
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|in:static,number,waiter,product',
                'audio' => 'required|string', // Base64 encoded audio
            ]);
        }

        $ownerId = $this->getOwnerId();

        try {
            $audioBinary = null;
            $extension = 'webm';
            
            // Check if it's a file upload or base64
            if ($request->hasFile('audio_file')) {
                // Handle file upload
                $file = $request->file('audio_file');
                $extension = $file->getClientOriginalExtension();
                // Validate extension
                $allowedExtensions = ['mp3', 'wav', 'ogg', 'webm', 'm4a', 'aac'];
                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid file format. Allowed: ' . implode(', ', $allowedExtensions),
                    ], 400);
                }
                $audioBinary = file_get_contents($file->getRealPath());
            } else {
                // Handle base64 audio
                $audioData = $request->input('audio');
                
                // Extract MIME type and data from data URI
                if (preg_match('/data:audio\/([^;]+);base64,(.+)/', $audioData, $matches)) {
                    $extension = $matches[1]; // e.g., webm, mp3, wav
                    $audioData = $matches[2];
                } elseif (strpos($audioData, 'data:audio') === 0) {
                    // Fallback: extract after comma
                    $parts = explode(',', $audioData);
                    if (count($parts) > 1) {
                        $audioData = $parts[1];
                        // Try to detect extension from MIME type
                        if (preg_match('/data:audio\/([^;]+)/', $parts[0], $mimeMatch)) {
                            $extension = $mimeMatch[1];
                        }
                    }
                }
                
                $audioBinary = base64_decode($audioData);
            }

            if (!$audioBinary) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid audio data',
                ], 400);
            }

            // Generate filename with proper extension
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $directory = public_path('storage/voice-clips');
            
            // Create directory if it doesn't exist
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Save audio file
            $filePath = $directory . '/' . $filename;
            file_put_contents($filePath, $audioBinary);

            // Save to database
            $voiceClip = \App\Models\VoiceClip::create([
                'user_id' => $ownerId,
                'name' => $validated['name'],
                'category' => $validated['category'],
                'audio_path' => 'voice-clips/' . $filename,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voice clip saved successfully',
                'clip' => $voiceClip,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to save voice clip: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get voice clips
     */
    public function getVoiceClips()
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission.'], 403);
        }

        $ownerId = $this->getOwnerId();

        $clips = \App\Models\VoiceClip::where('user_id', $ownerId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($clip) {
                return [
                    'id' => $clip->id,
                    'name' => $clip->name,
                    'category' => $clip->category,
                    'audio_url' => asset('storage/' . $clip->audio_path),
                    'created_at' => $clip->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'clips' => $clips,
        ]);
    }

    /**
     * Update voice clip
     */
    public function updateVoiceClip(Request $request, $id)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission.'], 403);
        }

        $ownerId = $this->getOwnerId();

        // Find the clip
        $clip = \App\Models\VoiceClip::where('id', $id)
            ->where('user_id', $ownerId)
            ->first();

        if (!$clip) {
            return response()->json(['error' => 'Voice clip not found.'], 404);
        }

        // Validate based on whether it's a file upload or base64
        if ($request->hasFile('audio_file')) {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'category' => 'sometimes|in:static,number,waiter,product',
                'audio_file' => 'required|file|mimes:mp3,wav,ogg,webm,m4a,aac|max:10240', // 10MB max
            ]);
        } else {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'category' => 'sometimes|in:static,number,waiter,product',
                'audio' => 'required|string', // Base64 encoded audio
            ]);
        }

        try {
            $audioBinary = null;
            $extension = pathinfo($clip->audio_path, PATHINFO_EXTENSION); // Keep original extension if no new audio
            
            // Check if it's a file upload or base64
            if ($request->hasFile('audio_file')) {
                // Handle file upload
                $file = $request->file('audio_file');
                $extension = $file->getClientOriginalExtension();
                // Validate extension
                $allowedExtensions = ['mp3', 'wav', 'ogg', 'webm', 'm4a', 'aac'];
                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid file format. Allowed: ' . implode(', ', $allowedExtensions),
                    ], 400);
                }
                $audioBinary = file_get_contents($file->getRealPath());
            } else if ($request->has('audio')) {
                // Handle base64 audio
                $audioData = $request->input('audio');
                
                // Extract MIME type and data from data URI
                if (preg_match('/data:audio\/([^;]+);base64,(.+)/', $audioData, $matches)) {
                    $extension = $matches[1]; // e.g., webm, mp3, wav
                    $audioData = $matches[2];
                } elseif (strpos($audioData, 'data:audio') === 0) {
                    // Fallback: extract after comma
                    $parts = explode(',', $audioData);
                    if (count($parts) > 1) {
                        $audioData = $parts[1];
                        // Try to detect extension from MIME type
                        if (preg_match('/data:audio\/([^;]+)/', $parts[0], $mimeMatch)) {
                            $extension = $mimeMatch[1];
                        }
                    }
                }
                
                $audioBinary = base64_decode($audioData);
            }

            // Update name and category if provided
            if (isset($validated['name'])) {
                $clip->name = $validated['name'];
            }
            if (isset($validated['category'])) {
                $clip->category = $validated['category'];
            }

            // If new audio provided, replace the file
            if ($audioBinary) {
                // Delete old audio file
                $oldFilePath = public_path('storage/' . $clip->audio_path);
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }

                // Generate new filename with proper extension
                $filename = time() . '_' . uniqid() . '.' . $extension;
                $directory = public_path('storage/voice-clips');
                
                // Create directory if it doesn't exist
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                // Save new audio file
                $filePath = $directory . '/' . $filename;
                file_put_contents($filePath, $audioBinary);

                // Update audio path
                $clip->audio_path = 'voice-clips/' . $filename;
            }

            $clip->save();

            return response()->json([
                'success' => true,
                'message' => 'Voice clip updated successfully',
                'clip' => $clip,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update voice clip: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete voice clip
     */
    public function deleteVoiceClip($id)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'You do not have permission.'], 403);
        }

        $ownerId = $this->getOwnerId();

        $clip = \App\Models\VoiceClip::where('id', $id)
            ->where('user_id', $ownerId)
            ->first();

        if (!$clip) {
            return response()->json(['error' => 'Voice clip not found.'], 404);
        }

        // Delete audio file
        $filePath = public_path('storage/' . $clip->audio_path);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete from database
        $clip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voice clip deleted successfully',
        ]);
    }

    /**
     * Send SMS notification for shift open/close
     */
    private function notifyShiftEvent($shift, $type)
    {
        $ownerId = $shift->user_id;
        $staff = $shift->staff;
        $staffName = $staff ? $staff->full_name : 'Staff';
        
        $eventType = strtoupper($type);
        $time = $shift->updated_at->format('M d, Y H:i');
        
        if ($type === 'open') {
            $message = "SHIFT OPENED - MauzoLink\n\nStaff: {$staffName}\nShift #: {$shift->shift_number}\nOpening Bal: " . number_format($shift->opening_balance, 0) . "\nTime: {$time}";
        } else {
            $message = "SHIFT CLOSED - MauzoLink\n\nStaff: {$staffName}\nShift #: {$shift->shift_number}\nCash Sales: " . number_format($shift->total_sales_cash, 0) . "\nDigital Sales: " . number_format($shift->total_sales_digital, 0) . "\nClosing Bal: " . number_format($shift->closing_balance, 0) . "\nTime: {$time}\n\nReconciliation is pending.";
        }

        $smsService = new \App\Services\SmsService();

        // Notify Managers
        $managers = \App\Models\Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->where('slug', 'manager');
            })->get();

        // Notify Counter Staff (excluding sender if you want, but user said "both")
        $counterStaff = \App\Models\Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->whereIn('slug', ['counter', 'bar-counter', 'bar_counter']);
            })->get();

        $phoneNumbers = $managers->pluck('phone_number')->merge($counterStaff->pluck('phone_number'))->unique()->filter();

        foreach ($phoneNumbers as $phone) {
            $smsService->sendSms($phone, $message);
        }
    }

    /**
     * Send SMS notification when stock is low
     */
    private function notifyLowStock($variantId, $currentQty, $ownerId)
    {
        $variant = ProductVariant::with('product')->find($variantId);
        if (!$variant) return;

        $threshold = $variant->low_stock_threshold ?? 10;
        
        // Only notify if we just crossed the threshold
        if ($currentQty < $threshold) {
            $smsService = new \App\Services\SmsService();
            $productName = $variant->display_name;
            $message = "LOW STOCK ALERT - MauzoLink\n\nItem: {$productName}\nRemaining: " . $variant->formatUnits($currentQty) . "\nThreshold: {$threshold}\n\nPlease restock soon.";

            // 1. Notify Managers
            $managers = \App\Models\Staff::where('user_id', $ownerId)
                ->where('is_active', true)
                ->whereHas('role', function($q) {
                    $q->where('slug', 'manager');
                })->get();

            // 2. Notify Current Counter Staff
            $counterStaff = \App\Models\Staff::where('user_id', $ownerId)
                ->where('is_active', true)
                ->whereHas('role', function($q) {
                    $q->whereIn('slug', ['counter', 'bar-counter', 'bar_counter']);
                })->get();

            $phoneNumbers = $managers->pluck('phone_number')->merge($counterStaff->pluck('phone_number'))->unique()->filter();

            foreach ($phoneNumbers as $phone) {
                $smsService->sendSms($phone, $message);
            }
        }
    }

    /**
     * Create Order from Counter
     */
    public function createOrder(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'create')) {
            return response()->json(['error' => 'You do not have permission to create orders.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();
        
        if (!$staff || !$staff->is_active) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate items
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'table_id' => 'nullable|exists:bar_tables,id',
            'waiter_id' => 'nullable|exists:staff,id',
            'existing_order_id' => 'nullable|exists:orders,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'order_notes' => 'nullable|string|max:1000',
        ]);
        
        DB::beginTransaction();
        try {
            $existingOrderId = $request->input('existing_order_id');
            $existingOrder = $existingOrderId ? BarOrder::find($existingOrderId) : null;
            
            // Calculate total and prepare items
            $totalAmount = 0;
            $orderItems = [];
            $kitchenOrderItems = [];
            $foodItemsNotes = [];

            foreach ($request->input('items') as $item) {
                // Handle food items
                if (isset($item['food_item_id']) && $item['food_item_id'] !== null) {
                    $unitPrice = (float)$item['price'];
                    $quantity = (int)$item['quantity'];
                    $itemTotal = $quantity * $unitPrice;
                    $totalAmount += $itemTotal;
                    
                    $kitchenOrderItems[] = [
                        'food_item_id' => $item['food_item_id'],
                        'food_item_name' => $item['product_name'] ?? 'Food Item',
                        'variant_name' => $item['variant_name'] ?? null,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $itemTotal,
                        'special_instructions' => $item['notes'] ?? null,
                        'status' => 'pending',
                    ];
                    
                    $foodItemNote = $quantity . 'x ' . ($item['product_name'] ?? 'Food Item') . 
                                   (isset($item['variant_name']) && $item['variant_name'] ? ' (' . $item['variant_name'] . ')' : '') . 
                                   ' - Tsh ' . number_format($unitPrice, 0);
                    
                    if (isset($item['notes']) && $item['notes']) {
                        $foodItemNote .= ' [Note: ' . $item['notes'] . ']';
                    }
                    
                    $foodItemsNotes[] = $foodItemNote;
                    continue;
                }
                
                // Handle Regular product variants (drinks)
                if (!isset($item['variant_id'])) continue;

                $sellType = $item['sell_type'] ?? 'unit';
                $variant = ProductVariant::with(['product', 'stockLocations' => function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'counter');
                }])->findOrFail($item['variant_id']);

                $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                if (!$counterStock) {
                    throw new \Exception("Counter stock not found for {$variant->product->name}");
                }

                // Accurate stock check for shots vs units (Match Waiter logic)
                if ($sellType === 'tot') {
                    $totsPerBottle = $variant->total_tots ?: 1;
                    $openBottle = \App\Models\OpenBottle::where('user_id', $ownerId)
                        ->where('product_variant_id', $variant->id)
                        ->first();
                    
                    $totalTotsAvailable = ($counterStock->quantity * $totsPerBottle) + ($openBottle ? $openBottle->tots_remaining : 0);
                    
                    if ($totalTotsAvailable < $item['quantity']) {
                        throw new \Exception("Insufficient shots for {$variant->product->name}. [Available: {$totalTotsAvailable}]");
                    }
                } else {
                    if ($counterStock->quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for {$variant->product->name}");
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
            
            // Build order notes
            $notesParts = [];
            if (!empty($foodItemsNotes)) {
                $notesParts[] = 'FOOD ITEMS: ' . implode(', ', $foodItemsNotes);
            }
            if (!empty($validated['order_notes'])) {
                $notesParts[] = 'ORDER NOTES: ' . $validated['order_notes'];
            }
            $newNotes = implode(' | ', $notesParts);

            if ($existingOrder) {
                // UPDATE EXISTING ORDER
                $existingOrder->total_amount += $totalAmount;
                if (!empty($newNotes)) {
                    $existingOrder->notes = ($existingOrder->notes ? $existingOrder->notes . ' | ' : '') . $newNotes;
                }
                // Maintain served status for counter orders
                $existingOrder->status = 'served';
                $existingOrder->save();
                $order = $existingOrder;
                $message = 'Items added to existing order successfully';
            } else {
                // CREATE NEW ORDER
                $orderNumber = BarOrder::generateOrderNumber($ownerId);
                
                // Get active shift
                $activeShift = \App\Models\StaffShift::where('staff_id', $staff->id)
                    ->where('status', 'open')
                    ->first();
                    
                $order = BarOrder::create([
                    'user_id' => $ownerId,
                    'order_number' => $orderNumber,
                    'waiter_id' => !empty($validated['waiter_id']) ? $validated['waiter_id'] : $staff->id,
                    'order_source' => 'counter',
                    'shift_id' => $activeShift ? $activeShift->id : null,
                    'table_id' => $validated['table_id'] ?? null,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'status' => 'served',
                    'payment_status' => 'pending',
                    'total_amount' => $totalAmount,
                    'paid_amount' => 0,
                    'notes' => $newNotes,
                ]);
                $message = 'Order created successfully';
            }

            // Create items, deduct stock, and attribute via service
            $transferSaleService = new \App\Services\TransferSaleService();
            $currentUser = $this->getCurrentUser();

            foreach ($orderItems as $item) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'sell_type' => $item['sell_type'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'is_served' => true, // Counter orders are served immediately
                ]);

                // --- DEDUCT STOCK IMMEDIATELY FOR COUNTER ORDERS ---
                $counterStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $orderItem->product_variant_id)
                    ->where('location', 'counter')
                    ->first();

                if ($counterStock) {
                    if ($orderItem->sell_type === 'tot') {
                        $variant = ProductVariant::find($orderItem->product_variant_id);
                        $totsPerBottle = $variant->total_tots ?: 1;
                        $totsNeeded = $orderItem->quantity;
                        
                        $openBottle = \App\Models\OpenBottle::where('user_id', $ownerId)
                            ->where('product_variant_id', $orderItem->product_variant_id)
                            ->first();
                            
                        if ($openBottle) {
                            if ($openBottle->tots_remaining >= $totsNeeded) {
                                $openBottle->decrement('tots_remaining', $totsNeeded);
                                if ($openBottle->tots_remaining <= 0) $openBottle->delete();
                                $totsNeeded = 0;
                            } else {
                                $totsNeeded -= $openBottle->tots_remaining;
                                $openBottle->delete();
                            }
                        }
                        
                        while ($totsNeeded > 0) {
                            $counterStock->decrement('quantity', 1);
                            if ($totsNeeded >= $totsPerBottle) {
                                $totsNeeded -= $totsPerBottle;
                            } else {
                                \App\Models\OpenBottle::create([
                                    'user_id' => $ownerId,
                                    'product_variant_id' => $orderItem->product_variant_id,
                                    'tots_remaining' => $totsPerBottle - $totsNeeded,
                                ]);
                                $totsNeeded = 0;
                            }

                            StockMovement::create([
                                'user_id' => $ownerId,
                                'product_variant_id' => $orderItem->product_variant_id,
                                'movement_type' => 'sale',
                                'from_location' => 'counter',
                                'to_location' => null,
                                'quantity' => 1,
                                'unit_price' => $orderItem->unit_price,
                                'reference_type' => BarOrder::class,
                                'reference_id' => $order->id,
                                'created_by' => $currentUser ? $currentUser->id : null,
                                'notes' => 'Bottle opened for POS shots: ' . $order->order_number,
                            ]);
                        }
                    } else {
                        $counterStock->decrement('quantity', $orderItem->quantity);
                        StockMovement::create([
                            'user_id' => $ownerId,
                            'product_variant_id' => $orderItem->product_variant_id,
                            'movement_type' => 'sale',
                            'from_location' => 'counter',
                            'to_location' => null,
                            'quantity' => $orderItem->quantity,
                            'unit_price' => $orderItem->unit_price,
                            'reference_type' => BarOrder::class,
                            'reference_id' => $order->id,
                            'created_by' => $currentUser ? $currentUser->id : null,
                            'notes' => 'POS Order served: ' . $order->order_number,
                        ]);
                    }
                    // Check low stock and notify
                    $this->notifyLowStock($orderItem->product_variant_id, (float)$counterStock->quantity, $ownerId);
                }

                // ----------------------------------------------------

                $transferSaleService->attributeSaleToTransfer($orderItem, $ownerId);
            }

            foreach ($kitchenOrderItems as $item) {
                KitchenOrderItem::create([
                    'order_id' => $order->id,
                    'food_item_id' => $item['food_item_id'],
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

            // Send SMS notifications
            try {
                $smsService = new \App\Services\WaiterSmsService();
                $smsService->sendOrderNotification($order);
                
                if ($order->customer_phone) {
                    $smsService->sendCustomerOrderConfirmation($order);
                }
            } catch (\Exception $e) {
                Log::error('SMS notification failed in counter', ['id' => $order->id, 'err' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'order' => $order->load(['items.productVariant.product', 'table']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Request $request, BarOrder $order)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to cancel orders.'], 403);
        }

        $ownerId = $this->getOwnerId();
        if ($order->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'pending' && $order->status !== 'served') {
            return response()->json(['error' => 'Only pending or served orders can be cancelled'], 400);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Paid orders cannot be cancelled'], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
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
     * Record payment for an order
     */
    public function recordPayment(Request $request, BarOrder $order)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission.'], 403);
        }

        $ownerId = $this->getOwnerId();
        if ($order->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:cash,mobile_money,bank,card',
            'mobile_money_number' => 'nullable|string|max:50',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $staff = $this->getCurrentStaff();
            
            // Map the frontend 'bank' string to the database enum 'bank_transfer'
            $paymentMethod = $validated['payment_method'] === 'bank' ? 'bank_transfer' : $validated['payment_method'];
            
            $order->update([
                'payment_method' => $paymentMethod,
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payment_status' => 'paid',
                'paid_amount' => $order->total_amount,
                'paid_by_waiter_id' => $staff->id,
            ]);

            \App\Models\OrderPayment::create([
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'amount' => $order->total_amount,
                'mobile_money_number' => $validated['mobile_money_number'] ?? null,
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payment_status' => 'verified',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'order' => $order->load(['items.productVariant.product', 'table', 'orderPayments']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to record payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Print receipt for an order from counter
     */
    public function printReceipt(BarOrder $order)
    {
        $ownerId = $this->getOwnerId();
        if ($order->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        $order->load(['items.productVariant.product', 'table', 'waiter', 'user']);

        return view('bar.waiter.receipt', compact('order'));
    }

    /**
     * Display a printable Daily Stock Sheet
     */
    public function dailyStockSheet()
    {
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view counter stock.');
        }

        $ownerId = $this->getOwnerId();
        
        $staff = $this->getCurrentStaff();
        $staffName = $staff ? $staff->full_name : (auth()->user()->name ?? auth()->user()->first_name ?? 'Counter Staff');

        // Get today's sold items
        $today = \Carbon\Carbon::today();
        $soldItems = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $today) {
                $q->where('user_id', $ownerId)->whereDate('created_at', $today)->where('status', 'served');
            })
            ->get()
            ->groupBy('product_variant_id');

        $stockQuery = \App\Models\StockLocation::where('user_id', $ownerId)
            ->where('location', 'counter')
            ->where('quantity', '>', 0)
            ->with(['productVariant.product']);

        $openBottles = \App\Models\OpenBottle::where('user_id', $ownerId)->get()->keyBy('product_variant_id');

        $stock = $stockQuery->get()->map(function($item) use ($openBottles, $soldItems) {
            $variant = $item->productVariant;
            if (!$variant) return null;
            
            // Today's Sales Calculation
            $variantSales = $soldItems->get($variant->id, collect());
            $bottlesSold = $variantSales->where('sell_type', 'unit')->sum('quantity');
            $totsSold = $variantSales->where('sell_type', 'tot')->sum('quantity');
            
            $soldRevenue = $variantSales->sum('total_price');
            
            $totalSoldQuantity = $bottlesSold;
            if ($variant->can_sell_in_tots && $variant->total_tots > 0) {
                $totalSoldQuantity += ($totsSold / $variant->total_tots);
            }
            
            $soldFormatted = $totalSoldQuantity > 0 ? $variant->formatUnits($totalSoldQuantity) : '-';

            $quantity = (float)$item->quantity;
            $openBottle = $openBottles->get($item->product_variant_id);
            
            $fraction = 0;
            if ($variant->can_sell_in_tots && $variant->total_tots > 0 && $openBottle) {
                $fraction = ($openBottle->tots_remaining / $variant->total_tots);
                $quantity += $fraction;
            }

            // Calculations
            $totalValue = 0;
            if ($variant->can_sell_in_tots && $variant->total_tots > 0) {
                // If it sells in glasses, value is calculated by glass logic across all available
                $totalTots = ($item->quantity * $variant->total_tots) + ($openBottle ? $openBottle->tots_remaining : 0);
                $totalValue = $totalTots * $variant->tot_price;
            } else {
                // Plain bottle logic
                $totalValue = $item->quantity * $variant->bottle_price;
            }
            
            return (object)[
                'id' => $variant->id,
                'name' => $variant->display_name ?? $variant->product?->name ?? 'Unknown',
                'category' => $variant->product?->category ?? 'General',
                'measurement' => $variant->measurement,
                'packaging' => $variant->packaging,
                'sold_formatted' => $soldFormatted,
                'sold_revenue' => $soldRevenue,
                'price_label' => $variant->can_sell_in_tots 
                                 ? 'TSh ' . number_format($variant->tot_price) . '/' . ($variant->portion_unit_name ?? 'Gl') 
                                 : 'TSh ' . number_format($variant->bottle_price) . '/Btl',
                'quantity_formatted' => $variant->formatUnits($quantity),
                'total_value' => $totalValue
            ];
        })->filter()->sortBy('name');

        $totalInventoryValue = $stock->sum('total_value');
        $totalSalesRevenue = $stock->sum('sold_revenue');

        return view('bar.counter.daily-stock-sheet', compact('stock', 'totalInventoryValue', 'totalSalesRevenue', 'staffName'));
    }
}

