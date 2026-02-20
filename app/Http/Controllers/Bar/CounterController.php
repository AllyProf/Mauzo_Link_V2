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
use App\Services\TransferSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->paginate(20);

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
                // Only deduct if stock hasn't been deducted yet (check for existing stock movements)
                $order->load('items.productVariant');
                $hasStockMovements = StockMovement::where('reference_type', BarOrder::class)
                    ->where('reference_id', $order->id)
                    ->where('movement_type', 'sale')
                    ->exists();

                if (!$hasStockMovements) {
                    $transferSaleService = new TransferSaleService();
                    $currentUser = $this->getCurrentUser();

                    foreach ($order->items as $orderItem) {
                        // Skip if this is a food item (handled by chef)
                        if (!$orderItem->productVariant || !$orderItem->productVariant->product) {
                            continue;
                        }

                        // Get counter stock for this variant
                        $counterStock = StockLocation::where('user_id', $ownerId)
                            ->where('product_variant_id', $orderItem->product_variant_id)
                            ->where('location', 'counter')
                            ->first();

                        if (!$counterStock) {
                            \Log::warning("Counter stock not found for variant {$orderItem->product_variant_id} when serving order {$order->id}");
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
                    }
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
            \Log::error('Failed to update order status: ' . $e->getMessage());
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

        // Get order statistics
        $todayOrders = BarOrder::where('user_id', $ownerId)
            ->whereDate('created_at', today())
            ->count();

        $pendingOrders = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->where('status', 'pending')
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

        // Get pending stock transfer requests (transfers requested by counter/owner)
        // Since transfers are always warehouse to counter, we count all pending transfers
        $pendingTransfers = StockTransfer::where('user_id', $ownerId)
            ->where('status', 'pending')
            ->count();

        // Get warehouse stock statistics
        $warehouseStockItems = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->whereHas('stockLocations', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)->where('location', 'warehouse')->where('quantity', '>', 0);
        })
        ->count();

        // Get low stock threshold from settings
        $lowStockThreshold = \App\Models\SystemSetting::get('low_stock_threshold_' . $ownerId, 10);
        $criticalStockThreshold = \App\Models\SystemSetting::get('critical_stock_threshold_' . $ownerId, 5);
        
        // Get low stock items (both warehouse and counter)
        $lowStockItemsList = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        }])
        ->get()
        ->filter(function($variant) use ($lowStockThreshold) {
            $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $warehouseQty = $warehouseStock ? $warehouseStock->quantity : 0;
            $counterQty = $counterStock ? $counterStock->quantity : 0;
            $totalQty = $warehouseQty + $counterQty;
            return $totalQty > 0 && $totalQty < $lowStockThreshold;
        })
        ->take(10)
        ->map(function($variant) use ($ownerId, $criticalStockThreshold) {
            $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            $warehouseQty = $warehouseStock ? $warehouseStock->quantity : 0;
            $counterQty = $counterStock ? $counterStock->quantity : 0;
            $totalQty = $warehouseQty + $counterQty;
            
            return [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant' => $variant->measurement,
                'warehouse_qty' => $warehouseQty,
                'counter_qty' => $counterQty,
                'total_qty' => $totalQty,
                'is_critical' => $totalQty < $criticalStockThreshold,
            ];
        });

        // Recent stock transfer requests
        $recentTransferRequests = StockTransfer::where('user_id', $ownerId)
            ->with(['productVariant.product', 'requestedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent orders
        $recentOrders = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('waiter_id')
            ->with(['waiter', 'items.productVariant.product'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
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
            'recentOrders'
        ));
    }

    /**
     * View Warehouse Stock (available products from stock keeper)
     */
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
            $query->where('user_id', $ownerId);
        }])
        ->get()
        ->filter(function($variant) {
            $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
            return $warehouseStock && $warehouseStock->quantity > 0;
        })
        ->map(function($variant) {
            $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            return [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant' => $variant->measurement . ' - ' . $variant->packaging,
                'warehouse_quantity' => $warehouseStock->quantity,
                'counter_quantity' => $counterStock ? $counterStock->quantity : 0,
                'buying_price' => $warehouseStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0,
                'selling_price' => $counterStock ? ($counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0) : ($variant->selling_price_per_unit ?? 0),
            ];
        });

        return view('bar.counter.warehouse-stock', compact('variants'));
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
            ->map(function($variant) {
                $counterStock    = $variant->stockLocations->where('location', 'counter')->first();
                $itemsPerPackage = $variant->items_per_package ?? 1;
                $packaging       = $variant->packaging ?? 'Package';
                $quantity        = $counterStock ? $counterStock->quantity : 0;
                $packages        = $itemsPerPackage > 1 ? floor($quantity / $itemsPerPackage) : 0;
                $remainingBottles= $itemsPerPackage > 1 ? ($quantity % $itemsPerPackage) : $quantity;

                return [
                    'id'                   => $variant->id,
                    'product_name'         => $variant->product->name,
                    'variant_name'         => $variant->name,          // e.g. "Fanta Pineapple"
                    'product_image'        => $variant->product->image,
                    'category'             => $variant->product->category ?? 'General',
                    'variant'              => $variant->measurement,    // e.g. "350"
                    'quantity'             => $quantity,
                    'items_per_package'    => $itemsPerPackage,
                    'packaging'            => $packaging,
                    'packages'             => $packages,
                    'remaining_bottles'    => $remainingBottles,
                    'selling_price'        => $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                    'selling_price_per_tot'=> $counterStock->selling_price_per_tot ?? $variant->selling_price_per_tot ?? 0,
                    'can_sell_in_tots'     => $variant->can_sell_in_tots && ($variant->total_tots > 0),
                    'buying_price'         => $counterStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0,
                    'is_low_stock'         => $quantity < 10,
                ];
            });

        $totalValue = $variants->sum(fn($v) => $v['quantity'] * $v['selling_price']);

        // Get unique categories for tabs
        $categories = $variants->pluck('category')->unique()->sort()->values();

        return view('bar.counter.counter-stock', compact('variants', 'totalValue', 'categories'));
    }

    /**
     * Request Stock Transfer from Warehouse
     */
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
}
