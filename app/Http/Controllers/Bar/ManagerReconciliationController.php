<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\Staff;
use App\Models\WaiterDailyReconciliation;
use App\Models\OrderPayment;
use App\Models\PettyCashIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ManagerReconciliationController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Manager Dashboard - Financial Overview
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

        // Today's Expenses (Petty Cash)
        $todayExpenses = PettyCashIssue::where('user_id', $ownerId)
            ->whereDate('issue_date', $date)
            ->where('status', 'issued')
            ->sum('amount');

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
                    
                    // Calculate real-time profit and revenue (FIFO-aware)
                    $metrics = $this->calculateRealTimeMetricsForTransfer($transfer, $ownerId, $buyingPrice, $location);
                    $transfer->real_time_profit = $metrics['profit'];
                    $transfer->real_time_revenue = $metrics['total'];
                    $transfer->real_time_units_sold = $metrics['units_sold'];
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

        return view('bar.manager.dashboard', compact(
            'date',
            'startDate',
            'endDate',
            'todayRevenue',
            'todayCash',
            'todayMobileMoney',
            'todayExpenses',
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
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('reports', 'view') && !$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view reconciliations.');
        }

        $ownerId = $this->getOwnerId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $location = session('active_location');
        $tab = $request->get('tab', 'financial'); // 'financial' or 'waiters' or 'payments'
        $canReconcile = $this->hasPermission('finance', 'edit');
        
        // Is user a manager/accountant who can see performance charts?
        $isManagerView = $this->hasPermission('finance', 'view') || $this->hasPermission('reports', 'view');
        
        // Determine if we should filter by department (Counter = bar, Chef = food)
        $currentStaff = $this->getCurrentStaff();
        $roleSlug = strtolower(trim($currentStaff->role->slug ?? ''));
        $deptFilter = null;
        if (in_array($roleSlug, ['counter', 'waiter'])) {
            $deptFilter = 'bar';
        } elseif ($roleSlug === 'chef') {
            $deptFilter = 'food';
        }

        // ── Financial Reconciliations Aggregate (By Date & Type)
        // 0. Get submitted handovers for visibility filter
        $handovers = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->whereBetween('handover_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->get();
        $handoverMap = $handovers->mapWithKeys(function($h) {
            if ($h->staff_shift_id) {
                return ['shift_' . $h->staff_shift_id . '_' . $h->department => $h];
            }
            $dateStr = $h->handover_date instanceof Carbon ? $h->handover_date->format('Y-m-d') : date('Y-m-d', strtotime($h->handover_date));
            return [$dateStr . '_' . $h->department => $h];
        });

        // 1. Get already submitted/verified reconciliations
        $submittedReconciliations = WaiterDailyReconciliation::query()
            ->where('user_id', $ownerId)
            ->when($location && $location !== 'all', function($q) use ($location) {
                $q->whereHas('waiter', function($sq) use ($location) {
                    $sq->where('location_branch', $location);
                });
            })
            ->when($deptFilter, function($q) use ($deptFilter) {
                // In DB, reconciliation_type for food might be 'kitchen' or 'food', but controller uses 'food'
                $dbType = $deptFilter === 'food' ? ['food', 'kitchen'] : [$deptFilter];
                $q->whereIn('reconciliation_type', $dbType);
            })
            ->whereBetween('reconciliation_date', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->selectRaw('MAX(reconciliation_date) as last_activity_date, reconciliation_type, staff_shift_id, SUM(expected_amount) as total_expected, SUM(submitted_amount) as total_submitted, SUM(cash_collected) as total_cash, SUM(mobile_money_collected) as total_mobile, SUM(bank_collected) as total_bank, SUM(card_collected) as total_card, COUNT(waiter_id) as waiter_count, MIN(status) as status_indicator, MAX(notes) as notes')
            ->groupBy('reconciliation_type', 'staff_shift_id', DB::raw("CASE WHEN staff_shift_id IS NULL THEN reconciliation_date ELSE '1900-01-01' END"))
            ->get()
            ->filter(function($r) use ($handoverMap) {
                // Only show to accountant if handover exists
                if ($r->staff_shift_id) {
                    $key = 'shift_' . $r->staff_shift_id . '_' . $r->reconciliation_type;
                } else {
                    $dateStr = ($r->last_activity_date instanceof \Carbon\Carbon) ? $r->last_activity_date->format('Y-m-d') : date('Y-m-d', strtotime($r->last_activity_date));
                    $key = $dateStr . '_' . $r->reconciliation_type;
                }
                return isset($handoverMap[$key]);
            })
            ->map(function($r) use ($handoverMap, $ownerId) {
                if ($r->staff_shift_id) {
                    $key = 'shift_' . $r->staff_shift_id . '_' . $r->reconciliation_type;
                } else {
                    $dateStr = ($r->last_activity_date instanceof \Carbon\Carbon) ? $r->last_activity_date->format('Y-m-d') : date('Y-m-d', strtotime($r->last_activity_date));
                    $key = $dateStr . '_' . $r->reconciliation_type;
                }
                
                $h = $handoverMap[$key] ?? null;
                
                // PROACTIVE SYNC: If all waiters are verified but handover is still pending, fix it now
                if ($r->status_indicator === 'verified' && $h && $h->status === 'pending') {
                    $h->update([
                        'status' => 'verified',
                        'confirmed_at' => now(),
                        'accountant_id' => $h->accountant_id ?: auth()->id()
                    ]);
                    $r->handover_status = 'verified';
                } else {
                    $r->handover_status = $h ? $h->status : 'pending';
                }

                $r->payment_breakdown = $h ? $h->payment_breakdown : [];
                $r->handover_staff_name = ($h && $h->staff) ? $h->staff->full_name : null;

                // AGGREGATE RECORDED PLATFORM BREAKDOWNS FROM ALL WAITERS
                $dbDate = ($r->reconciliation_date instanceof \Illuminate\Support\Carbon) ? $r->reconciliation_date->toDateString() : date('Y-m-d', strtotime($r->reconciliation_date));
                
                $recordedPlatformBreakdown = $this->getSystemExpectedBreakdown($ownerId, $dbDate, $r->reconciliation_type, $r->staff_shift_id);
                $submittedPlatformBreakdown = [];
                
                // Deduct Counter Expenses from Expected Sales to accurately reflect the balance
                if ($r->reconciliation_type === 'bar') {
                    $expensesQuery = \App\Models\CounterExpense::query();
                    if ($r->staff_shift_id) $expensesQuery->where('staff_shift_id', $r->staff_shift_id);
                    else $expensesQuery->where('expense_date', $dbDate);
                    $totalExpenses = $expensesQuery->sum('amount');
                    
                    $r->total_expected -= $totalExpenses;
                    $r->total_expenses = $totalExpenses;
                }
                
                $r->system_platform_breakdown = $recordedPlatformBreakdown;
                $r->recorded_platform_breakdown = $recordedPlatformBreakdown;

                // CRITICAL: TRUTH comes from Handover ($h) for submitted platforms
                if ($h && is_array($h->payment_breakdown)) {
                    $submittedPlatformBreakdown = [];
                    foreach ($h->payment_breakdown as $channel => $amt) {
                        $key = $this->normalizePaymentChannel($channel);
                        $submittedPlatformBreakdown[$key] = ($submittedPlatformBreakdown[$key] ?? 0) + (float)$amt;
                    }
                    $r->submitted_platform_breakdown = $submittedPlatformBreakdown;
                } else {
                    $r->submitted_platform_breakdown = $submittedPlatformBreakdown;
                }

                // Track shortage payments for summary and JS
                $paid = 0;
                $noteSource = ($h && $h->notes) ? $h->notes : ($r->notes ?? '');
                $r->notes = $noteSource;
                if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $noteSource, $m)) $paid = (int)$m[1];
                $r->shortage_paid = (float)$paid;
                
                $breakdown = "";
                if(preg_match('/\[ShortagePaidBreakdown:([^\]]+)\]/', $noteSource, $bm)) $breakdown = $bm[1];
                $r->shortage_breakdown = $breakdown;

                if ($h) {
                    $r->handover_id = $h->id;
                    $r->handover_amount = $h->amount;
                    $r->submitted_cash = 0;
                    $r->submitted_mobile = 0;
                    $r->submitted_bank = 0;
                    $r->submitted_card = 0;
                    
                    foreach ($h->payment_breakdown as $channel => $amt) {
                        $nk = $this->normalizePaymentChannel($channel);
                        $amt = (float)$amt;
                        if ($nk === 'cash') {
                            $r->submitted_cash += $amt;
                        } elseif (in_array($nk, ['mpesa', 'tigo_pesa', 'tpesa', 'airtel_money', 'halopesa', 'mixx'])) {
                            $r->submitted_mobile += $amt;
                        } elseif (in_array($nk, ['crdb', 'nmb', 'nbc', 'kcb', 'stanbic', 'equity', 'dtb', 'exim', 'azania', 'bank_transfer'])) {
                            $r->submitted_bank += $amt;
                        } elseif (in_array($nk, ['visa', 'mastercard']) || str_contains($nk, 'card') || str_contains($nk, 'pos')) {
                            $r->submitted_card += $amt;
                        }
                    }
                    $r->total_submitted_bag = $h->amount;
                } else {
                    $r->handover_amount = $r->total_submitted;
                    $r->total_submitted_bag = $r->total_submitted;
                    $r->submitted_cash = $r->total_cash;
                    $r->submitted_mobile = $r->total_mobile;
                }
                
                $r->profit_amount = $h ? (float)$h->profit_amount : 0;
                
                if ($r->profit_amount <= 0 && $r->reconciliation_type === 'bar') {
                    $profitQuery = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $dbDate, $r) {
                        $q->where('user_id', $ownerId)->where('status', 'served');
                        if ($r->staff_shift_id) {
                            $q->where('shift_id', $r->staff_shift_id);
                        } else {
                            $q->whereDate('created_at', $dbDate);
                        }
                    });
                    
                    $calcProfit = (float) $profitQuery->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                        ->selectRaw('SUM((order_items.unit_price - COALESCE(product_variants.buying_price_per_unit, 0)) * order_items.quantity) as profit')
                        ->value('profit');
                        
                    $r->profit_amount = $calcProfit;
                    
                    if ($h && $calcProfit > 0) {
                        \App\Models\FinancialHandover::where('id', $h->id)->update(['profit_amount' => $calcProfit]);
                    }
                }
                
                return $r;
            });

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
            $foodAmount = $order->kitchenOrderItems->sum('total_price');
            $totalOrderAmount = $barAmount + $foodAmount;

            if ($barAmount > 0 && (!$deptFilter || $deptFilter === 'bar')) {
                $key = $dateStr . '_bar';
                if (!isset($pendingAggr[$key])) {
                    $pendingAggr[$key] = [
                        'reconciliation_date' => $dateStr,
                        'reconciliation_type' => 'bar',
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
                $pendingAggr[$key]['total_expected'] += $barAmount;
                
                // Proportion of payment for bar
                $proportion = $totalOrderAmount > 0 ? ($barAmount / $totalOrderAmount) : 0;
                
                foreach ($order->orderPayments as $payment) {
                    $amount = $payment->amount * $proportion;
                    $pendingAggr[$key]['total_submitted'] += $amount;
                    $pendingAggr[$key]['status_indicator'] = 'submitted';
                    if ($payment->payment_method === 'cash') {
                        $pendingAggr[$key]['total_cash'] += $amount;
                    } else if ($payment->payment_method === 'mobile_money') {
                        $pendingAggr[$key]['total_mobile'] += $amount;
                    } else if ($payment->payment_method === 'bank_transfer') {
                        $pendingAggr[$key]['total_bank'] += $amount;
                    } else if (in_array($payment->payment_method, ['pos_card', 'card'])) {
                        $pendingAggr[$key]['total_card'] += $amount;
                    }
                }
            }

            // Chef Sales (Food)
            if ($foodAmount > 0 && (!$deptFilter || $deptFilter === 'food')) {
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
                
                // Proportion of payment for food
                $proportion = $totalOrderAmount > 0 ? ($foodAmount / $totalOrderAmount) : 0;

                foreach ($order->orderPayments as $payment) {
                    $amount = $payment->amount * $proportion;
                    $pendingAggr[$key]['total_submitted'] += $amount;
                    $pendingAggr[$key]['status_indicator'] = 'submitted';
                    if ($payment->payment_method === 'cash') {
                        $pendingAggr[$key]['total_cash'] += $amount;
                    } else if ($payment->payment_method === 'mobile_money') {
                        $pendingAggr[$key]['total_mobile'] += $amount;
                    } else if ($payment->payment_method === 'bank_transfer') {
                        $pendingAggr[$key]['total_bank'] += $amount;
                    } else if (in_array($payment->payment_method, ['pos_card', 'card'])) {
                        $pendingAggr[$key]['total_card'] += $amount;
                    }
                }
            }
        }

        // Merge submitted and pending
        // If a date/type has both, we might want to prioritize the submitted one or combine?
        // Usually, once 'Marked Paid', it vanishes from pending.
        $financialReconciliations = collect($submittedReconciliations);
        foreach ($pendingAggr as $item) {
            $key = $item['reconciliation_date'] . '_' . $item['reconciliation_type'];
            // Only add if handover exists AND it's not already in submitted
            if (isset($handoverMap[$key])) {
                $exists = $financialReconciliations->contains(function($r) use ($item) {
                    return $r->reconciliation_date->format('Y-m-d') == $item['reconciliation_date'] && $r->reconciliation_type == $item['reconciliation_type'];
                });
                if (!$exists) {
                    $item['reconciliation_date'] = Carbon::parse($item['reconciliation_date']);
                    $financialReconciliations->push((object)$item);
                }
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
            ->with(['waiter', 'verifiedBy', 'orders', 'orders.orderPayments', 'orders.table'])
            ->orderBy('reconciliation_date', 'desc')
            ->get();
        // Map to add computed display properties expected by the blade
        $waiterReconciliations = $waiterReconciliations->map(function($wr) {
            $wr->staff_name   = $wr->waiter ? $wr->waiter->full_name : 'Unknown Staff';
            $wr->staff_image  = ($wr->waiter && $wr->waiter->image) ? $wr->waiter->image : null;
            $wr->total_expected  = (float)($wr->expected_amount ?? 0);
            $wr->total_submitted = (float)($wr->submitted_amount ?? 0);
            $wr->status_indicator = $wr->status ?? 'pending';
            return $wr;
        })->values();

        // Build shortage settlement history from FinancialHandover notes
        $shortageHistory = collect();
        foreach ($handovers as $h) {
            if (!$h->notes) continue;
            
            // Prefer the precise machine-readable tag
            if (preg_match_all('/\[ShortagePaid:([^\]]+)\]/', $h->notes, $matches)) {
                foreach ($matches[1] as $entry) {
                    $parts = [];
                    foreach (explode(',', $entry) as $kv) {
                        if (strpos($kv, '=') !== false) {
                            [$k, $v] = explode('=', $kv, 2);
                            $parts[trim($k)] = trim($v);
                        }
                    }
                    if (empty($parts['amount'])) continue;
                    $dateVal = $h->handover_date instanceof \Carbon\Carbon ? $h->handover_date : \Carbon\Carbon::parse($h->handover_date);
                    $shortageHistory->push((object)[
                        'created_at'          => $dateVal,
                        'reconciliation_date'  => $dateVal,
                        'reconciliation_type'  => $h->department ?? 'bar',
                        'staff_name'           => $parts['staff'] ?? null,
                        'amount'               => (int)($parts['amount'] ?? 0),
                        'channel'              => $parts['channel'] ?? 'cash',
                        'reference'            => $parts['ref'] ?? null,
                        'Manager_name'         => 'Manager',
                    ]);
                }
            } 
            // Fallback to gracefully parsing the human-readable [ShortageNote: ...] 
            elseif (preg_match_all('/\[ShortageNote:\s*([^\]]+)\]/', $h->notes, $legacyMatches)) {
                foreach ($legacyMatches[1] as $entry) {
                    $amount = 0;
                    $channel = 'cash';
                    if (preg_match('/TSh\s*([0-9,]+)/', $entry, $am)) {
                        $amount = (int)str_replace(',', '', $am[1]);
                    }
                    if (preg_match('/\(([^)]+)\)/', $entry, $cm)) {
                        $channel = strtolower(str_replace(' ', '_', $cm[1]));
                    }
                    $dateVal = $h->handover_date instanceof \Carbon\Carbon ? $h->handover_date : \Carbon\Carbon::parse($h->handover_date);
                    $shortageHistory->push((object)[
                        'created_at'          => $dateVal,
                        'reconciliation_date'  => $dateVal,
                        'reconciliation_type'  => $h->department ?? 'bar',
                        'staff_name'           => null,
                        'amount'               => $amount,
                        'channel'              => $channel,
                        'reference'            => str_contains($entry, ' — ') ? explode(' — ', $entry)[1] : null,
                        'Manager_name'         => 'Manager',
                    ]);
                }
            }
        }
        $shortageHistory = $shortageHistory->sortByDesc('created_at')->values();

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

        $payments = $paymentsQuery->orderBy('created_at', 'desc')->get();

        $staffMembers = Staff::where('user_id', $ownerId)->get();

        // ── Financial Dashboard Charts (High Fidelity) ──
        $chartData = [
            'dates' => [],
            'expected' => [],
            'collected' => [],
            'methods' => [
                'Cash' => 0,
                'Mobile' => 0,
                'Bank' => 0,
                'Card' => 0
            ]
        ];

        // Group data by date for the trend graph
        $dailyResults = [];
        foreach ($financialReconciliations as $fr) {
            $dKey = \Carbon\Carbon::parse($fr->reconciliation_date)->format('M d');
            if(!isset($dailyResults[$dKey])) {
                $dailyResults[$dKey] = ['expected' => 0, 'collected' => 0];
            }
            
            // Tracking payments
            $paid = 0;
            if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $fr->notes ?? '', $m)) $paid = (int)$m[1];
            
            $dailyResults[$dKey]['expected'] += (float)$fr->total_expected;
            $dailyResults[$dKey]['collected'] += ((float)$fr->total_submitted + $paid);

            // Channel aggregation for Pie chart
            $breakdown = [];
            if(preg_match('/\[ShortagePaidBreakdown:([^\]]+)\]/', $fr->notes ?? '', $bm)) {
                foreach(explode(',', $bm[1]) as $p) {
                    $kv = explode('=', $p);
                    if(count($kv) == 2) $breakdown[$kv[0]] = (int)$kv[1];
                }
            }
            $chartData['methods']['Cash'] += ((float)$fr->total_cash + ($breakdown['cash'] ?? 0));
            $chartData['methods']['Mobile'] += ((float)$fr->total_mobile + ($breakdown['mobile_money'] ?? 0));
            $chartData['methods']['Bank'] += ((float)($fr->total_bank ?? 0) + ($breakdown['bank_transfer'] ?? 0));
            $chartData['methods']['Card'] += ((float)($fr->total_card ?? 0) + ($breakdown['pos_card'] ?? 0));
        }

        // Fill final chart arrays in chronological order
        ksort($dailyResults);
        foreach($dailyResults as $date => $vals) {
            $chartData['dates'][] = $date;
            $chartData['expected'][] = $vals['expected'];
            $chartData['collected'][] = $vals['collected'];
        }

        // ── Estimated Profit Calculation (Bar + Kitchen) ──
        $barProfit = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $startDate, $endDate, $location) {
            $q->where('user_id', $ownerId)
              ->where('status', 'served')
              ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
            if($location && $location !== 'all') {
                $q->whereHas('table', function($sq) use ($location) { $sq->where('location', $location); });
            }
        })
        ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
        ->selectRaw('SUM((order_items.unit_price - COALESCE(product_variants.buying_price_per_unit, 0)) * order_items.quantity) as profit')
        ->value('profit') ?? 0;

        $foodProfit = \App\Models\KitchenOrderItem::whereHas('order', function($q) use ($ownerId, $startDate, $endDate, $location) {
            $q->where('user_id', $ownerId)
              ->where('status', 'served')
              ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
            if($location && $location !== 'all') {
                $q->whereHas('table', function($sq) use ($location) { $sq->where('location', $location); });
            }
        })
        ->join('food_items', 'kitchen_order_items.food_item_id', '=', 'food_items.id')
        ->selectRaw('SUM((kitchen_order_items.unit_price - 0) * kitchen_order_items.quantity) as profit')
        ->value('profit') ?? 0;

        $summaryProfit = (float)$barProfit + (float)$foodProfit;

        return view('bar.manager.reconciliations', compact(
            'financialReconciliations',
            'waiterReconciliations',
            'payments',
            'startDate',
            'endDate',
            'tab',
            'staffMembers',
            'canReconcile',
            'chartData',
            'summaryProfit',
            'isManagerView',
            'handoverMap',
            'shortageHistory'
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
            'shift_id' => 'nullable|integer',
            'platform_amounts' => 'required|array',
            'circulation_money' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $ownerId = $this->getOwnerId();
        $date = $validated['date'];
        $type = $validated['type'];
        $shiftId = $validated['shift_id'] ?? null;

        // 1. Get all orders for this department/shift that have NOT been manager-verified yet.
        if ($shiftId) {
            // Shift-based: fetch ALL orders for this shift directly.
            // The shift_id is the authoritative reference — ignore any stale reconciliation_id links.
            $ordersQuery = BarOrder::where('orders.user_id', $ownerId)
                ->where('orders.shift_id', $shiftId)
                ->whereIn('orders.status', ['served', 'closed'])
                ->select('orders.*');
        } else {
            // Date-based: use LEFT JOIN to allow orders with no reconciliation 
            // OR ones whose reconciliation is not yet verified.
            $ordersQuery = BarOrder::where('orders.user_id', $ownerId)
                ->whereIn('orders.status', ['served', 'closed'])
                ->leftJoin('waiter_daily_reconciliations as wdr', 'orders.reconciliation_id', '=', 'wdr.id')
                ->where(function($q) {
                    $q->whereNull('orders.reconciliation_id')
                      ->orWhere('wdr.status', '!=', 'verified');
                })
                ->whereDate('orders.created_at', $date)
                ->select('orders.*');
        }
            
        $orders = $ordersQuery->with(['items', 'kitchenOrderItems', 'orderPayments'])
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
            $totalExpectedAll = $orders->sum(function($o) use ($type) { 
                return ($type === 'bar') ? $o->items->sum('total_price') : $o->kitchenOrderItems->sum('total_price');
            });

            $proportion = ($totalExpectedAll > 0) ? ($expected / $totalExpectedAll) : 0;
            
            // Map the dynamic platform amounts into the legacy model categories for backward compatibility
            // but also preserve the full breakdown in notes.
            $waiterCashActual = ($validated['platform_amounts']['cash'] ?? 0) * $proportion;
            
            $mobileKeys = ['mpesa', 'tigo_pesa', 'tpesa', 'airtel_money', 'halopesa', 'mixx'];
            $bankKeys = ['nmb', 'crdb', 'nbc', 'kcb', 'equity', 'dtb', 'exim', 'azania', 'stanbic', 'bank_transfer', 'bank', 'absa'];
            $cardKeys = ['visa', 'mastercard', 'bank_card'];

            $sumMobile = 0; foreach($mobileKeys as $mk) if(isset($validated['platform_amounts'][$mk])) $sumMobile += $validated['platform_amounts'][$mk];
            $sumBank = 0; foreach($bankKeys as $bk) if(isset($validated['platform_amounts'][$bk])) $sumBank += $validated['platform_amounts'][$bk];
            $sumCard = 0; foreach($cardKeys as $ck) if(isset($validated['platform_amounts'][$ck])) $sumCard += $validated['platform_amounts'][$ck];

            $waiterMobileActual = $sumMobile * $proportion;
            $waiterBankActual = $sumBank * $proportion;
            $waiterCardActual = $sumCard * $proportion;

            $waiterSubmittedActual = $waiterCashActual + $waiterMobileActual + $waiterBankActual + $waiterCardActual;

            $reconciliation = WaiterDailyReconciliation::updateOrCreate(
                [
                    'user_id' => $ownerId,
                    'waiter_id' => $waiterId,
                    'staff_shift_id' => $shiftId,
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
                    'verified_by' => auth()->id(), // Use ONLY User ID to prevent constraint crashes
                    'notes' => ($validated['notes'] ?? '') . " (Bulk reconciled by Manager)"
                ]
            );

            // Mark orders as reconciled
            foreach ($waiterOrders as $order) {
                $order->update(['reconciliation_id' => $reconciliation->id, 'payment_status' => 'paid']);
            }
            
            $processedWaiters++;
        }

        // Create a master FinancialHandover record if it doesn't exist
        $totalAmtRecv = array_sum($validated['platform_amounts']);
        
        // Calculate total profit for this specific shift submission
        $profitAmount = 0;
        if ($type === 'bar') {
            $profitAmount = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $date, $shiftId) {
                $q->where('user_id', $ownerId)
                  ->whereIn('status', ['served', 'closed']);
                if ($shiftId) $q->where('shift_id', $shiftId);
                else $q->whereDate('created_at', $date);
            })->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
              ->selectRaw('SUM((order_items.unit_price - product_variants.buying_price_per_unit) * order_items.quantity) as profit')
              ->value('profit') ?? 0;
        } else {
            // Food Profit Calculation (using food_items table for cost)
            $profitAmount = \App\Models\KitchenOrderItem::whereHas('order', function($q) use ($ownerId, $date, $shiftId) {
                $q->where('user_id', $ownerId)
                  ->whereIn('status', ['served', 'closed']);
                if ($shiftId) $q->where('shift_id', $shiftId);
                else $q->whereDate('created_at', $date);
            })->join('food_items', 'kitchen_order_items.food_item_id', '=', 'food_items.id')
              ->selectRaw('SUM((kitchen_order_items.unit_price - food_items.cost_price) * kitchen_order_items.quantity) as profit')
              ->value('profit') ?? 0;
        }

        // Normalize platform keys for consistent storage
        $finalPlatformAmounts = [];
        foreach (($validated['platform_amounts'] ?? []) as $channel => $amt) {
            $c = strtolower(trim(str_replace([' ', '-'], '_', $channel)));
            if (str_contains($c, 'mpesa') || str_contains($c, 'm_pesa')) $c = 'mpesa';
            $finalPlatformAmounts[$c] = $amt;
        }

        // Create or update a master FinancialHandover record
        $totalAmtRecv = array_sum($validated['platform_amounts']);
        
        $handoverMatch = [
            'user_id' => $ownerId,
            'department' => $type,
        ];
        if ($shiftId) {
            $handoverMatch['staff_shift_id'] = $shiftId;
        } else {
            $handoverMatch['handover_date'] = $date;
            $handoverMatch['staff_shift_id'] = null;
        }

        $handover = \App\Models\FinancialHandover::updateOrCreate(
            $handoverMatch,
            [
                'handover_date' => $date, // Ensure it has the manager's requested date for the entry
                'accountant_id' => auth()->id() ?: session('staff_id'),
                'amount' => $totalAmtRecv,
                'circulation_money' => $validated['circulation_money'] ?? 0,
                'profit_amount' => $profitAmount,
                'handover_type' => 'staff_to_accountant',
                'status' => 'verified', // Manager audit is final
                'confirmed_at' => now(),
                'notes' => ($validated['notes'] ?? '') . " (Master record created via Finalize Dept)",
                'payment_breakdown' => $finalPlatformAmounts
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully reconciled {$processedWaiters} staff records and created master handover for this department."
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

        // Sync FinancialHandover status if all waiters for this shift/date are now verified
        $allVerified = WaiterDailyReconciliation::where('user_id', $reconciliation->user_id)
            ->where('reconciliation_type', $reconciliation->reconciliation_type)
            ->when($reconciliation->staff_shift_id, function($q) use ($reconciliation) {
                $q->where('staff_shift_id', $reconciliation->staff_shift_id);
            })
            ->when(!$reconciliation->staff_shift_id, function($q) use ($reconciliation) {
                $q->where('reconciliation_date', $reconciliation->reconciliation_date);
            })
            ->where('status', '!=', 'verified')
            ->count() === 0;

        if ($allVerified) {
            $handoverQuery = \App\Models\FinancialHandover::where('user_id', $reconciliation->user_id)
                ->where('department', $reconciliation->reconciliation_type);
            
            if ($reconciliation->staff_shift_id) {
                $handoverQuery->where('staff_shift_id', $reconciliation->staff_shift_id);
            } else {
                $handoverQuery->where('handover_date', $reconciliation->reconciliation_date);
            }

            $handoverQuery->update([
                'status' => 'verified',
                'confirmed_at' => now(),
                'accountant_id' => auth()->id()
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Reset/Reopen a Department Handover
     */
    public function resetHandover(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'type' => 'required|string'
        ]);

        $ownerId = $this->getOwnerId();
        
        $query = \App\Models\FinancialHandover::where('user_id', $ownerId);
        
        if ($request->filled('handover_id')) {
            $query->where('id', $request->handover_id);
        } else {
            $query->whereDate('handover_date', $request->date)
                  ->where('department', $request->type);
        }
        
        $handover = $query->first();

        if (!$handover) {
            return response()->json(['success' => false, 'error' => 'Handover records not found.'], 404);
        }

        DB::beginTransaction();
        try {
            // 1. Reopen the shift if linked
            if ($handover->staff_shift_id) {
                $shift = \App\Models\StaffShift::find($handover->staff_shift_id);
                if ($shift) {
                    $shift->update([
                        'status' => 'open',
                        'closed_at' => null,
                        'closing_balance' => null,
                        'total_sales_cash' => 0,
                        'total_sales_digital' => 0,
                        'notes' => ($shift->notes ?? '') . " | Reopened by Manager."
                    ]);
                }
            }

            // 2. Delete the handover record
            $handover->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Handover has been successfully reset. The counter can now re-submit.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
        }
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

        return view('bar.manager.reconciliation-details', compact('reconciliation'));
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
    private function calculateRealTimeMetricsForTransfer($transfer, $ownerId, $buyingPrice, $location = null)
    {
        if ($transfer->status !== 'completed' || !$transfer->productVariant) {
            return [
                'total' => 0, 
                'profit' => 0, 
                'units_sold' => 0, 
                'submitted' => 0, 
                'recorded' => 0,
                'unit_sales' => 0,
                'portion_sales' => 0
            ];
        }

        // 1. Calculate how many total units were received before THIS receipt
        $previousReceiptsUnits = \App\Models\StockReceipt::where('user_id', $ownerId)
            ->where('product_variant_id', $transfer->product_variant_id)
            ->where('created_at', '<', $transfer->created_at)
            ->sum('total_units');
            
        $movements = \App\Models\StockMovement::where('product_variant_id', $transfer->product_variant_id)
            ->where('from_location', 'counter') 
            ->where(function($q) {
                $q->whereNull('to_location')->orWhere('to_location', '!=', 'warehouse');
            })
            ->where('user_id', $ownerId)
            ->with(['reference', 'productVariant']) 
            ->orderBy('id', 'asc')
            ->get();
            
        $myStart = $previousReceiptsUnits;
        $myEnd = $previousReceiptsUnits + $transfer->total_units;

        $runningSales = 0;
        $allocatedUnits = 0;
        $allocatedRevenue = 0;
        $allocatedProfit = 0;
        $unitSalesCount = 0;
        $portionSalesCount = 0;
        $unitRevenue = 0;
        $portionRevenue = 0;
        $unitProfit = 0;
        $portionProfit = 0;
        
        foreach ($movements as $movement) {
            $qty = (float)$movement->quantity;
            $itemStart = $runningSales;
            $itemEnd = $runningSales + $qty;

            // Check overlap with THIS receipt's window
            $overlapStart = max($myStart, $itemStart);
            $overlapEnd = min($myEnd, $itemEnd);

            if ($overlapEnd > $overlapStart) {
                // This movement (or part of it) belongs to THIS batch
                $overlapQuantity = $overlapEnd - $overlapStart;
                $allocatedUnits += $overlapQuantity;

                // 3. Resolve the Order and Revenue for this movement
                $order = null;
                $ref = $movement->reference;
                $isPortion = false;
                
                if ($ref instanceof \App\Models\BarOrder) {
                    $order = $ref;
                } elseif ($ref instanceof \App\Models\OrderItem) {
                    $order = $ref->order;
                    $isPortion = ($ref->sell_type === 'tot');
                }

                if ($order && in_array($order->payment_status, ['paid', 'partial', 'pending']) && $order->status !== 'cancelled') {
                    // Ratio of payments to order total
                    $paymentRatio = 0;
                    $itemPrice = $movement->unit_price ?: ($movement->productVariant->selling_price_per_unit ?? 0);
                    
                    if ($order->total_amount > 0) {
                        $recordedPayments = DB::table('order_payments')->where('order_id', $order->id)->sum('amount');
                        $paymentRatio = min(1.0, $recordedPayments / $order->total_amount);
                    }
                    
                    $movementRevenue = $overlapQuantity * $itemPrice;
                    $revenueShare = $movementRevenue * $paymentRatio;
                } else {
                    // TRUST THE FRIDGE: If order is missing or not resolved, assume cash sale for this unit
                    $itemPrice = $movement->unit_price ?: ($movement->productVariant->selling_price_per_unit ?? 0);
                    $revenueShare = $overlapQuantity * $itemPrice;
                }

                $allocatedRevenue += $revenueShare;
                $allocatedProfit += $revenueShare; // Add back the earned revenue to profit

                // Breakdown for breakdown columns
                if ($isPortion) {
                    $portionSalesCount += $overlapQuantity; 
                    $portionRevenue += $revenueShare;
                } else {
                    $unitSalesCount += $overlapQuantity;
                    $unitRevenue += $revenueShare;
                }

                // ALWAYS subtract cost for physical units leaving this batch (OUTSIDE the order check)
                $unitBuyingPrice = (float)$buyingPrice;
                $profitShare = - ($overlapQuantity * $unitBuyingPrice);
                $allocatedProfit += $profitShare;
                
                if ($isPortion) {
                    $portionProfit += $profitShare;
                } else {
                    $unitProfit += $profitShare;
                }
            }

            // Update cumulative total AFTER checking overlap
            $runningSales += $qty;

            if ($runningSales >= $myEnd) {
                break;
            }
        }
        
        return [
            'total' => $allocatedRevenue,
            'profit' => $allocatedProfit,
            'units_sold' => $allocatedUnits,
            'submitted' => $allocatedRevenue,
            'recorded' => $allocatedRevenue,
            'unit_sales' => $unitSalesCount,
            'portion_sales' => $portionSalesCount,
            'unit_revenue' => $unitRevenue,
            'portion_revenue' => $portionRevenue,
            'unit_profit' => $unitProfit,
            'portion_profit' => $portionProfit
        ];
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

        return view('bar.manager.reports', compact('revenueByDay', 'revenueByWaiter', 'startDate', 'endDate'));
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
            ->paginate(50);

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

        return view('bar.manager.stock-receipts-report', compact('receipts', 'groupSummary', 'startDate', 'endDate'));
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

        // We now track StockReceipts (which go to Counter directly) instead of obsolete StockTransfers
        $transfers = \App\Models\StockReceipt::where('user_id', $ownerId)
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->when($location, function($q) use ($location) {
                $q->whereExists(function($sq) use ($location) {
                    $sq->select(DB::raw(1))
                       ->from('staff')
                       ->whereRaw('staff.user_id = stock_receipts.user_id')
                       ->whereRaw('staff.email = (select email from users where id = stock_receipts.received_by limit 1)')
                       ->where('staff.location_branch', $location);
                });
            })
            ->with(['productVariant.product', 'receivedBy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($receipt) use ($ownerId, $location) {
                // Map receipt fields to match what the 'stock-transfers-report' view expects
                $receipt->transfer_number = $receipt->receipt_number;
                $receipt->status = 'completed'; // Receipts are instantly in stock
                $receipt->expected_revenue = $receipt->total_selling_value;
                $receipt->expected_profit = $receipt->total_profit;
                
                // Real-time metrics based on FIFO allocation
                $metrics = $this->calculateRealTimeMetricsForTransfer(
                    $receipt, 
                    $ownerId, 
                    $receipt->buying_price_per_unit, 
                    $location
                );
                
                $receipt->real_time_revenue = $metrics['total'];
                $receipt->real_time_submitted = $metrics['submitted'];
                $receipt->real_time_profit = $metrics['profit'];
                $receipt->real_time_units_sold = $metrics['units_sold'];
                $receipt->real_time_unit_sales = $metrics['unit_sales'];
                $receipt->real_time_portion_sales = $metrics['portion_sales'];
                $receipt->real_time_unit_revenue = $metrics['unit_revenue'];
                $receipt->real_time_portion_revenue = $metrics['portion_revenue'];
                $receipt->real_time_unit_profit = $metrics['unit_profit'];
                $receipt->real_time_portion_profit = $metrics['portion_profit'];
                $receipt->expected_revenue_bottle = $receipt->total_selling_value;
                $receipt->expected_revenue_glass = ($receipt->productVariant->can_sell_in_tots ?? false) ? ($receipt->total_units * ($receipt->productVariant->total_tots ?? 0) * ($receipt->selling_price_per_tot ?? 0)) : $receipt->total_selling_value;

                return $receipt;
            });

        $totals = [
            'expected_revenue' => $transfers->sum('expected_revenue'),
            'expected_profit' => $transfers->sum('expected_profit'),
            'real_time_revenue' => $transfers->sum('real_time_revenue'),
            'real_time_profit' => $transfers->sum('real_time_profit')
        ];

        return view('bar.manager.stock-transfers-report', compact('transfers', 'totals', 'startDate', 'endDate'));
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

        return view('bar.manager.fund_issuance', compact('issues', 'staffMembers'));
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

    public function cashLedger(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $date = $request->get('date', date('Y-m-d'));

        // Cash In: Verified Reconciliations (ONLY Physical Cash)
        $cashIn = WaiterDailyReconciliation::where('user_id', $ownerId)
            ->whereDate('reconciliation_date', $date)
            ->where('status', 'verified')
            ->with('waiter')
            ->get();

        // Independent Cash Injections (Starting Float, Bank Withdrawals, etc.)
        $topups = \App\Models\CashTopup::where('user_id', $ownerId)
            ->whereDate('topup_date', $date)
            ->get();

        // Cash Out: Issued Petty Cash
        $cashOut = PettyCashIssue::where('user_id', $ownerId)
            ->whereDate('issue_date', $date)
            ->where('status', 'issued')
            ->with('recipient')
            ->get();

        $totalIn = $cashIn->sum('cash_collected') + $topups->sum('amount');
        $totalOut = $cashOut->sum('amount');
        $netCash = $totalIn - $totalOut;

        $handover = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->whereDate('handover_date', $date)
            ->where('handover_type', 'accountant_to_owner')
            ->first();

        // Staff handovers to accountant (Chef, Counter → Accountant)
        $staffHandovers = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->whereDate('handover_date', $date)
            ->where('handover_type', 'staff_to_accountant')
            ->with('staff')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalStaffHandovers = $staffHandovers->sum('amount');

        return view('bar.manager.cash_ledger', compact(
            'cashIn', 'topups', 'cashOut', 'totalIn', 'totalOut', 'netCash',
            'date', 'handover', 'staffHandovers', 'totalStaffHandovers'
        ));
    }

    /**
     * Confirm a staff handover (Chef, Counter → Accountant)
     */
    public function confirmStaffHandover($id)
    {
        $ownerId = $this->getOwnerId();
        $handover = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->where('id', $id)
            ->where('handover_type', 'staff_to_accountant')
            ->firstOrFail();

        $handover->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Staff handover confirmed successfully.');
    }

    public function storeTopup(Request $request)
    {
        $ownerId = $this->getOwnerId();
        
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'source' => 'required|string',
            'topup_date' => 'required|date',
        ]);

        \App\Models\CashTopup::create([
            'user_id' => $ownerId,
            'accountant_id' => session('staff_id'),
            'amount' => $request->amount,
            'topup_date' => $request->topup_date,
            'source' => $request->source,
            'notes' => $request->notes
        ]);

        return back()->with('success', 'Cash addition recorded successfully.');
    }

    public function storeHandover(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $date = $request->input('date', date('Y-m-d'));
        
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        // Check if already exists
        $existing = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->whereDate('handover_date', $date)
            ->first();

        if ($existing) {
            return back()->with('error', 'Handover for this date already exists.');
        }

        \App\Models\FinancialHandover::create([
            'user_id' => $ownerId,
            'accountant_id' => session('staff_id'),
            'amount' => $request->amount,
            'handover_date' => $date,
            'status' => 'pending',
            'notes' => $request->notes
        ]);

        return back()->with('success', 'Handover to Boss successful! Awaiting confirmation.');
    }

    public function confirmHandover($id)
    {
        $ownerId = $this->getOwnerId();
        $handover = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->where('id', $id)
            ->firstOrFail();

        $handover->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Cash handover confirmed successfully.');
    }

    /**
     * Get orders for a specific department and date (AJAX)
     */
    public function getDepartmentOrders(Request $request)
    {
        if (!$this->hasPermission('finance', 'view') && !$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $date = $request->get('date');
        $type = $request->get('type');
        $ownerId = $this->getOwnerId();

        try {
            \Log::info('getDepartmentOrders called', ['date' => $date, 'type' => $type, 'owner' => $ownerId]);
            
            $orders = BarOrder::where('user_id', $ownerId)
                ->whereDate('created_at', $date)
                ->where('status', 'served')
                ->with(['waiter', 'table', 'items', 'kitchenOrderItems', 'orderPayments'])
                ->get();

            \Log::info('Fetched orders count', ['count' => $orders->count()]);

            $filteredOrders = [];
            foreach ($orders as $order) {
                $amount = 0;
                if ($type === 'bar') {
                    $amount = $order->items ? $order->items->sum('total_price') : 0;
                } else {
                    $amount = $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0;
                }

                if ($amount > 0) {
                    $filteredOrders[] = [
                        'order_number' => $order->order_number,
                        'waiter_name' => $order->waiter->full_name ?? 'N/A',
                        'table_name' => $order->table->name ?? 'Direct',
                        'total_amount' => $amount,
                        'payment_status' => $order->payment_status,
                        'created_at' => $order->created_at->format('H:i'),
                    ];
                }
            }

            $breakdown = $this->getDetailedPaymentBreakdown($orders, $type);

            return response()->json([
                'success' => true,
                'orders' => $filteredOrders,
                'payment_breakdown' => $breakdown
            ]);
        } catch (\Exception $e) {
            \Log::error('getDepartmentOrders failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Group payments by method and reference
     */
    private function getDetailedPaymentBreakdown($orders, $type)
    {
        $breakdownByMethod = [];
        foreach ($orders as $order) {
            $barAmount = $order->items ? $order->items->sum('total_price') : 0;
            $foodAmount = $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0;
            $totalAmount = $barAmount + $foodAmount;
            $proportion = $totalAmount > 0 ? (($type === 'bar' ? $barAmount : $foodAmount) / $totalAmount) : 0;

            if ($proportion <= 0) continue;

            foreach ($order->orderPayments as $payment) {
                $method = $payment->payment_method;
                $amount = $payment->amount * $proportion;
                
                if (!isset($breakdownByMethod[$method])) {
                    $breakdownByMethod[$method] = [
                        'total' => 0,
                        'transactions' => []
                    ];
                }
                $breakdownByMethod[$method]['total'] += $amount;
                $breakdownByMethod[$method]['transactions'][] = [
                    'order' => $order->order_number,
                    'waiter' => $order->waiter->full_name ?? 'N/A',
                    'amount' => $amount,
                    'reference' => $payment->transaction_reference,
                    'time' => $payment->created_at->format('H:i')
                ];
            }
        }
        return $breakdownByMethod;
    }

    /**
     * Clear/Pay a shortage for a department (Accountant Action)
     */
    public function payShortage(Request $request)
    {
        try {
            $request->validate([
                'date'      => 'required|date',
                'type'      => 'required|string',
                'amount'    => 'required|numeric|min:0',
                'channel'   => 'required|string',
                'reference' => 'nullable|string|max:500',
            ]);

            $ownerId = $this->getOwnerId();
            
            return DB::transaction(function() use ($request, $ownerId) {
                $query = \App\Models\FinancialHandover::where('user_id', $ownerId)
                    ->where('department', $request->type);
            
            if ($request->filled('handover_id')) {
                $query->where('id', $request->handover_id);
            } else {
                $query->whereDate('handover_date', $request->date);
            }
            
            $handover = $query->first();

            if (!$handover) {
                return response()->json(['success' => false, 'error' => 'Handover record not found for this date/department.'], 404);
            }

            $existingNotes = $handover->notes ?? '';
            $amount = (float)$request->amount;
            $shiftId = $request->shift_id ?: ($handover->staff_shift_id ?: null);
            
            // Intelligence: Automatically distribute settlement to platforms that are SHORT
            $systemExpected = $this->getSystemExpectedBreakdown($ownerId, $handover->handover_date, $handover->department, $shiftId);
            $staffSubmitted = $handover->payment_breakdown ?: [];
            
            $distBreakdown = [];
            $remainingPayment = $amount;

            // Phase 1: Bridge the Gaps (Pro-rata settlement for short rows)
            foreach ($systemExpected as $platform => $expected) {
                $actual = (float)($staffSubmitted[$platform] ?? 0);
                if ($actual < $expected && $remainingPayment > 0) {
                    $deficit = $expected - $actual;
                    $fill = min($deficit, $remainingPayment);
                    $distBreakdown[$platform] = ($distBreakdown[$platform] ?? 0) + $fill;
                    $remainingPayment -= $fill;
                }
            }

            // Phase 2: Any leftover surplus goes to the specifically selected channel
            if ($remainingPayment > 0) {
                $chosen = $this->normalizePaymentChannel($request->channel ?: 'cash');
                $distBreakdown[$chosen] = ($distBreakdown[$chosen] ?? 0) + $remainingPayment;
            }

            // Update the master record with Distributed Breakdown
            Log::info('Shortage Settlement Distribution', [
                'handover_id' => $handover->id,
                'total_amount' => $amount,
                'dist_breakdown' => $distBreakdown,
                'remaining' => $remainingPayment
            ]);

            $currentBreakdown = $handover->payment_breakdown ?: [];
            foreach ($distBreakdown as $p => $v) {
                $currentBreakdown[$p] = ($currentBreakdown[$p] ?? 0) + $v;
            }
            $handover->payment_breakdown = $currentBreakdown;
            $handover->amount += $amount;

            // Update Notes & Tags
            $existingNotes = $handover->notes ?? '';
            $shortagePaidTotal = 0;
            if (preg_match('/\[ShortagePaidTotal:(\d+)\]/', $existingNotes, $m)) $shortagePaidTotal = (int)$m[1];
            $newTotal = $shortagePaidTotal + $amount;

            $breakTags = [];
            if (preg_match('/\[ShortagePaidBreakdown:([^\]]+)\]/', $existingNotes, $bm)) {
                foreach (explode(',', $bm[1]) as $pair) {
                    $kv = explode('=', $pair);
                    if (count($kv) == 2) $breakTags[$kv[0]] = (float)$kv[1];
                }
            }
            foreach ($distBreakdown as $p => $v) $breakTags[$p] = ($breakTags[$p] ?? 0) + $v;
            
            $breakdownStr = "";
            foreach ($breakTags as $k => $v) $breakdownStr .= ($breakdownStr ? "," : "") . "{$k}={$v}";

            $newNotes = $existingNotes;
            $newNotes = preg_match('/\[ShortagePaidTotal:(\d+)\]/', $newNotes) 
                ? preg_replace('/\[ShortagePaidTotal:(\d+)\]/', "[ShortagePaidTotal:{$newTotal}]", $newNotes)
                : $newNotes . "\n[ShortagePaidTotal:{$newTotal}]";

            $newNotes = preg_match('/\[ShortagePaidBreakdown:[^\]]+\]/', $newNotes)
                ? preg_replace('/\[ShortagePaidBreakdown:[^\]]+\]/', "[ShortagePaidBreakdown:{$breakdownStr}]", $newNotes)
                : $newNotes . "\n[ShortagePaidBreakdown:{$breakdownStr}]";

            $noteStr = "Shortage settlement of TSh " . number_format($amount) . " distributed per platform on " . now()->format('d M y H:i');
            $newNotes .= "\n[ShortageNote: {$noteStr}]";
            $handover->notes = $newNotes;
            $handover->status = 'verified';
            $handover->confirmed_at = now();
            $handover->accountant_id = auth()->id() ?: (session('staff_id') ?: $handover->accountant_id);
            $handover->save();

                return response()->json(['success' => true, 'message' => 'Shortage of TSh ' . number_format($amount) . ' successfully recorded and distributed across platforms.']);
            });
        } catch (\Exception $e) {
            Log::error('Shortage Settlement Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['success' => false, 'error' => 'Failed to record settlement: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Canonical Truth: Calculate Expected (Net) Platform Breakdown for any audit context
     */
    private function getSystemExpectedBreakdown($ownerId, $date, $type, $shiftId = null)
    {
        $dbDate = $date instanceof \Carbon\Carbon ? $date->toDateString() : date('Y-m-d', strtotime($date));
        $recordedPlatformBreakdown = [];

        $ordersQuery = \App\Models\BarOrder::where('payment_status', 'paid')->with('orderPayments');
        if ($shiftId) $ordersQuery->where('shift_id', $shiftId);
        else $ordersQuery->where('user_id', $ownerId)->whereDate('created_at', $dbDate);

        foreach ($ordersQuery->get() as $order) {
            $barAmount = $order->items ? $order->items->sum('total_price') : 0;
            $foodAmount = $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0;
            $totalAmount = $barAmount + $foodAmount;
            $currentDeptAmount = ($type === 'bar') ? $barAmount : $foodAmount;
            $proportion = ($totalAmount > 0) ? ($currentDeptAmount / $totalAmount) : 0;
            if ($proportion <= 0) continue;

            foreach ($order->orderPayments as $payment) {
                $pProvider = strtolower(trim($payment->mobile_money_number ?? ''));
                $pMethod   = strtolower(trim($payment->payment_method ?? 'cash'));
                $pAmount   = (float)$payment->amount * $proportion;
                
                $key = 'cash';
                if ($pMethod !== 'cash') {
                    $key = $this->normalizePaymentChannel($pMethod);
                    if (str_contains($pProvider, 'nbc')) $key = 'nbc';
                    elseif (str_contains($pProvider, 'nmb')) $key = 'nmb';
                    elseif (str_contains($pProvider, 'crdb')) $key = 'crdb';
                    elseif (str_contains($pProvider, 'absa')) $key = 'absa';
                    elseif (str_contains($pProvider, 'halopesa')) $key = 'halopesa';
                    elseif (str_contains($pProvider, 'mpesa')) $key = 'mpesa';
                    elseif (str_contains($pProvider, 't-pesa') || str_contains($pProvider, 'tpesa') || str_contains($pProvider, 'ttcl')) $key = 'tpesa';
                }
                $recordedPlatformBreakdown[$key] = ($recordedPlatformBreakdown[$key] ?? 0) + $pAmount;
            }
        }

        if ($type === 'bar') {
            $expensesQuery = \App\Models\CounterExpense::query();
            if ($shiftId) $expensesQuery->where('staff_shift_id', $shiftId);
            else $expensesQuery->where('expense_date', $dbDate);

            foreach ($expensesQuery->get() as $exp) {
                $method = $this->normalizePaymentChannel($exp->payment_method ?: 'cash');
                $labelFound = isset($recordedPlatformBreakdown[$method]) ? $method : null;
                if (!$labelFound) {
                    foreach ($recordedPlatformBreakdown as $channel => $amt) {
                        if (str_contains($channel, $method)) { $labelFound = $channel; break; }
                    }
                }
                
                if ($labelFound) {
                    $recordedPlatformBreakdown[$labelFound] -= $exp->amount;
                    if ($labelFound !== 'cash' && $recordedPlatformBreakdown[$labelFound] < 0) {
                        $recordedPlatformBreakdown['cash'] = ($recordedPlatformBreakdown['cash'] ?? 0) - abs($recordedPlatformBreakdown[$labelFound]);
                        $recordedPlatformBreakdown[$labelFound] = 0;
                    }
                } else {
                    $defKey = ($method === 'cash') ? 'cash' : $method;
                    $recordedPlatformBreakdown[$defKey] = ($recordedPlatformBreakdown[$defKey] ?? 0) - $exp->amount;
                }
            }
        }
        return $recordedPlatformBreakdown;
    }

    /**
     * Canonical key normalizer — used for BOTH system and handover breakdowns
     */
    public function normalizePaymentChannel($channel)
    {
        $c = strtolower(trim(str_replace([' ', '-', '_'], '', $channel)));
        if (str_contains($c, 'mpesa')) return 'mpesa';
        if (str_contains($c, 'tpesa') || str_contains($c, 'ttcl')) return 'tpesa';
        if (str_contains($c, 'tigo')) return 'tigo_pesa';
        if (str_contains($c, 'airtel')) return 'airtel_money';
        if (str_contains($c, 'halo')) return 'halopesa';
        if (str_contains($c, 'mixx')) return 'mixx';
        if (str_contains($c, 'crdb')) return 'crdb';
        if (str_contains($c, 'nmb')) return 'nmb';
        if (str_contains($c, 'nbc')) return 'nbc';
        if (str_contains($c, 'kcb')) return 'kcb';
        if (str_contains($c, 'stanbic')) return 'stanbic';
        if (str_contains($c, 'equity')) return 'equity';
        if (str_contains($c, 'dtb') || str_contains($c, 'diamond')) return 'dtb';
        if (str_contains($c, 'exim')) return 'exim';
        if (str_contains($c, 'azania')) return 'azania';
        if (str_contains($c, 'visa')) return 'visa';
        if (str_contains($c, 'mastercard')) return 'mastercard';
        if (str_contains($c, 'cash')) return 'cash';
        if (str_contains($c, 'bank') || str_contains($c, 'transfer')) return 'bank_transfer';
        return $c;
    }
}
