<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Subscription;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index($role = null)
    {
        // IMPORTANT: Check for session conflicts - ensure only one type of session exists
        $isStaff = session('is_staff');
        $isUser = auth()->check();

        // If both exist, there's a conflict - clear staff session and use user
        if ($isStaff && $isUser) {
            session()->forget(['is_staff', 'staff_id', 'staff_name', 'staff_email', 'staff_role_id', 'staff_user_id']);
            $isStaff = false;
        }

        // Check if this is a staff member
        if ($isStaff) {
            $staff = \App\Models\Staff::find(session('staff_id'));
            
            if (!$staff || !$staff->is_active) {
                session()->forget(['is_staff', 'staff_id', 'staff_name', 'staff_email', 'staff_role_id', 'staff_user_id']);
                return redirect()->route('login')->with('error', 'Your staff account is no longer active.');
            }

            // IMPORTANT: Verify staff email matches session
            if ($staff->email !== session('staff_email')) {
                session()->forget(['is_staff', 'staff_id', 'staff_name', 'staff_email', 'staff_role_id', 'staff_user_id']);
                return redirect()->route('login')->with('error', 'Session mismatch. Please login again.');
            }

            // Get the owner's business info
            $owner = $staff->owner;
            
            // Get staff role slug for URL
            $roleSlug = $staff->role ? \Illuminate\Support\Str::slug($staff->role->name) : 'staff';
            
            // Redirect Counter staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'counter') {
                return redirect()->route('bar.counter.dashboard');
            }
            
            // Redirect Waiter staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'waiter') {
                return redirect()->route('bar.waiter.dashboard');
            }
            
            // Redirect Chef staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'chef') {
                return redirect()->route('bar.chef.dashboard');
            }
            
            // Redirect Accountant staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'accountant') {
                return redirect()->route('accountant.dashboard');
            }
            
            // Redirect Marketing staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'marketing') {
                return redirect()->route('marketing.dashboard');
            }
            
            // If URL doesn't include the role, redirect to include it
            if (!$role || $role !== $roleSlug) {
                return redirect()->route('dashboard.role', ['role' => $roleSlug]);
            }
            
            // Route Manager to dedicated dashboard with rich data
            $statistics = [];
            $roleName = strtolower($staff->role->name ?? '');
            $ownerId  = $owner->id;

            if ($roleName === 'manager') {
                $location = session('active_location');

                // Helper to apply branch filter to various models
                // It checks both the waiter/staff associated and the table location (if applicable)
                $applyLocation = function($query, $staffKey = 'waiter_id', $tableCheck = true) use ($location) {
                    if ($location) {
                        $query->where(function($q) use ($location, $staffKey, $tableCheck) {
                            // Filter by staff/waiter's branch
                            $q->whereExists(function ($sq) use ($location, $staffKey) {
                                $sq->select(\DB::raw(1))
                                   ->from('staff')
                                   ->whereColumn('staff.id', $staffKey)
                                   ->where('staff.location_branch', $location);
                            });

                            // OR Filter by table's location (if applicable)
                            if ($tableCheck) {
                                $q->orWhereHas('table', function($sq) use ($location) {
                                    $sq->where('location', $location);
                                });
                            }
                        });
                    }
                    return $query;
                };

                // ── Today's revenue
                $todayRevenue = $applyLocation(\App\Models\BarOrder::where('user_id', $ownerId))
                    ->whereDate('created_at', today())
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                // ── This month revenue
                $monthRevenue = $applyLocation(\App\Models\BarOrder::where('user_id', $ownerId))
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                // ── Today's orders
                $todayOrders = $applyLocation(\App\Models\BarOrder::where('user_id', $ownerId))
                    ->whereDate('created_at', today())
                    ->count();

                // ── Pending orders
                $pendingOrders = $applyLocation(\App\Models\BarOrder::where('user_id', $ownerId))
                    ->where('status', 'pending')
                    ->count();

                // ── Stock transfers summary
                // Filter by requester branch
                $pendingTransfers  = $applyLocation(\App\Models\StockTransfer::where('user_id', $ownerId), 'requested_by', false)
                    ->where('status', 'pending')->count();
                $approvedTransfers = $applyLocation(\App\Models\StockTransfer::where('user_id', $ownerId), 'requested_by', false)
                    ->where('status', 'approved')->count();
                $completedTransfersToday = $applyLocation(\App\Models\StockTransfer::where('user_id', $ownerId), 'requested_by', false)
                    ->where('status', 'completed')
                    ->whereDate('updated_at', today())
                    ->count();

                // ── Total transfer sales value
                $totalTransferSalesValue = \App\Models\TransferSale::whereHas('stockTransfer', function($q) use ($ownerId, $location) {
                    $q->where('user_id', $ownerId)
                      ->where('status', 'completed')
                      ->whereMonth('created_at', now()->month);
                    if ($location) {
                        $q->whereExists(function ($sq) use ($location) {
                            $sq->select(\DB::raw(1))
                               ->from('staff')
                               ->whereColumn('staff.id', 'stock_transfers.requested_by')
                               ->where('staff.location_branch', $location);
                        });
                    }
                })->sum('total_price');

                // ── Recent stock receipts (this month)
                // Filter by received_by branch
                $recentReceiptsQuery = \App\Models\StockReceipt::where('user_id', $ownerId)
                    ->with(['productVariant.product', 'supplier'])
                    ->whereMonth('received_date', now()->month);
                
                if ($location) {
                    $recentReceiptsQuery->whereExists(function ($sq) use ($location) {
                        $sq->select(\DB::raw(1))
                           ->from('staff')
                           ->whereColumn('staff.id', 'stock_receipts.received_by')
                           ->where('staff.location_branch', $location);
                    });
                }
                
                $recentReceipts = $recentReceiptsQuery->orderBy('received_date', 'desc')
                    ->limit(8)
                    ->get();

                // ── Monthly Purchase Cost
                $monthlyPurchaseCostQuery = \App\Models\StockReceipt::where('user_id', $ownerId)
                    ->whereMonth('received_date', now()->month);
                
                if ($location) {
                    $monthlyPurchaseCostQuery->whereExists(function ($sq) use ($location) {
                        $sq->select(\DB::raw(1))
                           ->from('staff')
                           ->whereColumn('staff.id', 'stock_receipts.received_by')
                           ->where('staff.location_branch', $location);
                    });
                }
                $monthlyPurchaseCost = $monthlyPurchaseCostQuery->sum('final_buying_cost');

                // ── Recent stock transfers (last 8)
                $recentTransfers = $applyLocation(\App\Models\StockTransfer::where('user_id', $ownerId), 'requested_by', false)
                    ->with(['productVariant.product', 'requestedBy', 'approvedBy'])
                    ->orderBy('created_at', 'desc')
                    ->limit(8)
                    ->get();

                // ── Revenue last 7 days
                $revenueTrend = $applyLocation(\App\Models\BarOrder::where('user_id', $ownerId))
                    ->where('payment_status', 'paid')
                    ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                    ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                // ── Top selling products this month
                $topProducts = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $location) {
                    $q->where('user_id', $ownerId)
                      ->where('payment_status', 'paid')
                      ->whereMonth('created_at', now()->month);
                    if ($location) {
                        $q->where(function($sq) use ($location) {
                            $sq->whereExists(function ($ssq) use ($location) {
                                $ssq->select(\DB::raw(1))
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
                
                // ── Warehouse stock statistics
                // Filter by variants that have been received or transferred into this branch context
                $warehouseStockItemsQuery = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->whereHas('stockLocations', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'warehouse')->where('quantity', '>', 0);
                });

                if ($location) {
                    // Filter variants that have some activity in this branch (receipts or orders)
                    $warehouseStockItemsQuery->where(function($q) use ($location) {
                        $q->whereExists(function($sq) use ($location) {
                            $sq->select(\DB::raw(1))
                               ->from('stock_receipts')
                               ->join('staff', 'stock_receipts.received_by', '=', 'staff.id')
                               ->whereColumn('stock_receipts.product_variant_id', 'product_variants.id')
                               ->where('staff.location_branch', $location);
                        })->orWhereExists(function($sq) use ($location) {
                            $sq->select(\DB::raw(1))
                               ->from('order_items')
                               ->join('orders', 'order_items.order_id', '=', 'orders.id')
                               ->join('staff', 'orders.waiter_id', '=', 'staff.id')
                               ->whereColumn('order_items.product_variant_id', 'product_variants.id')
                               ->where('staff.location_branch', $location);
                        });
                    });
                }
                $warehouseStockItems = $warehouseStockItemsQuery->count();

                $counterStockItemsQuery = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->whereHas('stockLocations', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'counter')->where('quantity', '>', 0);
                });

                if ($location) {
                    $counterStockItemsQuery->where(function($q) use ($location) {
                        $q->whereExists(function($sq) use ($location) {
                            $sq->select(\DB::raw(1))
                               ->from('stock_receipts')
                               ->join('staff', 'stock_receipts.received_by', '=', 'staff.id')
                               ->whereColumn('stock_receipts.product_variant_id', 'product_variants.id')
                               ->where('staff.location_branch', $location);
                        })->orWhereExists(function($sq) use ($location) {
                            $sq->select(\DB::raw(1))
                               ->from('order_items')
                               ->join('orders', 'order_items.order_id', '=', 'orders.id')
                               ->join('staff', 'orders.waiter_id', '=', 'staff.id')
                               ->whereColumn('order_items.product_variant_id', 'product_variants.id')
                               ->where('staff.location_branch', $location);
                        });
                    });
                }
                $counterStockItems = $counterStockItemsQuery->count();

                $lowStockThreshold = \App\Models\SystemSetting::get('low_stock_threshold_' . $ownerId, 10);
                $lowStockListQuery = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                }]);

                if ($location) {
                    $lowStockListQuery->where(function($q) use ($location) {
                        $q->whereExists(function($sq) use ($location) {
                            $sq->select(\DB::raw(1))
                               ->from('stock_receipts')
                               ->join('staff', 'stock_receipts.received_by', '=', 'staff.id')
                               ->whereColumn('stock_receipts.product_variant_id', 'product_variants.id')
                               ->where('staff.location_branch', $location);
                        });
                    });
                }

                $lowStockList = $lowStockListQuery->get()
                ->filter(function($variant) use ($lowStockThreshold) {
                    $warehouseQty = optional($variant->stockLocations->where('location', 'warehouse')->first())->quantity ?? 0;
                    $counterQty   = optional($variant->stockLocations->where('location', 'counter')->first())->quantity ?? 0;
                    $totalQty = $warehouseQty + $counterQty;
                    return $totalQty > 0 && $totalQty < $lowStockThreshold;
                })
                ->take(10);

                // ── Category Distribution (this month)
                $categoryDistribution = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $location) {
                    $q->where('user_id', $ownerId)
                      ->where('payment_status', 'paid')
                      ->whereMonth('created_at', now()->month);
                    if ($location) {
                        $q->where(function($sq) use ($location) {
                            $sq->whereExists(function ($ssq) use ($location) {
                                $ssq->select(\DB::raw(1))
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

                // ── Monthly Targets Progress
                $monthlyTargets = \App\Models\SalesTarget::where('user_id', $ownerId)
                    ->where('month', now()->month)
                    ->where('year', now()->year)
                    ->get()
                    ->keyBy('target_type');
                
                $barMonthlyTarget = $monthlyTargets['monthly_bar']->target_amount ?? 0;
                $foodMonthlyTarget = $monthlyTargets['monthly_food']->target_amount ?? 0;
                
                $barTargetProgress = $barMonthlyTarget > 0 ? min(100, round(($monthRevenue / $barMonthlyTarget) * 100)) : 0;
                // Note: food actual needs to be calculated if not already. 
                // For now, let's use a combined progress or show separately if possible.
                // Re-calculating food actual for dashboard
                $foodMonthRevenue = \App\Models\KitchenOrderItem::whereHas('order', function($q) use ($ownerId, $location) {
                        $q->where('user_id', $ownerId)
                          ->where('status', 'served')
                          ->whereMonth('created_at', now()->month);
                        // Apply location filters... (simulated for now as KitchenOrderItem usually tied to orders)
                    })->sum('total_price');
                $foodTargetProgress = $foodMonthlyTarget > 0 ? min(100, round(($foodMonthRevenue / $foodMonthlyTarget) * 100)) : 0;

                // ── Master Sheet Financials (Manager Context)
                $monthProfit = \App\Models\DailyCashLedger::where('user_id', $ownerId)
                    ->whereMonth('ledger_date', now()->month)
                    ->whereYear('ledger_date', now()->year)
                    ->where('status', 'closed')
                    ->sum('profit_submitted_to_boss');

                $masterSheetTrend = \App\Models\DailyCashLedger::where('user_id', $ownerId)
                    ->where('ledger_date', '>=', now()->subDays(6)->startOfDay())
                    ->orderBy('ledger_date')
                    ->get();

                return view('dashboard.manager', compact(
                    'staff', 'owner',
                    'todayRevenue', 'monthRevenue', 'todayOrders', 'pendingOrders',
                    'pendingTransfers', 'approvedTransfers', 'completedTransfersToday',
                    'totalTransferSalesValue', 'monthlyPurchaseCost',
                    'recentReceipts', 'recentTransfers',
                    'revenueTrend', 'topProducts',
                    'warehouseStockItems', 'counterStockItems',
                    'lowStockList', 'categoryDistribution',
                    'barMonthlyTarget', 'foodMonthlyTarget', 'barTargetProgress', 'foodTargetProgress', 'foodMonthRevenue',
                    'monthProfit', 'masterSheetTrend'
                ));
            }

            // Stock Keeper and other roles
            if ($roleName === 'stock keeper' || $roleName === 'stockkeeper' || ($staff->role && $staff->role->hasPermission('inventory', 'view'))) {
                // Warehouse stock statistics
                $statistics['warehouseStockItems'] = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->whereHas('stockLocations', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'warehouse')->where('quantity', '>', 0);
                })
                ->count();

                $statistics['counterStockItems'] = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->whereHas('stockLocations', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'counter')->where('quantity', '>', 0);
                })
                ->count();

                $statistics['pendingTransfers'] = \App\Models\StockTransfer::where('user_id', $ownerId)
                    ->where('status', 'pending')->count();

                $lowStockThreshold    = \App\Models\SystemSetting::get('low_stock_threshold_' . $ownerId, 10);
                $criticalStockThreshold = \App\Models\SystemSetting::get('critical_stock_threshold_' . $ownerId, 5);

                $lowStockVariants = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                }])
                ->get()
                ->filter(function($variant) use ($lowStockThreshold) {
                    $warehouseQty = optional($variant->stockLocations->where('location', 'warehouse')->first())->quantity ?? 0;
                    $counterQty   = optional($variant->stockLocations->where('location', 'counter')->first())->quantity ?? 0;
                    $totalQty = $warehouseQty + $counterQty;
                    return $totalQty > 0 && $totalQty < $lowStockThreshold;
                });

                $statistics['lowStockItems'] = $lowStockVariants->count();
                $statistics['lowStockItemsList'] = $lowStockVariants->take(10)->map(function($variant) use ($criticalStockThreshold) {
                    $warehouseQty = optional($variant->stockLocations->where('location', 'warehouse')->first())->quantity ?? 0;
                    $counterQty   = optional($variant->stockLocations->where('location', 'counter')->first())->quantity ?? 0;
                    return [
                        'id'           => $variant->id,
                        'product_name' => $variant->product->name,
                        'variant'      => $variant->measurement,
                        'warehouse_qty'=> $warehouseQty,
                        'counter_qty'  => $counterQty,
                        'total_qty'    => $warehouseQty + $counterQty,
                        'is_critical'  => ($warehouseQty + $counterQty) < $criticalStockThreshold,
                    ];
                });
            }

            return view('dashboard.staff', compact('staff', 'owner', 'statistics'));
        }

        
        // Regular users should not have role in URL - redirect to clean URL if role is present
        if ($role) {
            return redirect()->route('dashboard');
        }

        // Regular user authentication
        if (!$isUser) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // IMPORTANT: Verify user email matches authenticated user
        if ($user->email !== request()->input('email') && !session()->has('verified_user')) {
            // This is just a safety check - in normal flow, auth()->user() is already verified
        }
        
        // Redirect admins to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard.index');
        }

        // Check if user needs to complete business configuration
        if (!$user->isConfigured()) {
            $plan = $user->currentPlan();
            $canConfigure = false;

            // If user has a plan
            if ($plan) {
                // Free plan - can configure immediately
                if ($plan->slug === 'free') {
                    $canConfigure = true;
                } 
                // Basic/Pro plans - need verified payment first
                elseif (in_array($plan->slug, ['basic', 'pro'])) {
                    $subscription = $user->activeSubscription;
                    $canConfigure = $subscription && $subscription->status === 'active';
                }
            } else {
                // No plan yet - check if they have a subscription (even if pending)
                $subscription = $user->activeSubscription;
                if ($subscription && $subscription->plan) {
                    // If they have a free plan subscription (trial), allow configuration
                    if ($subscription->plan->slug === 'free') {
                        $canConfigure = true;
                    }
                }
            }

            if ($canConfigure) {
                return redirect()->route('business-configuration.index')
                    ->with('info', 'Please complete your business configuration to get started.');
            }
        }
        
        // Get active subscription
        $subscription = $user->activeSubscription;
        $currentPlan = $subscription ? $subscription->plan : null;
        
        // Check if user has pending subscription (paid plan waiting for payment)
        $pendingSubscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('is_trial', false)
            ->latest()
            ->first();
        
        // Get pending invoices
        $pendingInvoices = Invoice::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'pending_verification', 'paid'])
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Check if trial is expiring soon (within 7 days)
        $trialExpiringSoon = false;
        $trialDaysRemaining = 0;
        if ($subscription && $subscription->is_trial) {
            $trialDaysRemaining = $subscription->getTrialDaysRemaining();
            $trialExpiringSoon = $trialDaysRemaining > 0 && $trialDaysRemaining <= 7;
        }
        
        // Check if trial has expired
        $trialExpired = false;
        if ($subscription && $subscription->is_trial && $subscription->trial_ends_at) {
            $trialExpired = \Carbon\Carbon::now()->greaterThan($subscription->trial_ends_at);
        }
        
        // Get upgrade plans (Basic and Pro)
        $upgradePlans = \App\Models\Plan::where('slug', '!=', 'free')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        // Get pending cash handovers
        $pendingHandovers = \App\Models\FinancialHandover::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with('accountant')
            ->orderBy('handover_date', 'desc')
            ->get();
        
        return view('dashboard.index', compact(
            'subscription', 
            'currentPlan', 
            'pendingInvoices',
            'trialExpiringSoon',
            'trialDaysRemaining',
            'trialExpired',
            'upgradePlans',
            'pendingSubscription',
            'pendingHandovers'
        ));
    }

    /**
     * Switch active location/branch context
     */
    public function switchLocation(Request $request)
    {
        $location = $request->input('active_location');
        
        if ($location === 'all') {
            session()->forget('active_location');
        } else {
            session(['active_location' => $location]);
        }
        
        return back()->with('success', 'Location switched to: ' . ($location === 'all' ? 'All Locations' : $location));
    }
}
