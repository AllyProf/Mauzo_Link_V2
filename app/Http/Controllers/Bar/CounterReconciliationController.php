<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\Staff;
use App\Models\FinancialHandover;
use App\Models\OrderPayment;
use App\Models\WaiterDailyReconciliation;
use App\Models\WaiterNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CounterReconciliationController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Display reconciliation page with all waiters
     */
    public function reconciliation(Request $request)
    {
        // Allow counter staff, accountants, and anyone with bar_orders view permission
        $currentStaff = $this->getCurrentStaff();
        $roleSlug = strtolower(trim($currentStaff->role->slug ?? ''));
        $isCounterOrAccountant = in_array($roleSlug, ['counter', 'accountant']);

        if (!$isCounterOrAccountant && !$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view reconciliations.');
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', now()->format('Y-m-d'));

        // Check if current user is accountant (should see all orders across all owners)
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Get location from session (branch switcher)
        $location = session('active_location');

        // Get waiters, or anyone who placed an order today, or has a reconciliation today
        $waitersQuery = Staff::where('is_active', true)
            ->where(function ($query) use ($date, $location) {
                // Role check
                $query->whereHas('role', function ($q) {
                    $q->where('slug', 'waiter');
                })
                // OR orders today check
                ->orWhereHas('orders', function ($q) use ($date, $location) {
                    $q->whereDate('created_at', $date);
                    if ($location && $location !== 'all') {
                        $q->whereHas('table', function ($sq) use ($location) {
                            $sq->where('location', $location);
                        });
                    }
                })
                // OR daily reconciliations check
                ->orWhereHas('dailyReconciliations', function ($q) use ($date) {
                    $q->where('reconciliation_date', $date)
                      ->where('reconciliation_type', 'bar');
                });
            })
            ->when($location && $location !== 'all', function($q) use ($location) {
                $q->where('location_branch', $location);
            });
        
        // If not accountant, filter by owner
        if (!$isAccountant) {
            $waitersQuery->where('user_id', $ownerId);
        }
        
        $waiters = $waitersQuery
            ->with(['dailyReconciliations' => function($q) use ($date) {
                $q->where('reconciliation_date', $date)
                  ->where('reconciliation_type', 'bar'); // Only get bar reconciliations
            }])
            ->get()
            ->map(function($waiter) use ($ownerId, $date, $isAccountant, $location) {
                $ordersQuery = BarOrder::query()
                    ->where('waiter_id', $waiter->id)
                    ->when($location && $location !== 'all', function($q) use ($location) {
                        $q->whereHas('table', function($sq) use ($location) {
                            $sq->where('location', $location);
                        });
                    });
                
                // If not accountant, filter by owner
                if (!$isAccountant) {
                    $ordersQuery->where('user_id', $ownerId);
                }
                
                $allOrders = $ordersQuery
                    ->whereDate('created_at', $date)
                    ->with(['items', 'kitchenOrderItems', 'table', 'orderPayments'])
                    ->get();
                
                // Separate bar orders (drinks) from food orders
                // Bar orders: orders that have items (drinks) - may also have food
                // Food-only orders: orders that only have kitchenOrderItems, no items
                $barOrders = $allOrders->filter(function($order) {
                    return $order->items && $order->items->count() > 0;
                });
                
                $foodOnlyOrders = $allOrders->filter(function($order) {
                    return ($order->items->count() === 0) && ($order->kitchenOrderItems && $order->kitchenOrderItems->count() > 0);
                });
                
                // For Counter reconciliation: only count bar orders (drinks)
                // Calculate bar sales from items (drinks) only
                $barSales = $barOrders->sum(function($order) {
                    return $order->items->sum('total_price');
                });
                
                // Calculate food sales from kitchenOrderItems
                $foodSales = $allOrders->sum(function($order) {
                    return $order->kitchenOrderItems ? $order->kitchenOrderItems->sum('total_price') : 0;
                });
                
                // Total sales for counter = bar sales only
                $totalSales = $barSales;
                
                // Count only bar orders (orders with drinks)
                $barOrdersCount = $barOrders->count();
                $foodOrdersCount = $foodOnlyOrders->count();
                
                // Check for unpaid served bar orders
                $unpaidBarOrders = $barOrders->filter(function($order) {
                    return $order->status === 'served' && $order->payment_status !== 'paid';
                });
                $hasUnpaidOrders = $unpaidBarOrders->count() > 0;
                
                // Calculate total paid amount (only orders that have been reconciled/submitted)
                $totalPaidAmount = $barOrders->filter(function($order) {
                    return $order->status === 'served' && $order->payment_status === 'paid';
                })->sum(function($order) {
                    return $order->items->sum('total_price');
                });

                // Payment collection from bar orders only (Avoiding double counting)
                $cashCollected = 0;
                $mobileMoneyCollected = 0;
                
                foreach ($barOrders as $order) {
                    if ($order->orderPayments->count() > 0) {
                        // Use OrderPayments if they exist
                        $cashCollected += $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                        // Sum everything that is NOT cash as digital/mobile money
                        $mobileMoneyCollected += $order->orderPayments->where('payment_method', '!=', 'cash')->sum('amount');
                    } else {
                        // Fallback to order fields
                        if ($order->payment_method === 'cash') {
                            $cashCollected += $order->paid_amount;
                        } else {
                            // Any other payment method (mobile_money, bank_transfer, etc.)
                            $mobileMoneyCollected += $order->paid_amount;
                        }
                    }
                }
                
                // Detailed platform breakdown for the waiter
                $waiterPlatformTotals = [];
                foreach ($barOrders as $order) {
                    foreach ($order->orderPayments as $payment) {
                        if ($payment->payment_method === 'cash') continue;
                        
                        $provider = strtolower(trim($payment->mobile_money_number ?? 'mobile'));
                        $label = 'MOBILE MONEY';
                        if (str_contains($provider, 'm-pesa') || str_contains($provider, 'mpesa')) { $label = 'M-PESA'; }
                        elseif (str_contains($provider, 'mixx')) { $label = 'MIXX BY YAS'; }
                        elseif (str_contains($provider, 'halo')) { $label = 'HALOPESA'; }
                        elseif (str_contains($provider, 'tigo')) { $label = 'TIGO PESA'; }
                        elseif (str_contains($provider, 'airtel')) { $label = 'AIRTEL MONEY'; }
                        elseif (str_contains($provider, 'nmb')) { $label = 'NMB BANK'; }
                        elseif (str_contains($provider, 'crdb')) { $label = 'CRDB BANK'; }
                        elseif (str_contains($provider, 'kcb')) { $label = 'KCB BANK'; }
                        
                        $waiterPlatformTotals[$label] = ($waiterPlatformTotals[$label] ?? 0) + $payment->amount;
                    }
                }

                
                // Re-calculate Total Recorded to match the above logic
                $totalRecordedAmount = $cashCollected + $mobileMoneyCollected;
                
                $reconciliation = $waiter->dailyReconciliations->first();
                
                // Submitted amount: use reconciliation if exists, otherwise 0 (not yet submitted)
                // Don't use totalPaidAmount here - that would show as submitted before reconciliation
                $submittedAmount = $reconciliation ? $reconciliation->submitted_amount : 0;
                
                // Calculate difference: 
                // If submitted, use submitted - total. Else use recorded - total.
                $difference = ($submittedAmount > 0 || $reconciliation) 
                              ? ($submittedAmount - $totalSales) 
                              : ($totalRecordedAmount - $totalSales);
                
                // Determine status intelligently
                $status = 'pending';
                if ($reconciliation) {
                    // If reconciliation exists, use its status
                    $status = $reconciliation->status;
                } else {
                    // No reconciliation record - determine status based on payment
                    if ($hasUnpaidOrders) {
                        $status = 'pending'; // Still has unpaid orders
                    } else if ($totalPaidAmount > 0 && abs($difference) < 0.01) {
                        $status = 'paid'; // All orders paid and amounts match
                    } else if ($totalPaidAmount > 0) {
                        $status = 'partial'; // Some orders paid but amounts don't match
                    }
                }
                
                // Final amounts for the UI: Use reconciliation record if it exists
                $finalCash = $reconciliation ? $reconciliation->cash_collected : $cashCollected;
                $finalDigital = $reconciliation ? $reconciliation->mobile_money_collected : $mobileMoneyCollected;

                return [
                    'waiter' => $waiter,
                    'total_sales' => $totalSales, // Bar sales only
                    'bar_sales' => $barSales,
                    'food_sales' => $foodSales,
                    'total_orders' => $barOrdersCount, // Bar orders count only
                    'bar_orders_count' => $barOrdersCount,
                    'food_orders_count' => $foodOrdersCount,
                    'has_unpaid_orders' => $hasUnpaidOrders,
                    'cash_collected' => $finalCash,
                    'mobile_money_collected' => $finalDigital,
                    'recorded_cash' => $cashCollected,
                    'recorded_digital' => $mobileMoneyCollected,
                    'expected_amount' => $totalSales, // Expected = bar sales only
                    'recorded_amount' => $totalRecordedAmount, // Amount recorded by waiter (from OrderPayments)
                    'submitted_amount' => $submittedAmount, // Amount submitted/reconciled by counter
                    'difference' => $difference, // Always calculate difference
                    'status' => $status,
                    'orders' => $barOrders, // Only bar orders
                    'reconciliation' => $reconciliation,
                    'platform_totals' => $waiterPlatformTotals
                ];
            })
            ->filter(function($data) {
                return $data['total_orders'] > 0; // Only show waiters with orders
            })
            ->sortByDesc('total_sales')
            ->values();

        // Get an active accountant to handover to
        $accountant = Staff::where('user_id', $ownerId)
            ->whereHas('role', function($q) {
                $q->where('slug', 'accountant');
            })
            ->where('is_active', true)
            ->first();

        // Check if there is already a handover today
        $todayHandover = null;
        if ($currentStaff) {
            $todayHandover = FinancialHandover::where('user_id', $ownerId)
                ->where('accountant_id', $currentStaff->id) // Current Counter
                ->whereDate('handover_date', $date)
                ->where('handover_type', 'staff_to_accountant')
                ->first();
        }

        $expectedBreakdowns = [
            'cash_amount' => 0,
            'mpesa_amount' => 0,
            'mixx_amount' => 0,
            'halopesa_amount' => 0,
            'tigo_pesa_amount' => 0,
            'airtel_money_amount' => 0,
            'nmb_amount' => 0,
            'crdb_amount' => 0,
            'kcb_amount' => 0,
        ];

        foreach ($waiters as $data) {
            foreach ($data['orders'] as $order) {
                // Determine payments to iterate over
                if ($order->orderPayments && $order->orderPayments->count() > 0) {
                    $payments = $order->orderPayments;
                } else {
                    // mock orderPayment interface using order itself
                    if ($order->payment_status === 'paid' && $order->paid_amount > 0) {
                        $payments = [ (object)[
                            'payment_method' => $order->payment_method,
                            'mobile_money_number' => $order->mobile_money_number,
                            'amount' => $order->paid_amount
                        ]];
                    } else {
                        $payments = [];
                    }
                }

                foreach ($payments as $payment) {
                    $amount = $payment->amount;
                    if ($payment->payment_method === 'cash') {
                        $expectedBreakdowns['cash_amount'] += $amount;
                    } else {
                        $provider = strtolower(trim($payment->mobile_money_number ?? ''));
                        if (str_contains($provider, 'm-pesa') || str_contains($provider, 'mpesa')) {
                            $expectedBreakdowns['mpesa_amount'] += $amount;
                        } elseif (str_contains($provider, 'mixx')) {
                            $expectedBreakdowns['mixx_amount'] += $amount;
                        } elseif (str_contains($provider, 'halo')) {
                            $expectedBreakdowns['halopesa_amount'] += $amount;
                        } elseif (str_contains($provider, 'tigo')) {
                            $expectedBreakdowns['tigo_pesa_amount'] += $amount;
                        } elseif (str_contains($provider, 'airtel')) {
                            $expectedBreakdowns['airtel_money_amount'] += $amount;
                        } elseif (str_contains($provider, 'nmb')) {
                            $expectedBreakdowns['nmb_amount'] += $amount;
                        } elseif (str_contains($provider, 'crdb')) {
                            $expectedBreakdowns['crdb_amount'] += $amount;
                        } elseif (str_contains($provider, 'kcb')) {
                            $expectedBreakdowns['kcb_amount'] += $amount;
                        } else {
                            // If somehow generic mobile money or bank without explicit provider
                            if (str_contains($payment->payment_method, 'bank') || $payment->payment_method === 'card') {
                                // Defaulting unspecified banks to NMB to prevent loss (could adjust as needed)
                                $expectedBreakdowns['nmb_amount'] += $amount;
                            } else {
                                // default generic M-PESA
                                $expectedBreakdowns['mpesa_amount'] += $amount;
                            }
                        }
                    }
                }
            }
        }

        return view('bar.counter.reconciliation', compact('waiters', 'date', 'accountant', 'todayHandover', 'expectedBreakdowns'));
    }

    /**
     * Verify a waiter's reconciliation
     */
    public function verifyReconciliation(Request $request, WaiterDailyReconciliation $reconciliation)
    {
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to verify reconciliations.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        // Verify reconciliation belongs to owner
        if ($reconciliation->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reconciliation->update([
            'status' => 'verified',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reconciliation verified successfully.',
            'reconciliation' => $reconciliation
        ]);
    }

    /**
     * Mark all orders as paid for a waiter after reconciliation verification
     */
    public function markAllOrdersPaid(Request $request)
    {
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'You do not have permission to mark orders as paid.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        $validated = $request->validate([
            'waiter_id' => 'required|exists:staff,id',
            'date' => 'required|date',
            'submitted_amount' => 'nullable|numeric|min:0',
        ]);

        // Check if current user is accountant
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Verify waiter belongs to owner (unless accountant)
        $waiterQuery = Staff::where('id', $validated['waiter_id']);
        if (!$isAccountant) {
            $waiterQuery->where('user_id', $ownerId);
        }
        $waiter = $waiterQuery->first();

        if (!$waiter) {
            return response()->json(['error' => 'Waiter not found'], 404);
        }

        $location = session('active_location');

        // Get all served bar orders (with drinks) for this waiter on this date that are not yet paid
        // Counter only marks bar orders as paid, not food orders
        $ordersQuery = BarOrder::query()
            ->where('waiter_id', $waiter->id)
            ->when($location && $location !== 'all', function($q) use ($location) {
                $q->whereHas('table', function($sq) use ($location) {
                    $sq->where('location', $location);
                });
            });
        
        // If not accountant, filter by owner
        if (!$isAccountant) {
            $ordersQuery->where('user_id', $ownerId);
        }
        
        $orders = $ordersQuery
            ->whereDate('created_at', $validated['date'])
            ->where('status', 'served')
            ->where('payment_status', '!=', 'paid')
            ->whereHas('items') // Only orders with drinks (bar items)
            ->get();

        // Only error out if we have NO unpaid orders AND no submitted_amount provided
        if ($orders->isEmpty() && !isset($validated['submitted_amount'])) {
            return response()->json([
                'success' => false,
                'error' => 'No unpaid served orders found for this waiter on this date.'
            ], 400);
        }

        // Calculate expected amount (total bar sales for this waiter on this date)
        // This includes both paid and unpaid orders
        $expectedOrdersQuery = BarOrder::query()
            ->where('waiter_id', $waiter->id);
        
        // If not accountant, filter by owner
        if (!$isAccountant) {
            $expectedOrdersQuery->where('user_id', $ownerId);
        }
        
        $expectedAmount = $expectedOrdersQuery
            ->whereDate('created_at', $validated['date'])
            ->where('status', 'served')
            ->whereHas('items') // Only bar orders
            ->with('items')
            ->get()
            ->sum(function($order) {
                // Only sum the bar items (drinks) amount
                return $order->items->sum('total_price');
            });

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            $updatedCount = 0;

            foreach ($orders as $order) {
                $order->payment_status = 'paid';
                $order->paid_amount = $order->total_amount;
                $order->paid_by_waiter_id = $waiter->id;
                $order->save();
                
                $totalAmount += $order->total_amount;
                $updatedCount++;
            }

            DB::commit();

            \Log::info('Bulk mark orders as paid', [
                'waiter_id' => $waiter->id,
                'date' => $validated['date'],
                'orders_count' => $updatedCount,
                'total_amount' => $totalAmount
            ]);

            // Check if reconciliation already exists
            $existingReconciliation = \App\Models\WaiterDailyReconciliation::where('user_id', $ownerId)
                ->where('waiter_id', $waiter->id)
                ->where('reconciliation_date', $validated['date'])
                ->first();
            
            $previousSubmittedAmount = $existingReconciliation ? $existingReconciliation->submitted_amount : 0;
            
            // Use submitted_amount if provided, otherwise calculate from OrderPayments (recorded payments)
            if (isset($validated['submitted_amount'])) {
                // If there's already a submitted amount, add the new amount to it
                $newSubmittedAmount = $validated['submitted_amount'];
                $submittedAmount = $previousSubmittedAmount + $newSubmittedAmount;
            } else {
                // Calculate submitted amount from OrderPayments (what waiters have recorded)
                $allOrdersWithPaymentsQuery = BarOrder::query()
                    ->where('waiter_id', $waiter->id)
                    ->whereDate('created_at', $validated['date'])
                    ->where('status', 'served')
                    ->whereHas('items') // Only bar orders
                    ->whereHas('orderPayments') // Must have recorded payments
                    ->with(['items', 'orderPayments']);
                
                // If not accountant, filter by owner
                if (!$isAccountant) {
                    $allOrdersWithPaymentsQuery->where('user_id', $ownerId);
                }
                
                $calculatedSubmittedAmount = $allOrdersWithPaymentsQuery
                    ->get()
                    ->sum(function($order) {
                        // Sum all OrderPayments (recorded payments) for this order
                        return $order->orderPayments->sum('amount');
                    });
                
                // Add to previous submitted amount if exists
                $submittedAmount = $previousSubmittedAmount + $calculatedSubmittedAmount;
            }
            
            // Calculate difference
            $difference = $submittedAmount - $expectedAmount;
            
            // Get bar orders for cash/mobile money calculation
            $barOrdersQuery = BarOrder::query()
                ->where('waiter_id', $waiter->id)
                ->whereDate('created_at', $validated['date'])
                ->where('status', 'served')
                ->whereHas('items') // Only bar orders
                ->with(['items', 'orderPayments']);
            
            if (!$isAccountant) {
                $barOrdersQuery->where('user_id', $ownerId);
            }
            $barOrders = $barOrdersQuery->get();
            
            // Calculate recorded platform breakdown from orders
            $waiterPlatformTotals = [];
            foreach ($barOrders as $order) {
                if ($order->orderPayments->count() > 0) {
                    foreach ($order->orderPayments as $payment) {
                        $pKey = ($payment->payment_method === 'cash') ? 'cash' : strtolower(trim(str_replace(' ', '_', $payment->mobile_money_number ?? 'mobile')));
                        $waiterPlatformTotals[$pKey] = ($waiterPlatformTotals[$pKey] ?? 0) + $payment->amount;
                    }
                } else {
                    $pKey = ($order->payment_method === 'cash') ? 'cash' : strtolower(trim(str_replace(' ', '_', $order->mobile_money_number ?? 'mobile')));
                    $waiterPlatformTotals[$pKey] = ($waiterPlatformTotals[$pKey] ?? 0) + $order->paid_amount;
                }
            }

            $breakdown = $request->input('breakdown', []);
            $submittedCash = $breakdown['cash'] ?? 0;
            $submittedDigital = 0;
            foreach ($breakdown as $platform => $amt) {
                if ($platform !== 'cash') {
                    $submittedDigital += $amt;
                }
            }

            // Create or update bar-specific reconciliation record
            $reconciliation = \App\Models\WaiterDailyReconciliation::updateOrCreate(
                [
                    'user_id' => $ownerId,
                    'waiter_id' => $waiter->id,
                    'reconciliation_date' => $validated['date'],
                    'reconciliation_type' => 'bar', // Bar-specific reconciliation
                ],
                [
                    'expected_amount' => $expectedAmount,
                    'submitted_amount' => $submittedAmount,
                    'cash_collected' => $submittedCash,
                    'mobile_money_collected' => $submittedDigital,
                    'difference' => $difference,
                    'status' => abs($difference) < 0.01 ? 'reconciled' : 'partial',
                    'submitted_at' => now(),
                    'notes' => json_encode([
                        'submitted_breakdown' => $breakdown,
                        'recorded_breakdown' => $waiterPlatformTotals
                    ]),
                ]
            );

            // Create notification for waiter
            try {
                WaiterNotification::create([
                    'waiter_id' => $waiter->id,
                    'type' => 'payment_recorded',
                    'title' => 'Bar Orders Marked as Paid',
                    'message' => "Counter has marked {$updatedCount} bar order(s) as paid for " . \Carbon\Carbon::parse($validated['date'])->format('M d, Y') . ". Total amount: TSh " . number_format($totalAmount, 0),
                    'data' => [
                        'date' => $validated['date'],
                        'orders_count' => $updatedCount,
                        'total_amount' => $totalAmount,
                        'order_type' => 'bar',
                        'marked_by' => 'counter',
                    ],
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to create notification', [
                    'waiter_id' => $waiter->id,
                    'error' => $e->getMessage()
                ]);
            }

            $message = "Successfully marked {$updatedCount} order(s) as paid.";
            if ($submittedAmount < $expectedAmount) {
                $message .= " Submitted amount: TSh " . number_format($submittedAmount, 0) . " (Expected: TSh " . number_format($expectedAmount, 0) . ")";
            } else {
                $message .= " Total: TSh " . number_format($totalAmount, 0);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'orders_count' => $updatedCount,
                'total_amount' => $totalAmount,
                'submitted_amount' => $submittedAmount,
                'expected_amount' => $expectedAmount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to mark all orders as paid', [
                'waiter_id' => $waiter->id,
                'date' => $validated['date'],
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to mark orders as paid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get waiter's orders for a specific date (AJAX)
     */
    public function getWaiterOrders(Request $request, Staff $waiter)
    {
        if (!$this->hasPermission('bar_orders', 'view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', now()->format('Y-m-d'));

        // Check if current user is accountant
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Verify waiter belongs to owner (unless accountant)
        if (!$isAccountant && $waiter->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Return all orders (both bar and food) for counter reconciliation view
        $ordersQuery = BarOrder::query()
            ->where('waiter_id', $waiter->id);
        
        // If not accountant, filter by owner
        if (!$isAccountant) {
            $ordersQuery->where('user_id', $ownerId);
        }
        
        $orders = $ordersQuery
            ->whereDate('created_at', $date)
            ->with(['items.productVariant.product', 'kitchenOrderItems', 'table', 'orderPayments', 'paidByWaiter'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Store financial handover to accountant
     */
    public function storeHandover(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $staff = $this->getCurrentStaff();
        $date = $request->input('date', date('Y-m-d'));
        
        $request->validate([
            'cash_amount' => 'required|numeric|min:0',
            'mpesa_amount' => 'nullable|numeric|min:0',
            'nmb_amount' => 'nullable|numeric|min:0',
            'kcb_amount' => 'nullable|numeric|min:0',
            'crdb_amount' => 'nullable|numeric|min:0',
            'mixx_amount' => 'nullable|numeric|min:0',
            'tigo_pesa_amount' => 'nullable|numeric|min:0',
            'airtel_money_amount' => 'nullable|numeric|min:0',
            'halopesa_amount' => 'nullable|numeric|min:0',
        ]);

        // Calculate total amount
        $breakdown = [
            'cash' => $request->input('cash_amount', 0),
            'mpesa' => $request->input('mpesa_amount', 0),
            'nmb' => $request->input('nmb_amount', 0),
            'kcb' => $request->input('kcb_amount', 0),
            'crdb' => $request->input('crdb_amount', 0),
            'mixx' => $request->input('mixx_amount', 0),
            'tigo_pesa' => $request->input('tigo_pesa_amount', 0),
            'airtel_money' => $request->input('airtel_money_amount', 0),
            'halopesa' => $request->input('halopesa_amount', 0),
        ];
        
        $totalAmount = array_sum($breakdown);

        // Check if already exists
        $existing = FinancialHandover::where('user_id', $ownerId)
            ->where('accountant_id', $staff->id)
            ->whereDate('handover_date', $date)
            ->where('handover_type', 'staff_to_accountant')
            ->first();

        if ($existing) {
            return back()->with('error', 'Handover for this date already exists.');
        }

        // Find an active accountant for the owner to be the recipient
        $accountant = Staff::where('user_id', $ownerId)
            ->whereHas('role', function($q) {
                $q->where('slug', 'accountant');
            })
            ->where('is_active', true)
            ->first();

        $handover = FinancialHandover::create([
            'user_id' => $ownerId,
            'accountant_id' => $staff->id,
            'handover_type' => 'staff_to_accountant',
            'recipient_id' => $accountant ? $accountant->id : null,
            'department' => 'bar',
            'amount' => $totalAmount,
            'payment_breakdown' => $breakdown,
            'handover_date' => $date,
            'status' => 'pending',
            'notes' => $request->notes
        ]);

        // No longer auto-reconciling here.
        // The Counter Staff MUST explicitly reconcile each waiter in the table 
        // BEFORE submitting the final handover. This ensures all shortages, 
        // surpluses, and paid/unpaid statuses are accurately recorded and 
        // not overwritten by automatic order matching.

        // Send SMS notification to accountant
        try {
            $smsService = new \App\Services\HandoverSmsService();
            $smsService->sendHandoverSubmissionSms($handover, $ownerId);
        } catch (\Exception $e) {
            \Log::error('SMS notification failed for handover: ' . $e->getMessage());
        }

        return back()->with('success', 'Handover mapped and sent to Accountant successful! Awaiting confirmation.');
    }

    /**
     * Reset a reconciliation record (Reopen the staff row)
     */
    public function resetReconciliation(WaiterDailyReconciliation $reconciliation)
    {
        if (!$this->hasPermission('bar_orders', 'edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only allow resetting if not yet verified by accountant
        if ($reconciliation->status === 'verified') {
            return response()->json(['error' => 'Cannot reset a verified reconciliation.'], 400);
        }

        try {
            DB::beginTransaction();
            
            // Delete the reconciliation record
            $reconciliation->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation reset successfully. Row is now reopened.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to reset reconciliation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset the entire handover (Cancel and Reopen the day)
     */
    public function resetHandover(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $date = $request->input('date');
        
        DB::beginTransaction();
        try {
            // 1. Delete the handover
            $deleted = FinancialHandover::where('user_id', $ownerId)
                ->whereDate('handover_date', $date)
                ->where('status', 'pending') // Only pending handovers can be reset
                ->delete();

            if ($deleted) {
                // 2. Revert staff records from 'verified' back to 'reconciled'
                // so they can be individually reset/adjusted.
                WaiterDailyReconciliation::where('user_id', $ownerId)
                    ->where('reconciliation_date', $date)
                    ->where('status', 'verified')
                    ->update(['status' => 'reconciled']);
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
