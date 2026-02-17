<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\Staff;
use App\Models\WaiterDailyReconciliation;
use App\Models\WaiterNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CounterReconciliationController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Display reconciliation page with all waiters
     */
    public function reconciliation(Request $request)
    {
        if (!$this->hasPermission('bar_orders', 'view')) {
            abort(403, 'You do not have permission to view reconciliations.');
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', now()->format('Y-m-d'));

        // Check if current user is accountant (should see all orders across all owners)
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Get all waiters with their sales for the date
        $waitersQuery = Staff::query()
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->where('name', 'Waiter');
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
            ->map(function($waiter) use ($ownerId, $date, $isAccountant) {
                $ordersQuery = BarOrder::query()
                    ->where('waiter_id', $waiter->id);
                
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
                
                // Calculate total recorded amount from OrderPayments (recorded by waiters)
                // This shows what waiters have recorded, regardless of reconciliation status
                $totalRecordedAmount = $barOrders->filter(function($order) {
                    return $order->status === 'served' && $order->orderPayments && $order->orderPayments->count() > 0;
                })->sum(function($order) {
                    // Sum all OrderPayments for this order (recorded payments)
                    return $order->orderPayments->sum('amount');
                });
                
                // Calculate total paid amount (only orders that have been reconciled/submitted)
                $totalPaidAmount = $barOrders->filter(function($order) {
                    return $order->status === 'served' && $order->payment_status === 'paid';
                })->sum(function($order) {
                    // Only sum the bar items (drinks) amount, not the total order amount
                    return $order->items->sum('total_price');
                });
                
                // Payment collection from bar orders only
                $cashCollected = $barOrders->where('payment_method', 'cash')->sum('paid_amount') + 
                               $barOrders->sum(function($order) {
                                   return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                               });
                $mobileMoneyCollected = $barOrders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                                      $barOrders->sum(function($order) {
                                          return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                                      });
                
                $reconciliation = $waiter->dailyReconciliations->first();
                
                // Submitted amount: use reconciliation if exists, otherwise 0 (not yet submitted)
                // Don't use totalPaidAmount here - that would show as submitted before reconciliation
                $submittedAmount = $reconciliation ? $reconciliation->submitted_amount : 0;
                
                // Calculate difference: Submitted - Expected
                $difference = $submittedAmount - $totalSales;
                
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
                
                return [
                    'waiter' => $waiter,
                    'total_sales' => $totalSales, // Bar sales only
                    'bar_sales' => $barSales,
                    'food_sales' => $foodSales,
                    'total_orders' => $barOrdersCount, // Bar orders count only
                    'bar_orders_count' => $barOrdersCount,
                    'food_orders_count' => $foodOrdersCount,
                    'has_unpaid_orders' => $hasUnpaidOrders,
                    'cash_collected' => $cashCollected,
                    'mobile_money_collected' => $mobileMoneyCollected,
                    'expected_amount' => $totalSales, // Expected = bar sales only
                    'recorded_amount' => $totalRecordedAmount, // Amount recorded by waiter (from OrderPayments)
                    'submitted_amount' => $submittedAmount, // Amount submitted/reconciled by counter
                    'difference' => $difference, // Always calculate difference
                    'status' => $status,
                    'orders' => $barOrders, // Only bar orders
                    'reconciliation' => $reconciliation
                ];
            })
            ->filter(function($data) {
                return $data['total_orders'] > 0; // Only show waiters with orders
            })
            ->sortByDesc('total_sales')
            ->values();

        return view('bar.counter.reconciliation', compact('waiters', 'date'));
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

        // Get all served bar orders (with drinks) for this waiter on this date that are not yet paid
        // Counter only marks bar orders as paid, not food orders
        $ordersQuery = BarOrder::query()
            ->where('waiter_id', $waiter->id);
        
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

        if ($orders->isEmpty()) {
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
            
            // Ensure submitted amount doesn't exceed expected amount
            $submittedAmount = min($submittedAmount, $expectedAmount);
            
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
                    'difference' => $difference,
                    'status' => $submittedAmount >= $expectedAmount ? 'submitted' : 'partial',
                    'submitted_at' => now(),
                    'cash_collected' => $barOrders->where('payment_method', 'cash')->sum('paid_amount') + 
                                      $barOrders->sum(function($order) {
                                          return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                                      }),
                    'mobile_money_collected' => $barOrders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                                              $barOrders->sum(function($order) {
                                                  return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                                              }),
                    'total_sales' => $expectedAmount,
                ]
            );

            // Create notification for waiter
            try {
                WaiterNotification::create([
                    'waiter_id' => $waiter->id,
                    'type' => 'payment_recorded',
                    'title' => 'Bar Orders Marked as Paid',
                    'message' => "Counter has marked {$updatedCount} bar order(s) as paid for " . \Carbon\Carbon::parse($validated['date'])->format('M d, Y') . ". Total amount: TSh " . number_format($allPaidOrders, 0),
                    'data' => [
                        'date' => $validated['date'],
                        'orders_count' => $updatedCount,
                        'total_amount' => $allPaidOrders,
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
}
