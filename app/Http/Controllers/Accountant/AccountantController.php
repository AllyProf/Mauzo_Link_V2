<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\Staff;
use App\Models\WaiterDailyReconciliation;
use App\Models\OrderPayment;
use App\Models\PettyCashIssue;
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

        $location = session('active_location');

        // Helper to apply common filters (owner + location)
        $applyFilters = function($query) use ($ownerId, $location) {
            $query->where('user_id', $ownerId);
            if ($location) {
                $query->whereHas('table', function($q) use ($location) {
                    $q->where('location', $location);
                });
            }
            return $query;
        };

        // Accountant must be restricted to their owner's data
        // Today's Financial Summary
        $todayOrders = $applyFilters(BarOrder::query())
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
        $periodOrders = $applyFilters(BarOrder::query())
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

        // Waiter Reconciliations Summary (filtered by branch)
        $reconciliations = WaiterDailyReconciliation::query()
            ->where('user_id', $ownerId)
            ->whereBetween('reconciliation_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->when($location, function($q) use ($location) {
                $q->whereHas('waiter', function($sq) use ($location) {
                    $sq->where('location_branch', $location);
                });
            })
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
            $dayOrders = $applyFilters(BarOrder::query())
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

        // Top Waiters by Revenue (filtered by owner and branch)
        $topWaiters = Staff::query()
            ->where('user_id', $ownerId)
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
            })
            ->when($location, function($q) use ($location) {
                $q->where('location_branch', $location);
            })
            ->with(['dailyReconciliations' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('reconciliation_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
            }])
            ->get()
            ->map(function($waiter) use ($startDate, $endDate, $ownerId, $location) {
                $query = BarOrder::query()
                    ->where('user_id', $ownerId)
                    ->where('waiter_id', $waiter->id)
                    ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                    ->where('payment_status', 'paid');
                
                if ($location) {
                    $query->whereHas('table', function($q) use ($location) {
                        $q->where('location', $location);
                    });
                }

                $orders = $query->with(['items', 'kitchenOrderItems', 'orderPayments'])->get();
                
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

        // Pending Stock Transfer Verifications (filtered by owner and branch)
        $pendingTransferVerifications = \App\Models\StockTransfer::query()
            ->where('user_id', $ownerId)
            ->where('status', 'completed')
            ->whereNull('verified_at')
            ->when($location, function($q) use ($location) {
                $q->whereExists(function($sq) use ($location) {
                    $sq->select(DB::raw(1))
                       ->from('staff')
                       ->whereRaw('staff.user_id = stock_transfers.user_id')
                       ->whereRaw('staff.email = (select email from users where id = stock_transfers.requested_by limit 1)')
                       ->where('staff.location_branch', $location);
                });
            })
            ->with(['productVariant.product', 'productVariant.counterStock', 'productVariant.warehouseStock'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($transfer) use ($ownerId, $location) {
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
                    
                    // Calculate real-time profit and revenue (branch-aware)
                    $transfer->real_time_profit = $this->calculateRealTimeProfitForTransfer($transfer, $ownerId, $sellingPrice, $buyingPrice, $location);
                    $revenueData = $this->calculateRealTimeRevenueForTransfer($transfer, $ownerId, $location);
                    $transfer->real_time_revenue = $revenueData['total'];
                }
                return $transfer;
            });

        // Recent Reconciliations (filtered by branch)
        $recentReconciliations = WaiterDailyReconciliation::query()
            ->where('user_id', $ownerId)
            ->when($location, function($q) use ($location) {
                $q->whereHas('waiter', function($sq) use ($location) {
                    $sq->where('location_branch', $location);
                });
            })
            ->with('waiter', 'verifiedBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Outstanding Payments (filtered by branch)
        $outstandingOrders = $applyFilters(BarOrder::query())
            ->where('status', 'served')
            ->where('payment_status', '!=', 'paid')
            ->with(['waiter', 'table', 'items', 'kitchenOrderItems'])
            ->orderBy('created_at', 'desc')
            ->get();

        $outstandingAmount = $outstandingOrders->sum('total_amount');

        // ── Top selling products this month (Flavor-specific names)
        $topProducts = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $location) {
            $q->where('user_id', $ownerId)
              ->where('payment_status', 'paid')
              ->whereMonth('created_at', now()->month);
            if ($location) {
                $q->where(function($sq) use ($location) {
                    $sq->whereExists(function ($ssq) use ($location) {
                        $ssq->select(DB::raw(1))
                           ->from('staff')
                           ->whereColumn('staff.id', 'orders.waiter_id')
                           ->where('staff.location_branch', $location);
                    })->orWhereHas('table', function($ssq) use ($location) {
                        $ssq->where('location', $location);
                    });
                });
            }
        })
        ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
        ->selectRaw('product_variants.name as display_name, SUM(order_items.quantity) as total_sold, SUM(order_items.total_price) as total_revenue')
        ->groupBy('product_variants.name')
        ->orderByDesc('total_sold')
        ->limit(8)
        ->get();

        // ── Category Distribution (this month)
        $categoryDistribution = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $location) {
            $q->where('user_id', $ownerId)
              ->where('payment_status', 'paid')
              ->whereMonth('created_at', now()->month);
            if ($location) {
                $q->where(function($sq) use ($location) {
                    $sq->whereExists(function ($ssq) use ($location) {
                        $ssq->select(DB::raw(1))
                           ->from('staff')
                           ->whereColumn('staff.id', 'orders.waiter_id')
                           ->where('staff.location_branch', $location);
                    })->orWhereHas('table', function($ssq) use ($location) {
                        $ssq->where('location', $location);
                    });
                });
            }
        })
        ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
        ->join('products', 'product_variants.product_id', '=', 'products.id')
        ->selectRaw('products.category, SUM(order_items.quantity) as total_sold')
        ->groupBy('products.category')
        ->orderByDesc('total_sold')
        ->get();

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
            'outstandingAmount',
            'topProducts',
            'categoryDistribution'
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
        $location = session('active_location');
        $tab = $request->get('tab', 'financial'); // 'financial' or 'waiters' or 'payments'
        $canReconcile = $this->hasPermission('finance', 'edit');

        // ── Financial Reconciliations Aggregate (By Date & Type)
        // 1. Get already submitted/verified reconciliations
        $submittedReconciliations = WaiterDailyReconciliation::query()
            ->where('user_id', $ownerId)
            ->when($location && $location !== 'all', function($q) use ($location) {
                $q->whereHas('waiter', function($sq) use ($location) {
                    $sq->where('location_branch', $location);
                });
            })
            ->whereBetween('reconciliation_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('reconciliation_date, reconciliation_type, SUM(expected_amount) as total_expected, SUM(submitted_amount) as total_submitted, SUM(cash_collected) as total_cash, SUM(mobile_money_collected) as total_mobile, SUM(bank_collected) as total_bank, SUM(card_collected) as total_card, COUNT(waiter_id) as waiter_count, MIN(status) as status_indicator, MAX(notes) as notes')
            ->groupBy('reconciliation_date', 'reconciliation_type')
            ->get();

        // 2. Get Real-time Expected Sales (Pending Reconciliations)
        // We look for orders that haven't been reconciled yet for the date range
        $realTimeSales = BarOrder::query()
            ->where('user_id', $ownerId)
            ->when($location && $location !== 'all', function($q) use ($location) {
                // Determine branch from the table location
                $q->whereHas('table', function($sq) use ($location) {
                    $sq->where('location', $location);
                });
            })
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->where('status', 'served')
            ->whereDoesntHave('reconciliation', function($q) {
                $q->where('status', 'verified');
            })
            ->with(['items', 'kitchenOrderItems', 'orderPayments'])
            ->get();

        // Group the real-time sales by date and type for display
        $pendingAggr = [];
        foreach ($realTimeSales as $order) {
            $dateStr = $order->created_at->format('Y-m-d');
            
            // Bar Sales (Drinks)
            $barAmount = $order->items->sum('total_price');
            if ($barAmount > 0) {
                $key = $dateStr . '_bar';
                if (!isset($pendingAggr[$key])) {
                    $pendingAggr[$key] = [
                        'reconciliation_date' => $dateStr,
                        'reconciliation_type' => 'bar',
                        'total_expected' => 0,
                        'total_submitted' => 0, // Not submitted yet
                        'total_cash' => 0,
                        'total_mobile' => 0,
                        'total_bank' => 0,
                        'total_card' => 0,
                        'waiter_count' => 0,
                        'status_indicator' => 'pending',
                        'notes' => ''
                    ];
                }
                $pendingAggr[$key]['total_expected'] += $barAmount;
                foreach ($order->orderPayments as $payment) {
                    if ($payment->payment_method === 'cash') {
                        $pendingAggr[$key]['total_cash'] += $payment->amount;
                    } else if ($payment->payment_method === 'mobile_money') {
                        $pendingAggr[$key]['total_mobile'] += $payment->amount;
                    } else if ($payment->payment_method === 'bank_transfer') {
                        $pendingAggr[$key]['total_bank'] += $payment->amount;
                    } else if (in_array($payment->payment_method, ['pos_card', 'card'])) {
                        $pendingAggr[$key]['total_card'] += $payment->amount;
                    }
                }
            }

            // Chef Sales (Food)
            $foodAmount = $order->kitchenOrderItems->sum('total_price');
            if ($foodAmount > 0) {
                $key = $dateStr . '_food';
                if (!isset($pendingAggr[$key])) {
                    $pendingAggr[$key] = [
                        'reconciliation_date' => $dateStr,
                        'reconciliation_type' => 'food',
                        'total_expected' => 0,
                        'total_submitted' => 0,
                        'total_cash' => 0,
                        'total_mobile' => 0,
                        'total_bank' => 0,
                        'total_card' => 0,
                        'waiter_count' => 0,
                        'status_indicator' => 'pending',
                        'notes' => ''
                    ];
                }
                $pendingAggr[$key]['total_expected'] += $foodAmount;
                foreach ($order->orderPayments as $payment) {
                    if ($payment->payment_method === 'cash') {
                        $pendingAggr[$key]['total_cash'] += $payment->amount;
                    } else if ($payment->payment_method === 'mobile_money') {
                        $pendingAggr[$key]['total_mobile'] += $payment->amount;
                    } else if ($payment->payment_method === 'bank_transfer') {
                        $pendingAggr[$key]['total_bank'] += $payment->amount;
                    } else if (in_array($payment->payment_method, ['pos_card', 'card'])) {
                        $pendingAggr[$key]['total_card'] += $payment->amount;
                    }
                }
            }
        }

        // Merge submitted and pending
        // If a date/type has both, we might want to prioritize the submitted one or combine?
        // Usually, once 'Marked Paid', it vanishes from pending.
        $financialReconciliations = collect($submittedReconciliations);
        foreach ($pendingAggr as $item) {
            // Only add if not already in submitted
            $exists = $financialReconciliations->contains(function($r) use ($item) {
                return $r->reconciliation_date->format('Y-m-d') == $item['reconciliation_date'] && $r->reconciliation_type == $item['reconciliation_type'];
            });
            if (!$exists) {
                $item['reconciliation_date'] = Carbon::parse($item['reconciliation_date']);
                $financialReconciliations->push((object)$item);
            }
        }

        $financialReconciliations = $financialReconciliations->sortByDesc('reconciliation_date')->values();

        // Separate Waiter-level reconciliations for the details view or drill down
        $waiterReconciliations = WaiterDailyReconciliation::query()
            ->where('user_id', $ownerId)
            ->when($location && $location !== 'all', function($q) use ($location) {
                $q->whereHas('waiter', function($sq) use ($location) {
                    $sq->where('location_branch', $location);
                });
            })
            ->whereBetween('reconciliation_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->with(['waiter', 'verifiedBy'])
            ->orderBy('reconciliation_date', 'desc')
            ->get();
        $waiterReconciliations = $waiterReconciliations->values();

        // ── Payments Log (Detailed breakdown for the accountant)
        $paymentSearch = $request->get('payment_search');
        $paymentMethod = $request->get('payment_method');
        $paymentStaff = $request->get('payment_staff');

        $paymentsQuery = OrderPayment::query()
            ->whereHas('order', function($q) use ($ownerId) {
                $q->where('user_id', $ownerId);
            })
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->with(['order.waiter', 'order.table']);

        if ($paymentSearch) {
            $paymentsQuery->where(function($q) use ($paymentSearch) {
                $q->where('transaction_reference', 'like', "%{$paymentSearch}%")
                  ->orWhere('mobile_money_number', 'like', "%{$paymentSearch}%")
                  ->orWhereHas('order', function($sq) use ($paymentSearch) {
                      $sq->where('order_number', 'like', "%{$paymentSearch}%");
                  });
            });
        }
        if ($paymentMethod) {
            $paymentsQuery->where('payment_method', $paymentMethod);
        }
        if ($paymentStaff) {
            $paymentsQuery->whereHas('order', function($q) use ($paymentStaff) {
                $q->where('waiter_id', $paymentStaff);
            });
        }

        $payments = $paymentsQuery->orderBy('created_at', 'desc')
            ->paginate(30, ['*'], 'payments_page');

        $staffMembers = Staff::where('user_id', $ownerId)->get();

        return view('accountant.reconciliations', compact(
            'financialReconciliations',
            'waiterReconciliations',
            'payments',
            'startDate',
            'endDate',
            'tab',
            'staffMembers',
            'canReconcile'
        ));
    }

    /**
     * Finalize Department Reconciliation (Accountant Action)
     */
    public function finalizeDepartmentReconciliation(Request $request)
    {
        if (!$this->hasPermission('finance', 'edit')) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:bar,food',
            'cash_received' => 'required|numeric|min:0',
            'mobile_received' => 'required|numeric|min:0',
            'bank_received' => 'required|numeric|min:0',
            'card_received' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $ownerId = $this->getOwnerId();
        $date = $validated['date'];
        $type = $validated['type'];

        // 1. Get all pending orders for this department and date
        $orders = BarOrder::where('user_id', $ownerId)
            ->whereDate('created_at', $date)
            ->where('status', 'served')
            ->whereDoesntHave('reconciliation', function($q) {
                $q->where('status', 'verified');
            })
            ->with(['items', 'kitchenOrderItems', 'orderPayments'])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No pending orders found for this period.']);
        }

        // 2. We group orders by waiter to create reconciliation records for each
        $waiterGroups = $orders->groupBy('waiter_id');
        $processedWaiters = 0;

        foreach ($waiterGroups as $waiterId => $waiterOrders) {
            if (!$waiterId) continue;
            
            $expected = 0;
            if ($type === 'bar') {
                $expected = $waiterOrders->sum(function($o) { return $o->items->sum('total_price'); });
            } else {
                $expected = $waiterOrders->sum(function($o) { return $o->kitchenOrderItems->sum('total_price'); });
            }

            if ($expected <= 0) continue;

            // For the sake of this aggregate action, we distribute the 'actual' amounts proportionally 
            // or just attribute the 'recorded' amounts as a baseline.
            // But since the accountant is doing a WHOLE department, we might just mark them all 'submitted' 
            // and the 'Actual' goes into a master record or distributed.
            
            // 3. We use the ACTUAL inputs from the accountant (re-distributed proportionally if needed)
            // For now, since they reconcile the whole department, we set the actuals on each waiter record
            // OR we could create a master record. But the table in the UI sums them up.
            
            // CRITICAL FIX: Use the accountant's input!
            // Wait, proportional distribution is safer for multiple waiters:
            $totalCashInput = $validated['cash_received'];
            $totalMobileInput = $validated['mobile_received'];
            $totalBankInput = $validated['bank_received'];
            $totalCardInput = $validated['card_received'];
            $totalExpectedAll = $orders->sum(function($o) use ($type) { 
                return ($type === 'bar') ? $o->items->sum('total_price') : $o->kitchenOrderItems->sum('total_price');
            });

            $proportion = ($totalExpectedAll > 0) ? ($expected / $totalExpectedAll) : 0;
            $waiterCashActual = $totalCashInput * $proportion;
            $waiterMobileActual = $totalMobileInput * $proportion;
            $waiterBankActual = $totalBankInput * $proportion;
            $waiterCardActual = $totalCardInput * $proportion;
            $waiterSubmittedActual = $waiterCashActual + $waiterMobileActual + $waiterBankActual + $waiterCardActual;

            $reconciliation = WaiterDailyReconciliation::updateOrCreate(
                [
                    'user_id' => $ownerId,
                    'waiter_id' => $waiterId,
                    'reconciliation_date' => $date,
                    'reconciliation_type' => $type,
                ],
                [
                    'expected_amount' => $expected,
                    'submitted_amount' => $waiterSubmittedActual,
                    'cash_collected' => $waiterCashActual,
                    'mobile_money_collected' => $waiterMobileActual,
                    'bank_collected' => $waiterBankActual,
                    'card_collected' => $waiterCardActual,
                    'difference' => $waiterSubmittedActual - $expected,
                    'status' => 'verified',
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                    'notes' => $validated['notes'] . " (Bulk reconciled by Accountant)"
                ]
            );

            // Mark orders as reconciled
            foreach ($waiterOrders as $order) {
                $order->update(['reconciliation_id' => $reconciliation->id, 'payment_status' => 'paid']);
            }
            
            $processedWaiters++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully reconciled {$processedWaiters} staff records for this department."
        ]);
    }

    /**
     * Verify a financial reconciliation (Counter/Chef)
     */
    public function verifyFinancialReconciliation(Request $request, $id)
    {
        if (!$this->hasPermission('finance', 'edit')) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $reconciliation = WaiterDailyReconciliation::findOrFail($id);
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'required|in:verified,flagged'
        ]);

        $reconciliation->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reconciliation updated successfully.'
        ]);
    }

    /**
     * Counter Reconciliation (Accountant View - Proxy)
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
    private function calculateRealTimeProfitForTransfer($transfer, $ownerId, $sellingPrice, $buyingPrice, $location = null)
    {
        if ($transfer->status !== 'completed' || !$transfer->productVariant) {
            return 0;
        }

        // Get the date when transfer was completed
        $completedDate = $transfer->updated_at;
        
        // Find all order items from this product variant created after transfer completion
        $orderItems = \App\Models\OrderItem::where('product_variant_id', $transfer->product_variant_id)
            ->whereHas('order', function($query) use ($ownerId, $completedDate, $location) {
                $query->where('user_id', $ownerId)
                      ->where('created_at', '>=', $completedDate);
                
                if ($location) {
                    $query->whereHas('table', function($q) use ($location) {
                        $q->where('location', $location);
                    });
                }
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
    private function calculateRealTimeRevenueForTransfer($transfer, $ownerId, $location = null)
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
            ->whereHas('order', function($query) use ($ownerId, $completedDate, $location) {
                $query->where('user_id', $ownerId)
                      ->where('created_at', '>=', $completedDate);
                
                if ($location) {
                    $query->whereHas('table', function($q) use ($location) {
                        $q->where('location', $location);
                    });
                }
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
        $location = session('active_location');

        // Helper to apply common filters (owner + location)
        $applyFilters = function($query) use ($ownerId, $location) {
            $query->where('user_id', $ownerId);
            if ($location) {
                $query->whereHas('table', function($q) use ($location) {
                    $q->where('location', $location);
                });
            }
            return $query;
        };

        // Revenue by Day (all orders)
        $revenueByDay = [];
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($currentDate->lte($end)) {
            $dayOrders = $applyFilters(BarOrder::query())
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

        // Revenue by Waiter (branch-filtered if applicable)
        $revenueByWaiter = Staff::query()
            ->where('user_id', $ownerId)
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
            })
            ->when($location, function($q) use ($location) {
                $q->where('location_branch', $location);
            })
            ->get()
            ->map(function($waiter) use ($startDate, $endDate, $ownerId, $location) {
                $query = BarOrder::query()
                    ->where('user_id', $ownerId)
                    ->where('waiter_id', $waiter->id)
                    ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
                    ->where('payment_status', 'paid');
                
                if ($location) {
                    $query->whereHas('table', function($q) use ($location) {
                        $q->where('location', $location);
                    });
                }

                $orders = $query->with(['items', 'kitchenOrderItems'])->get();

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

        return view('accountant.reports', compact('revenueByDay', 'revenueByWaiter', 'startDate', 'endDate'));
    }

    /**
     * Stock Receipt Reports
     */
    public function stockReceiptsReport(Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to view stock receipt reports.');
        }

        $ownerId = $this->getOwnerId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $location = session('active_location');

        $receipts = \App\Models\StockReceipt::where('user_id', $ownerId)
            ->whereBetween('received_date', [$startDate, $endDate])
            ->when($location, function($q) use ($location) {
                // If location is provided, filter by requested_by branch (assuming staff belong to branches)
                $q->whereExists(function($sq) use ($location) {
                    $sq->select(DB::raw(1))
                       ->from('staff')
                       ->whereRaw('staff.user_id = stock_receipts.user_id')
                       ->whereRaw('staff.email = (select email from users where id = stock_receipts.received_by limit 1)')
                       ->where('staff.location_branch', $location);
                });
            })
            ->with(['supplier', 'productVariant.product', 'receivedBy'])
            ->orderBy('received_date', 'desc')
            ->get();

        $groupSummary = \App\Models\StockReceipt::where('user_id', $ownerId)
            ->whereBetween('received_date', [$startDate, $endDate])
            ->when($location, function($q) use ($location) {
                $q->whereExists(function($sq) use ($location) {
                    $sq->select(DB::raw(1))
                       ->from('staff')
                       ->whereRaw('staff.user_id = stock_receipts.user_id')
                       ->whereRaw('staff.email = (select email from users where id = stock_receipts.received_by limit 1)')
                       ->where('staff.location_branch', $location);
                });
            })
            ->selectRaw('sum(final_buying_cost) as total_buying_cost')
            ->selectRaw('count(distinct receipt_number) as unique_batches')
            ->selectRaw('sum(total_units) as total_items')
            ->first();

        return view('accountant.stock-receipts-report', compact('receipts', 'groupSummary', 'startDate', 'endDate'));
    }

    /**
     * Stock Transfer Reports (with Real-time tracking)
     */
    public function stockTransfersReport(Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view')) {
            abort(403, 'You do not have permission to view stock transfer reports.');
        }

        $ownerId = $this->getOwnerId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $location = session('active_location');

        $transfers = \App\Models\StockTransfer::where('user_id', $ownerId)
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->when($location, function($q) use ($location) {
                $q->whereExists(function($sq) use ($location) {
                    $sq->select(DB::raw(1))
                       ->from('staff')
                       ->whereRaw('staff.user_id = stock_transfers.user_id')
                       ->whereRaw('staff.email = (select email from users where id = stock_transfers.requested_by limit 1)')
                       ->where('staff.location_branch', $location);
                });
            })
            ->with(['productVariant.product', 'requestedBy', 'verifiedBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($transfer) use ($ownerId, $location) {
                $financials = $transfer->calculateFinancials();
                $transfer->expected_revenue = $financials['revenue'];
                $transfer->expected_profit = $financials['profit'];
                
                // Real-time data (branch-aware)
                $revenueData = $this->calculateRealTimeRevenueForTransfer($transfer, $ownerId, $location);
                $transfer->real_time_revenue = $revenueData['total'];
                $transfer->real_time_submitted = $revenueData['submitted'];
                $transfer->real_time_profit = $this->calculateRealTimeProfitForTransfer(
                    $transfer, 
                    $ownerId, 
                    $financials['selling_price'], 
                    $financials['buying_price'],
                    $location
                );
                
                return $transfer;
            });

        $totals = [
            'expected_revenue' => $transfers->sum('expected_revenue'),
            'expected_profit' => $transfers->sum('expected_profit'),
            'real_time_revenue' => $transfers->sum('real_time_revenue'),
            'real_time_profit' => $transfers->sum('real_time_profit')
        ];

        return view('accountant.stock-transfers-report', compact('transfers', 'totals', 'startDate', 'endDate'));
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
        $location = session('active_location');

        // Helper to apply common filters (owner + location)
        $applyFilters = function($query) use ($ownerId, $location) {
            $query->where('user_id', $ownerId);
            if ($location) {
                $query->whereHas('table', function($q) use ($location) {
                    $q->where('location', $location);
                });
            }
            return $query;
        };

        // Revenue by Day (all orders)
        $revenueByDay = [];
        $currentDate = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($currentDate->lte($end)) {
            $dayOrders = $applyFilters(BarOrder::query())
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

        $revenueByWaiter = Staff::query()
            ->where('user_id', $ownerId)
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
            })
            ->when($location, function($q) use ($location) {
                $q->where('location_branch', $location);
            })
            ->get()
            ->map(function($waiter) use ($startDate, $endDate, $ownerId, $location, $applyFilters) {
                $orders = $applyFilters(BarOrder::query())
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

    /**
     * Petty Cash / Fund Issuance List
     */
    public function fundIssuance(Request $request)
    {
        if (!$this->hasPermission('finance', 'view')) {
            abort(403);
        }

        $ownerId = $this->getOwnerId();
        
        $query = PettyCashIssue::where('user_id', $ownerId)
            ->with(['recipient', 'issuer'])
            ->orderBy('issue_date', 'desc');

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->whereBetween('issue_date', [$request->start_date, $request->end_date]);
        }

        $issues = $query->paginate(20);
        $staffMembers = Staff::where('user_id', $ownerId)->get();

        return view('accountant.fund_issuance', compact('issues', 'staffMembers'));
    }

    /**
     * Store New Fund Issuance
     */
    public function storeFundIssuance(Request $request)
    {
        if (!$this->hasPermission('finance', 'edit')) {
            return back()->with('error', 'Unauthorized');
        }

        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'amount' => 'required|numeric|min:0',
            'purpose' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        PettyCashIssue::create([
            'user_id' => $this->getOwnerId(),
            'issued_by' => auth()->id(),
            'staff_id' => $request->staff_id,
            'amount' => $request->amount,
            'purpose' => $request->purpose,
            'issue_date' => $request->issue_date,
            'notes' => $request->notes,
            'status' => 'issued'
        ]);

        return back()->with('success', 'Funds issued successfully.');
    }

    /**
     * Update Fund Issuance Status
     */
    /**
     * Re-open a previously finalized department shift (undo reconciliation)
     */
    public function reopenDepartmentShift(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string|in:bar,food',
        ]);

        $ownerId = $this->getOwnerId();
        $date = $validated['date'];
        $type = $validated['type'];

        DB::beginTransaction();
        try {
            // Find the reconciliations for this department/date
            $reconciliations = WaiterDailyReconciliation::where('user_id', $ownerId)
                ->where('reconciliation_date', $date)
                ->where('reconciliation_type', $type)
                ->get();

            foreach ($reconciliations as $recon) {
                // We don't necessarily need to touch the orders if they are linked via a relationship
                // but if we want them to show up as 'Served' but 'Un-reconciled', deleting the record is enough.
                $recon->delete();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Shift re-opened successfully. You can now re-reconcile it.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to re-open shift: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark a shortage as paid/cleared
     */
    public function payShortage(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string|in:bar,food',
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string|in:cash,mobile_money,bank_transfer,pos_card',
        ]);

        $ownerId = $this->getOwnerId();
        $date = $validated['date'];
        $type = $validated['type'];

        DB::beginTransaction();
        try {
            $reconciliations = WaiterDailyReconciliation::where('user_id', $ownerId)
                ->where('reconciliation_date', $date)
                ->where('reconciliation_type', $type)
                ->get();

            foreach ($reconciliations as $recon) {
                // Tracking total and breakdown
                $method = $validated['method'];
                $amount = (int)$validated['amount'];
                
                // 1. Total Tracking
                $totalPaidSoFar = 0;
                preg_match('/\[ShortagePaidTotal:(\d+)\]/', $recon->notes, $tm);
                if (isset($tm[1])) {
                    $totalPaidSoFar = (int)$tm[1];
                    $recon->notes = preg_replace('/\[ShortagePaidTotal:\d+\]/', '', $recon->notes);
                }
                $newTotal = $totalPaidSoFar + $amount;
                
                // 2. Breakdown Tracking (cash=100,mobile=200)
                $breakdown = [];
                preg_match('/\[ShortagePaidBreakdown:([^\]]+)\]/', $recon->notes, $bm);
                if (isset($bm[1])) {
                    $parts = explode(',', $bm[1]);
                    foreach($parts as $p) {
                        list($k, $v) = explode('=', $p);
                        $breakdown[$k] = (int)$v;
                    }
                    $recon->notes = preg_replace('/\[ShortagePaidBreakdown:[^\]]+\]/', '', $recon->notes);
                }
                $breakdown[$method] = ($breakdown[$method] ?? 0) + $amount;
                
                $breakdownStr = "";
                foreach($breakdown as $k => $v) $breakdownStr .= ($breakdownStr ? ',' : '') . "{$k}={$v}";

                $recon->notes .= " | [ShortagePaidTotal:{$newTotal}] [ShortagePaidBreakdown:{$breakdownStr}] - TSh " . number_format($amount) . " received via " . strtoupper($method) . " by Accountant on " . now()->toDateTimeString();
                $recon->save();
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Payment recorded successfuly.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to pay shortage: ' . $e->getMessage()], 500);
        }
    }

    public function updateFundStatus(Request $request, $id)
    {
        if (!$this->hasPermission('finance', 'edit')) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $issue = PettyCashIssue::findOrFail($id);
        $request->validate(['status' => 'required|in:completed,cancelled']);

        $issue->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status updated.']);
    }
}
