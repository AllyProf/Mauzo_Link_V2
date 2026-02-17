<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\Staff;
use App\Models\WaiterDailyReconciliation;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AccountantController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Accountant Dashboard - Financial Overview
     */
    public function dashboard(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to access accountant dashboard.');
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', now()->format('Y-m-d'));
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Accountant can see all orders (cross-owner access for financial overview)
        // Today's Financial Summary
        $todayOrders = BarOrder::query()
            ->whereDate('created_at', $date)
            ->with(['items', 'kitchenOrderItems', 'orderPayments'])
            ->get();

        // Calculate revenue from items (bar + food), not total_amount
        $todayRevenue = $todayOrders->where('payment_status', 'paid')->sum(function($order) {
            $barAmount = $order->items && $order->items->isNotEmpty() 
                ? $order->items->sum('total_price') 
                : 0;
            $foodAmount = $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                ? $order->kitchenOrderItems->sum('total_price')
                : 0;
            return $barAmount + $foodAmount;
        });
        // Calculate cash and mobile money from OrderPayments only (source of truth)
        $todayCash = $todayOrders->sum(function($order) {
            return $order->orderPayments && $order->orderPayments->isNotEmpty()
                ? $order->orderPayments->where('payment_method', 'cash')->sum('amount')
                : 0;
        });
        $todayMobileMoney = $todayOrders->sum(function($order) {
            return $order->orderPayments && $order->orderPayments->isNotEmpty()
                ? $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount')
                : 0;
        });
        $todayOrdersCount = $todayOrders->count();
        $todayPaidOrders = $todayOrders->where('payment_status', 'paid')->count();
        $todayPendingAmount = $todayOrders->where('payment_status', '!=', 'paid')->sum('total_amount');

        // Period Financial Summary (default: current month)
        $periodOrders = BarOrder::query()
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->with(['items', 'kitchenOrderItems', 'orderPayments'])
            ->get();

        // Calculate revenue from items (bar + food), not total_amount
        $periodRevenue = $periodOrders->where('payment_status', 'paid')->sum(function($order) {
            $barAmount = $order->items && $order->items->isNotEmpty() 
                ? $order->items->sum('total_price') 
                : 0;
            $foodAmount = $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                ? $order->kitchenOrderItems->sum('total_price')
                : 0;
            return $barAmount + $foodAmount;
        });
        // Calculate cash and mobile money from OrderPayments only (source of truth)
        $periodCash = $periodOrders->sum(function($order) {
            return $order->orderPayments && $order->orderPayments->isNotEmpty()
                ? $order->orderPayments->where('payment_method', 'cash')->sum('amount')
                : 0;
        });
        $periodMobileMoney = $periodOrders->sum(function($order) {
            return $order->orderPayments && $order->orderPayments->isNotEmpty()
                ? $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount')
                : 0;
        });
        $periodOrdersCount = $periodOrders->count();
        $periodPaidOrders = $periodOrders->where('payment_status', 'paid')->count();
        $periodPendingAmount = $periodOrders->where('payment_status', '!=', 'paid')->sum('total_amount');

        // Separate bar and food sales
        $todayBarSales = $todayOrders->filter(function($order) {
            return $order->items && $order->items->isNotEmpty();
        })->sum(function($order) {
            return $order->items->sum('total_price');
        });

        $todayFoodSales = $todayOrders->sum(function($order) {
            return $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty() 
                ? $order->kitchenOrderItems->sum('total_price') 
                : 0;
        });

        $periodBarSales = $periodOrders->filter(function($order) {
            return $order->items && $order->items->isNotEmpty();
        })->sum(function($order) {
            return $order->items->sum('total_price');
        });

        $periodFoodSales = $periodOrders->sum(function($order) {
            return $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                ? $order->kitchenOrderItems->sum('total_price')
                : 0;
        });

        // Waiter Reconciliations Summary (all reconciliations)
        $reconciliations = WaiterDailyReconciliation::query()
            ->whereBetween('reconciliation_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->with('waiter')
            ->get();

        $totalExpected = $reconciliations->sum('expected_amount');
        $totalSubmitted = $reconciliations->sum('submitted_amount');
        $totalDifference = $reconciliations->sum('difference');
        $verifiedReconciliations = $reconciliations->where('status', 'verified')->count();
        $pendingReconciliations = $reconciliations->where('status', 'pending')->count();
        $submittedReconciliations = $reconciliations->where('status', 'submitted')->count();

        // Revenue by Day (Last 30 days)
        $revenueByDay = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $dayOrders = BarOrder::query()
                ->whereDate('created_at', $day->format('Y-m-d'))
                ->where('payment_status', 'paid')
                ->with(['items', 'kitchenOrderItems', 'orderPayments'])
                ->get();
            
            // Calculate revenue from items
            $dayRevenue = $dayOrders->sum(function($order) {
                $barAmount = $order->items && $order->items->isNotEmpty() 
                    ? $order->items->sum('total_price') 
                    : 0;
                $foodAmount = $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                    ? $order->kitchenOrderItems->sum('total_price')
                    : 0;
                return $barAmount + $foodAmount;
            });
            
            $revenueByDay[] = [
                'date' => $day->format('M d'),
                'revenue' => $dayRevenue,
                'cash' => $dayOrders->sum(function($order) {
                    return $order->orderPayments && $order->orderPayments->isNotEmpty()
                        ? $order->orderPayments->where('payment_method', 'cash')->sum('amount')
                        : 0;
                }),
                'mobile_money' => $dayOrders->sum(function($order) {
                    return $order->orderPayments && $order->orderPayments->isNotEmpty()
                        ? $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount')
                        : 0;
                }),
            ];
        }

        // Top Waiters by Revenue (all waiters across all owners)
        $topWaiters = Staff::query()
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
            })
            ->with(['dailyReconciliations' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('reconciliation_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
            }])
            ->get()
            ->map(function($waiter) use ($startDate, $endDate) {
                $orders = BarOrder::query()
                    ->where('waiter_id', $waiter->id)
                    ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                    ->where('payment_status', 'paid')
                    ->with(['items', 'kitchenOrderItems', 'orderPayments'])
                    ->get();
                
                return [
                    'waiter' => $waiter,
                    'total_revenue' => $orders->sum('total_amount'),
                    'orders_count' => $orders->count(),
                    'cash_collected' => $orders->where('payment_method', 'cash')->sum('paid_amount') + 
                                      $orders->sum(function($order) {
                                          return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                                      }),
                    'mobile_money_collected' => $orders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                                              $orders->sum(function($order) {
                                                  return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                                              }),
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values();

        // Pending Stock Transfer Verifications (completed transfers waiting for accountant verification)
        $pendingTransferVerifications = \App\Models\StockTransfer::query()
            ->where('status', 'completed')
            ->whereNull('verified_at') // Not yet verified
            ->with(['productVariant.product', 'productVariant.counterStock', 'productVariant.warehouseStock'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transfer) use ($ownerId) {
                // Calculate expected and real-time profit/revenue
                if ($transfer->productVariant) {
                    $counterStock = \App\Models\StockLocation::where('user_id', $ownerId)
                        ->where('product_variant_id', $transfer->product_variant_id)
                        ->where('location', 'counter')
                        ->first();
                    
                    $warehouseStock = \App\Models\StockLocation::where('user_id', $ownerId)
                        ->where('product_variant_id', $transfer->product_variant_id)
                        ->where('location', 'warehouse')
                        ->first();
                    
                    $sellingPrice = $counterStock->selling_price ?? $warehouseStock->selling_price ?? $transfer->productVariant->selling_price_per_unit ?? 0;
                    $buyingPrice = $warehouseStock->average_buying_price ?? $transfer->productVariant->buying_price_per_unit ?? 0;
                    
                    $transfer->expected_revenue = $transfer->total_units * $sellingPrice;
                    $transfer->expected_profit = ($sellingPrice - $buyingPrice) * $transfer->total_units;
                    
                    // Calculate real-time profit and revenue
                    $transfer->real_time_profit = $this->calculateRealTimeProfitForTransfer($transfer, $ownerId, $sellingPrice, $buyingPrice);
                    $revenueData = $this->calculateRealTimeRevenueForTransfer($transfer, $ownerId);
                    $transfer->real_time_revenue = $revenueData['total'];
                }
                return $transfer;
            });

        // Recent Reconciliations (all reconciliations)
        $recentReconciliations = WaiterDailyReconciliation::query()
            ->with('waiter', 'verifiedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Outstanding Payments (all orders)
        $outstandingOrders = BarOrder::query()
            ->where('status', 'served')
            ->where('payment_status', '!=', 'paid')
            ->with(['waiter', 'table', 'items', 'kitchenOrderItems'])
            ->orderBy('created_at', 'desc')
            ->get();

        $outstandingAmount = $outstandingOrders->sum('total_amount');

        return view('accountant.dashboard', compact(
            'date',
            'startDate',
            'endDate',
            'todayRevenue',
            'todayCash',
            'todayMobileMoney',
            'todayOrdersCount',
            'todayPaidOrders',
            'todayPendingAmount',
            'todayBarSales',
            'todayFoodSales',
            'periodRevenue',
            'periodCash',
            'periodMobileMoney',
            'periodOrdersCount',
            'periodPaidOrders',
            'periodPendingAmount',
            'periodBarSales',
            'periodFoodSales',
            'totalExpected',
            'totalSubmitted',
            'totalDifference',
            'verifiedReconciliations',
            'pendingReconciliations',
            'submittedReconciliations',
            'revenueByDay',
            'topWaiters',
            'pendingTransferVerifications',
            'recentReconciliations',
            'outstandingOrders',
            'outstandingAmount'
        ));
    }

    /**
     * View All Stock Transfer Reconciliations (Accountant reconciles stock transfers)
     */
    public function reconciliations(Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to view reconciliations.');
        }

        $ownerId = $this->getOwnerId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status'); // verified, unverified

        $query = \App\Models\StockTransfer::query()
            ->where('status', 'completed')
            ->whereBetween('updated_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->with(['productVariant.product', 'productVariant.counterStock', 'productVariant.warehouseStock', 'verifiedBy', 'requestedBy', 'approvedBy']);

        if ($status === 'verified') {
            $query->whereNotNull('verified_at');
        } elseif ($status === 'unverified') {
            $query->whereNull('verified_at');
        }

        $transfers = $query->orderBy('updated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate expected and real-time profit/revenue for each transfer
        $transfers->getCollection()->transform(function($transfer) use ($ownerId) {
            if ($transfer->productVariant) {
                $counterStock = \App\Models\StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $transfer->product_variant_id)
                    ->where('location', 'counter')
                    ->first();
                
                $warehouseStock = \App\Models\StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $transfer->product_variant_id)
                    ->where('location', 'warehouse')
                    ->first();
                
                $sellingPrice = $counterStock->selling_price ?? $warehouseStock->selling_price ?? $transfer->productVariant->selling_price_per_unit ?? 0;
                $buyingPrice = $warehouseStock->average_buying_price ?? $transfer->productVariant->buying_price_per_unit ?? 0;
                
                $transfer->expected_revenue = $transfer->total_units * $sellingPrice;
                $transfer->expected_profit = ($sellingPrice - $buyingPrice) * $transfer->total_units;
                
                // Calculate real-time profit and revenue
                $transfer->real_time_profit = $this->calculateRealTimeProfitForTransfer($transfer, $ownerId, $sellingPrice, $buyingPrice);
                $revenueData = $this->calculateRealTimeRevenueForTransfer($transfer, $ownerId);
                $transfer->real_time_revenue = $revenueData['total'];
                $transfer->real_time_revenue_submitted = $revenueData['submitted'];
                $transfer->real_time_revenue_pending = $revenueData['pending'];
            }
            return $transfer;
        });

        return view('accountant.reconciliations', compact(
            'transfers',
            'startDate',
            'endDate',
            'status'
        ));
    }

    /**
     * Counter Reconciliation (Accountant View)
     */
    public function counterReconciliation(Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to view counter reconciliation.');
        }

        // Use the same controller method but with accountant permissions
        $counterController = new \App\Http\Controllers\Bar\CounterReconciliationController();
        return $counterController->reconciliation($request);
    }

    /**
     * View Reconciliation Details
     */
    public function reconciliationDetails($id, Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to view reconciliation details.');
        }

        $ownerId = $this->getOwnerId();

        // Accountant can view any reconciliation
        $reconciliation = WaiterDailyReconciliation::query()
            ->with(['waiter', 'verifiedBy', 'orders.items.productVariant.product', 'orders.kitchenOrderItems'])
            ->findOrFail($id);

        // Only get orders that are specifically linked to this reconciliation
        $orders = $reconciliation->orders;

        // If AJAX request, return JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'reconciliation' => [
                    'id' => $reconciliation->id,
                    'date' => $reconciliation->reconciliation_date->format('F d, Y'),
                    'waiter' => [
                        'name' => $reconciliation->waiter->full_name,
                        'email' => $reconciliation->waiter->email,
                    ],
                    'status' => $reconciliation->status,
                    'expected_amount' => $reconciliation->expected_amount,
                    'submitted_amount' => $reconciliation->submitted_amount,
                    'difference' => $reconciliation->difference,
                    'cash_collected' => $reconciliation->cash_collected,
                    'mobile_money_collected' => $reconciliation->mobile_money_collected,
                    'notes' => $reconciliation->notes,
                    'verified_by' => $reconciliation->verifiedBy ? [
                        'name' => $reconciliation->verifiedBy->full_name,
                        'date' => $reconciliation->verified_at->format('M d, Y H:i'),
                    ] : null,
                    'orders' => $orders->map(function($order) {
                        return [
                            'order_number' => $order->order_number,
                            'date' => $order->created_at->format('M d, Y H:i'),
                            'bar_items' => $order->items ? $order->items->map(function($item) {
                                return [
                                    'quantity' => $item->quantity,
                                    'product_name' => ($item->productVariant && $item->productVariant->product) 
                                        ? $item->productVariant->product->name 
                                        : 'N/A',
                                ];
                            })->toArray() : [],
                            'food_items' => $order->kitchenOrderItems ? $order->kitchenOrderItems->map(function($item) {
                                return [
                                    'quantity' => $item->quantity,
                                    'name' => $item->food_item_name ?? 'N/A',
                                ];
                            })->toArray() : [],
                            'bar_amount' => $order->items ? $order->items->sum('total_price') : 0,
                            'food_amount' => $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0,
                            'total_amount' => $order->total_amount,
                            'payment_method' => $order->payment_method,
                            'payment_status' => $order->payment_status,
                        ];
                    })->toArray(),
                ]
            ]);
        }

        return view('accountant.reconciliation-details', compact('reconciliation'));
    }

    /**
     * Verify a stock transfer (Final approval by accountant based on expected profit/revenue)
     */
    public function verifyStockTransfer(Request $request, \App\Models\StockTransfer $stockTransfer)
    {
        if (!$this->hasPermission('finance', 'edit') && !$this->hasPermission('reports', 'edit')) {
            return response()->json(['error' => 'You do not have permission to verify stock transfers.'], 403);
        }

        // Only completed transfers can be verified
        if ($stockTransfer->status !== 'completed') {
            return response()->json(['error' => 'Only completed stock transfers can be verified.'], 400);
        }

        // Check if already verified
        if ($stockTransfer->verified_at) {
            return response()->json(['error' => 'This stock transfer has already been verified.'], 400);
        }

        $stockTransfer->update([
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock transfer verified successfully.',
            'transfer' => $stockTransfer->load('verifiedBy', 'productVariant.product')
        ]);
    }

    /**
     * Calculate real-time profit for a completed stock transfer.
     */
    private function calculateRealTimeProfitForTransfer($transfer, $ownerId, $sellingPrice, $buyingPrice)
    {
        if ($transfer->status !== 'completed' || !$transfer->productVariant) {
            return 0;
        }

        // Get the date when transfer was completed
        $completedDate = $transfer->updated_at;
        
        // Find all order items from this product variant created after transfer completion
        $orderItems = \App\Models\OrderItem::where('product_variant_id', $transfer->product_variant_id)
            ->whereHas('order', function($query) use ($ownerId, $completedDate) {
                $query->where('user_id', $ownerId)
                      ->where('created_at', '>=', $completedDate);
            })
            ->with(['order.orderPayments'])
            ->get();

        $totalProfit = 0;
        foreach ($orderItems as $item) {
            $order = $item->order;
            
            // Check if order has recorded payments (OrderPayments)
            if ($order && $order->orderPayments && $order->orderPayments->count() > 0) {
                // Get total recorded payments for this order
                $recordedPayments = $order->orderPayments->sum('amount');
                $orderTotal = $order->items->sum('total_price');
                
                if ($orderTotal > 0) {
                    // Calculate the proportion of recorded payments
                    $paymentRatio = min(1, $recordedPayments / $orderTotal); // Cap at 1 (100%)
                    
                    // Calculate profit: (selling price - buying price) * quantity * payment ratio
                    $itemProfit = ($item->unit_price - $buyingPrice) * $item->quantity * $paymentRatio;
                    $totalProfit += $itemProfit;
                }
            }
        }

        return $totalProfit;
    }

    /**
     * Calculate real-time revenue for a completed stock transfer.
     * Returns array with 'recorded', 'submitted', 'pending', and 'total' amounts.
     */
    private function calculateRealTimeRevenueForTransfer($transfer, $ownerId)
    {
        if ($transfer->status !== 'completed' || !$transfer->productVariant) {
            return [
                'recorded' => 0,
                'submitted' => 0,
                'pending' => 0,
                'total' => 0
            ];
        }

        $completedDate = $transfer->updated_at;
        
        // Get all order items matching this transfer's product variant
        $orderItems = \App\Models\OrderItem::where('product_variant_id', $transfer->product_variant_id)
            ->whereHas('order', function($query) use ($ownerId, $completedDate) {
                $query->where('user_id', $ownerId)
                      ->where('created_at', '>=', $completedDate);
            })
            ->with(['order.orderPayments', 'order.reconciliation'])
            ->get();

        // Calculate recorded amount: Sum of all OrderPayment amounts (both pending and verified)
        $recordedAmount = 0;
        $orderIds = $orderItems->pluck('order_id')->unique();
        
        foreach ($orderIds as $orderId) {
            $order = \App\Models\BarOrder::with('orderPayments')->find($orderId);
            if ($order) {
                // Sum all OrderPayments for this order (both pending and verified)
                $recordedAmount += $order->orderPayments->sum('amount');
            }
        }

        // Calculate submitted amount: From WaiterDailyReconciliation records
        $submittedAmount = 0;
        
        // Group order items by order_id and waiter_id to handle reconciliations
        $ordersByWaiterAndDate = $orderItems->groupBy(function($item) {
            $order = $item->order;
            if ($order && $order->waiter_id && $order->created_at) {
                return $order->waiter_id . '_' . $order->created_at->format('Y-m-d');
            }
            return 'no_waiter';
        });

        foreach ($ordersByWaiterAndDate as $key => $items) {
            if ($key === 'no_waiter') continue;
            
            list($waiterId, $date) = explode('_', $key, 2);
            
            // Get reconciliation for this waiter on this date
            $reconciliation = \App\Models\WaiterDailyReconciliation::where('waiter_id', $waiterId)
                ->where('reconciliation_date', $date)
                ->where('user_id', $ownerId)
                ->first();
            
            if ($reconciliation) {
                // Get total order value for items from this transfer's product variant
                $totalOrderValue = $items->sum('total_price');
                
                // Get all orders for this waiter on this date
                $allWaiterOrders = \App\Models\BarOrder::where('waiter_id', $waiterId)
                    ->where('user_id', $ownerId)
                    ->whereDate('created_at', $date)
                    ->with('items')
                    ->get();
                
                $allWaiterOrderTotal = $allWaiterOrders->sum(function($order) {
                    return $order->items->sum('total_price');
                });
                
                if ($allWaiterOrderTotal > 0) {
                    // Proportionally allocate submitted amount
                    $ratio = $totalOrderValue / $allWaiterOrderTotal;
                    $submittedAmount += $reconciliation->submitted_amount * $ratio;
                }
            }
        }

        $pendingAmount = $recordedAmount - $submittedAmount;

        return [
            'recorded' => $recordedAmount,
            'submitted' => $submittedAmount,
            'pending' => $pendingAmount,
            'total' => $recordedAmount
        ];
    }

    /**
     * Financial Reports
     */
    public function reports(Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to view financial reports.');
        }

        $ownerId = $this->getOwnerId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Revenue by Day (all orders)
        $revenueByDay = [];
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($currentDate->lte($end)) {
            $dayOrders = BarOrder::query()
                ->whereDate('created_at', $currentDate->format('Y-m-d'))
                ->where('payment_status', 'paid')
                ->with(['items', 'kitchenOrderItems', 'orderPayments'])
                ->get();
            
            // Calculate revenue from items (bar + food), not total_amount
            $dayRevenue = $dayOrders->sum(function($order) {
                $barAmount = $order->items && $order->items->isNotEmpty() 
                    ? $order->items->sum('total_price') 
                    : 0;
                $foodAmount = $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                    ? $order->kitchenOrderItems->sum('total_price')
                    : 0;
                return $barAmount + $foodAmount;
            });
            
            $revenueByDay[] = [
                'date' => $currentDate->format('Y-m-d'),
                'date_formatted' => $currentDate->format('M d, Y'),
                'revenue' => $dayRevenue,
                'orders_count' => $dayOrders->count(),
                'cash' => $dayOrders->sum(function($order) {
                    return $order->orderPayments && $order->orderPayments->isNotEmpty()
                        ? $order->orderPayments->where('payment_method', 'cash')->sum('amount')
                        : 0;
                }),
                'mobile_money' => $dayOrders->sum(function($order) {
                    return $order->orderPayments && $order->orderPayments->isNotEmpty()
                        ? $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount')
                        : 0;
                }),
            ];
            
            $currentDate->addDay();
        }

        // Revenue by Waiter (all waiters)
        $revenueByWaiter = Staff::query()
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
            })
            ->get()
            ->map(function($waiter) use ($startDate, $endDate) {
                $orders = BarOrder::query()
                    ->where('waiter_id', $waiter->id)
                    ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                    ->where('payment_status', 'paid')
                    ->with(['items', 'kitchenOrderItems'])
                    ->get();
                
                // Calculate revenue from items (bar + food), not total_amount
                $barSales = $orders->filter(function($order) {
                    return $order->items && $order->items->isNotEmpty();
                })->sum(function($order) {
                    return $order->items->sum('total_price');
                });
                
                $foodSales = $orders->sum(function($order) {
                    return $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                        ? $order->kitchenOrderItems->sum('total_price')
                        : 0;
                });
                
                return [
                    'waiter' => $waiter,
                    'total_revenue' => $barSales + $foodSales,
                    'orders_count' => $orders->count(),
                    'bar_sales' => $barSales,
                    'food_sales' => $foodSales,
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();

        return view('accountant.reports', compact(
            'revenueByDay',
            'revenueByWaiter',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export Financial Reports as PDF
     */
    public function exportReportsPdf(Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to export financial reports.');
        }

        $ownerId = $this->getOwnerId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Revenue by Day (all orders)
        $revenueByDay = [];
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($currentDate->lte($end)) {
            $dayOrders = BarOrder::query()
                ->whereDate('created_at', $currentDate->format('Y-m-d'))
                ->where('payment_status', 'paid')
                ->with(['items', 'kitchenOrderItems', 'orderPayments'])
                ->get();
            
            // Calculate revenue from items (bar + food), not total_amount
            $dayRevenue = $dayOrders->sum(function($order) {
                $barAmount = $order->items && $order->items->isNotEmpty() 
                    ? $order->items->sum('total_price') 
                    : 0;
                $foodAmount = $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                    ? $order->kitchenOrderItems->sum('total_price')
                    : 0;
                return $barAmount + $foodAmount;
            });
            
            $revenueByDay[] = [
                'date' => $currentDate->format('Y-m-d'),
                'date_formatted' => $currentDate->format('M d, Y'),
                'revenue' => $dayRevenue,
                'orders_count' => $dayOrders->count(),
                'cash' => $dayOrders->sum(function($order) {
                    return $order->orderPayments && $order->orderPayments->isNotEmpty()
                        ? $order->orderPayments->where('payment_method', 'cash')->sum('amount')
                        : 0;
                }),
                'mobile_money' => $dayOrders->sum(function($order) {
                    return $order->orderPayments && $order->orderPayments->isNotEmpty()
                        ? $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount')
                        : 0;
                }),
            ];
            
            $currentDate->addDay();
        }

        // Revenue by Waiter (all waiters)
        $revenueByWaiter = Staff::query()
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
            })
            ->get()
            ->map(function($waiter) use ($startDate, $endDate) {
                $orders = BarOrder::query()
                    ->where('waiter_id', $waiter->id)
                    ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                    ->where('payment_status', 'paid')
                    ->with(['items', 'kitchenOrderItems'])
                    ->get();
                
                // Calculate revenue from items (bar + food), not total_amount
                $barSales = $orders->filter(function($order) {
                    return $order->items && $order->items->isNotEmpty();
                })->sum(function($order) {
                    return $order->items->sum('total_price');
                });
                
                $foodSales = $orders->sum(function($order) {
                    return $order->kitchenOrderItems && $order->kitchenOrderItems->isNotEmpty()
                        ? $order->kitchenOrderItems->sum('total_price')
                        : 0;
                });
                
                return [
                    'waiter' => $waiter,
                    'total_revenue' => $barSales + $foodSales,
                    'orders_count' => $orders->count(),
                    'bar_sales' => $barSales,
                    'food_sales' => $foodSales,
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();

        // Calculate totals
        $totalRevenue = collect($revenueByDay)->sum('revenue');
        $totalCash = collect($revenueByDay)->sum('cash');
        $totalMobileMoney = collect($revenueByDay)->sum('mobile_money');
        $totalOrders = collect($revenueByDay)->sum('orders_count');

        // Generate PDF
        $pdf = Pdf::loadView('accountant.reports-pdf', compact(
            'revenueByDay',
            'revenueByWaiter',
            'startDate',
            'endDate',
            'totalRevenue',
            'totalCash',
            'totalMobileMoney',
            'totalOrders'
        ));

        $filename = 'Financial_Report_' . $startDate . '_to_' . $endDate . '.pdf';
        
        return $pdf->download($filename);
    }
}
