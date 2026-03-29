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
        // Parse week string e.g., '2026-W13'
        $week = $request->get('week', date('Y-\WW'));
        $year = (int) substr($week, 0, 4);
        $weekNum = (int) substr($week, 6, 2);
        
        $startDateObj = (new \DateTime())->setISODate($year, $weekNum);
        $startDate = $startDateObj->format('Y-m-d 00:00:00');
        $endDateObj = clone $startDateObj;
        $endDateObj->modify('+6 days');
        $endDate = $endDateObj->format('Y-m-d 23:59:59');
        $displayDate = $startDateObj->format('M d') . ' - ' . $endDateObj->format('M d, Y');
        
        // For storing in DB (reconciliation_date is visually a Date column)
        $date = $startDateObj->format('Y-m-d');

        // Get the active shift for the current staff (unless a specific ID is provided)
        $shiftId = $request->get('shift_id');
        if ($shiftId) {
            $activeShift = \App\Models\StaffShift::where('id', $shiftId)
                ->where('user_id', $ownerId)
                ->first();
        } else {
            $activeShift = \App\Models\StaffShift::where('staff_id', $currentStaff->id)
                ->where('status', 'open')
                ->first();
            
            // Sync shift ID for the view (important for modal filtering)
            if ($activeShift) $shiftId = $activeShift->id;

            // If no open shift, check if they JUST finished one today to show its summary
            if (!$activeShift && !$shiftId && $week === date('Y-\WW')) {
                $activeShift = \App\Models\StaffShift::where('staff_id', $currentStaff->id)
                    ->where('status', 'closed')
                    ->whereDate('closed_at', date('Y-m-d'))
                    ->latest()
                    ->first();
                
                if ($activeShift) $shiftId = $activeShift->id;
                $isPostHandoverView = true;
            }
        }

        if ($activeShift) {
            // Override display dates to match the SHIFT timeframe
            $startDate = $activeShift->opened_at->format('Y-m-d H:i:s');
            // If viewing history, use closed_at. If active dashboard, use now().
            $endDate = ($activeShift->closed_at ?: now())->format('Y-m-d H:i:s');
            
            // Format for title display
            if ($activeShift->closed_at) {
                $displayDate = $activeShift->opened_at->format('M d, Y') . ' - ' . $activeShift->closed_at->format('M d, Y');
            } else {
                $displayDate = $activeShift->opened_at->format('M d, Y') . ' (Active Shift)';
            }
            
            // ALSO override the base reconciliation date to match the shift start date
            $date = $activeShift->opened_at->format('Y-m-d');

            if (isset($isPostHandoverView) && $isPostHandoverView) {
                // Keep the week range for HISTORY but set display title to show shift specifically
                $displayDate = $activeShift->opened_at->format('M d, Y') . ' (Recently Closed Shift)';
            }
        } else if ($week === date('Y-\WW')) {
            // Default to TODAY if no shift and viewing the current week
            $date = date('Y-m-d');
        }

        // Check if current user is accountant (should see all orders across all owners)
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';
        $location = session('active_location');

        // 1. Get ALL orders from all waiters within the shift/range
        $allOrdersQuery = BarOrder::query()
            ->with(['items', 'kitchenOrderItems', 'table', 'orderPayments'])
            ->when($location && $location !== 'all', function($q) use ($location) {
                $q->whereHas('table', function($sq) use ($location) {
                    $sq->where('location', $location);
                });
            });

        if (!$isAccountant) {
            $allOrdersQuery->where('user_id', $ownerId);
        }

        if ($activeShift) {
            $openedAt = $activeShift->opened_at;
            $closedAt = $activeShift->closed_at ?: now();
            $allOrdersQuery->whereBetween('created_at', [$openedAt, $closedAt]);
            
            if (!$isAccountant) {
                // Strictly filter by shift ID to avoid picking up orders from other shifts on the same day
                $allOrdersQuery->where('shift_id', $activeShift->id);
            }
        } else {
            $allOrdersQuery->whereBetween('created_at', [$startDate, $endDate]);
            if (!$isAccountant) {
                $allOrdersQuery->where('user_id', $ownerId);
            }
        }

        $allOrders = $allOrdersQuery->get();

        // 2. Group orders by [waiter_id, date]
        $groupedOrders = $allOrders->groupBy(function($order) {
            return $order->waiter_id . '_' . $order->created_at->format('Y-m-d');
        });

        // 3. Build the flattened rows
        $waiters = collect();

        foreach ($groupedOrders as $key => $ordersInGroup) {
            $parts = explode('_', $key);
            $waiterId = $parts[0];
            $rowDate = $parts[1];

            $waiter = Staff::find($waiterId);
            if (!$waiter) continue;

            $barOrders = $ordersInGroup->filter(fn($o) => $o->items->count() > 0);
            $foodSales = $ordersInGroup->sum(fn($o) => $o->kitchenOrderItems ? $o->kitchenOrderItems->sum('total_price') : 0);
            $barSales = $barOrders->sum(fn($o) => $o->items->sum('total_price'));
            $totalSales = $barSales;
            
            $unpaidBarOrders = $barOrders->filter(fn($o) => $o->status === 'served' && $o->payment_status !== 'paid');
            $hasUnpaidOrders = $unpaidBarOrders->count() > 0;
            
            $totalPaidAmount = $barOrders->filter(fn($o) => $o->status === 'served' && $o->payment_status === 'paid')
                ->sum(fn($o) => $o->items->sum('total_price'));

            $cashCollected = 0;
            $mobileMoneyCollected = 0;
            foreach ($barOrders as $order) {
                if ($order->orderPayments->count() > 0) {
                    $cashCollected += $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                    $mobileMoneyCollected += $order->orderPayments->where('payment_method', '!=', 'cash')->sum('amount');
                } else {
                    if ($order->payment_method === 'cash') $cashCollected += $order->paid_amount;
                    else $mobileMoneyCollected += $order->paid_amount;
                }
            }

            $reconciliation = \App\Models\WaiterDailyReconciliation::where('waiter_id', $waiterId)
                ->where('reconciliation_date', $rowDate)
                ->where('reconciliation_type', 'bar')
                ->when($activeShift, fn($q) => $q->where('staff_shift_id', $activeShift->id))
                ->first();

            $waiterPlatformTotals = [];
            foreach ($barOrders as $order) {
                foreach ($order->orderPayments as $payment) {
                    if ($payment->payment_method === 'cash') continue;
                    $provider = strtolower(trim($payment->mobile_money_number ?? ''));
                    $method = strtolower($payment->payment_method ?? '');
                    
                    if (str_contains($provider, 'nmb') || str_contains($method, 'nmb')) { $label = 'NMB BANK'; }
                    elseif (str_contains($provider, 'crdb') || str_contains($method, 'crdb')) { $label = 'CRDB BANK'; }
                    elseif (str_contains($provider, 'kcb') || str_contains($method, 'kcb')) { $label = 'KCB BANK'; }
                    elseif (str_contains($provider, 'nbc') || str_contains($method, 'nbc')) { $label = 'NBC BANK'; }
                    elseif (str_contains($provider, 'm-pesa') || str_contains($provider, 'mpesa') || str_contains($method, 'm-pesa') || str_contains($method, 'mpesa')) { $label = 'M-PESA'; }
                    elseif (str_contains($provider, 'mixx')) { $label = 'MIXX BY YAS'; }
                    elseif (str_contains($provider, 'halo')) { $label = 'HALOPESA'; }
                    elseif (str_contains($provider, 'tigo')) { $label = 'TIGO PESA'; }
                    elseif (str_contains($provider, 't-pesa') || str_contains($provider, 'tpesa') || str_contains($provider, 'ttcl')) { $label = 'T-PESA'; }
                    elseif (str_contains($provider, 'airtel')) { $label = 'AIRTEL MONEY'; }
                    elseif (str_contains($provider, 'visa')) { $label = 'VISA CARD'; }
                    elseif (str_contains($provider, 'mastercard') || str_contains($provider, 'master card')) { $label = 'MASTERCARD'; }
                    elseif (str_contains($provider, 'equity')) { $label = 'EQUITY BANK'; }
                    elseif (str_contains($provider, 'absa')) { $label = 'ABSA BANK'; }
                    elseif (str_contains($provider, 'dtb') || str_contains($provider, 'diamond')) { $label = 'DTB BANK'; }
                    elseif (str_contains($provider, 'exim')) { $label = 'EXIM BANK'; }
                    elseif (str_contains($provider, 'azania')) { $label = 'AZANIA BANK'; }
                    elseif (str_contains($provider, 'stanbic')) { $label = 'STANBIC BANK'; }
                    elseif ($method === 'card' || str_contains($method, 'pos')) { $label = 'BANK CARD'; }
                    elseif (str_contains($method, 'bank') || str_contains($provider, 'bank') || str_contains($provider, 'transfer')) { $label = 'BANK TRANSFER'; }
                    else { $label = 'MOBILE MONEY'; }
                    $waiterPlatformTotals[$label] = ($waiterPlatformTotals[$label] ?? 0) + $payment->amount;
                }
            }

            $totalRecordedAmount = $cashCollected + $mobileMoneyCollected;
            $isFullyPaid = !$hasUnpaidOrders && $barOrders->count() > 0 && ($totalPaidAmount >= $totalSales - 0.01);
            
            if ($isFullyPaid && (!$reconciliation || $reconciliation->status !== 'verified')) {
                if (!$reconciliation) {
                    $reconciliation = new \App\Models\WaiterDailyReconciliation([
                        'user_id' => $ownerId,
                        'waiter_id' => $waiterId,
                        'staff_shift_id' => $activeShift ? $activeShift->id : null,
                        'reconciliation_date' => $rowDate,
                        'reconciliation_type' => 'bar',
                    ]);
                }
                $reconciliation->expected_amount = $totalSales;
                $reconciliation->submitted_amount = $totalRecordedAmount;
                $reconciliation->cash_collected = $cashCollected;
                $reconciliation->mobile_money_collected = $mobileMoneyCollected;
                $reconciliation->difference = 0;
                $reconciliation->status = 'submitted';
                $reconciliation->submitted_at = now();
                $reconciliation->notes = json_encode(['auto_reconciled' => true, 'recorded_breakdown' => $waiterPlatformTotals, 'submitted_breakdown' => $waiterPlatformTotals]);
                $reconciliation->save();
                $submittedAmount = $totalRecordedAmount;
                $difference = 0;
                $status = 'submitted';
            } else {
                $submittedAmount = $reconciliation ? $reconciliation->submitted_amount : 0;
                $difference = $reconciliation ? ($submittedAmount - $totalSales) : ($totalRecordedAmount - $totalSales);
                $status = $reconciliation ? $reconciliation->status : 'pending';
                if (!$reconciliation) {
                    if ($hasUnpaidOrders) $status = 'pending';
                    else if ($totalPaidAmount > 0 && abs($difference) < 0.01) $status = 'paid';
                    else if ($totalPaidAmount > 0) $status = 'partial';
                }
            }

            $waiters->push([
                'waiter' => $waiter,
                'date' => $rowDate,
                'total_sales' => $totalSales,
                'bar_sales' => $barSales,
                'food_sales' => $foodSales,
                'total_orders' => $barOrders->count(),
                'has_unpaid_orders' => $hasUnpaidOrders,
                'cash_collected'          => $cashCollected,
                'mobile_money_collected'  => $mobileMoneyCollected,
                'recorded_cash' => $cashCollected,
                'recorded_digital' => $mobileMoneyCollected,
                'expected_amount' => $totalSales,
                'recorded_amount' => $totalRecordedAmount,
                'submitted_amount' => $submittedAmount,
                'difference' => $difference,
                'status' => $status,
                'orders' => $barOrders,
                'reconciliation' => $reconciliation,
                'platform_totals' => $waiterPlatformTotals
            ]);
        }
        
        $waiters = $waiters->sortByDesc('date')->values()
            ->filter(function($data) {
                return $data['total_orders'] > 0; // Only show waiters with orders
            })
            ->sortByDesc('total_sales')
            ->values();

        // Get an active manager to handover to (accountant as fallback)
        $manager = Staff::where('user_id', $ownerId)
            ->whereHas('role', function($q) {
                $q->where('slug', 'manager');
            })
            ->where('is_active', true)
            ->first();

        if (!$manager) {
            $manager = Staff::where('user_id', $ownerId)
                ->whereHas('role', function($q) {
                    $q->where('slug', 'accountant');
                })
                ->where('is_active', true)
                ->first();
        }

        // Check if there is already a handover for the SELECTED shift or SELECTED date
        $todayHandover = null;
        if ($activeShift) {
            $todayHandover = FinancialHandover::where('staff_shift_id', $activeShift->id)
                ->latest()
                ->first();
        } 
        
        // Alternative date-based lookup (if no shift or multiple shifts on date)
        if (!$todayHandover) {
            $todayHandover = FinancialHandover::where('user_id', $ownerId)
                ->where(function($q) use ($currentStaff) {
                    $q->where('accountant_id', $currentStaff->id)
                      ->orWhere('recipient_id', $currentStaff->id);
                })
                ->whereDate('handover_date', $date)
                ->where('department', 'bar')
                ->latest()
                ->first();
        }

        // PROACTIVE SYNC: If all waiters in this view are verified, make sure the handover record matches
        if ($todayHandover && $todayHandover->status === 'pending' && $waiters->isNotEmpty()) {
            $unverifiedCount = $waiters->filter(fn($w) => $w['status'] !== 'verified')->count();
            if ($unverifiedCount === 0) {
                // All visible waiters are verified — sync the master handover record
                $todayHandover->update([
                    'status' => 'verified',
                    'confirmed_at' => now()
                ]);
            }
        }

        // Determine which shift is most relevant for the 'Print Report' button
        // If an active shift is running, we show the report for THAT shift.
        // Otherwise we show the report for the most recently closed shift.
        $latestClosedShift = $activeShift;
        if (!$latestClosedShift) {
            $latestClosedShift = \App\Models\StaffShift::where('staff_id', $currentStaff->id)
                ->where('status', 'closed')
                ->latest('closed_at')
                ->first();
        }

        $expectedBreakdowns = [
            'cash_amount' => 0,
            'mpesa_amount' => 0,
            'mixx_amount' => 0,
            'halopesa_amount' => 0,
            'tigo_pesa_amount' => 0,
            'tpesa_amount' => 0,
            'airtel_money_amount' => 0,
            'nmb_amount' => 0,
            'crdb_amount' => 0,
            'nbc_amount' => 0,
            'kcb_amount' => 0,
            'azania_amount' => 0,
            'equity_amount' => 0,
            'absa_amount' => 0,
            'dtb_amount' => 0,
            'exim_amount' => 0,
            'stanbic_amount' => 0,
            'bank_card_amount' => 0,
            'bank_transfer_amount' => 0,
            'visa_card_amount' => 0,
            'mastercard_amount' => 0,
        ];

        // When calculating shift totals, only count orders paid by the person WHO OWNED THIS SHIFT
        $shiftStaffId = $activeShift ? $activeShift->staff_id : ($currentStaff ? $currentStaff->id : null);

        foreach ($waiters as $data) {
            foreach (data_get($data, 'orders', []) as $order) {
                // Ensure we only count orders where payment was collected by the shifts' counter person
                if ($order->payment_status !== 'paid' || $order->paid_by_waiter_id != $shiftStaffId) {
                    continue;
                }

                $payments = ($order->orderPayments && $order->orderPayments->count() > 0) 
                    ? $order->orderPayments 
                    : [(object)['payment_method' => $order->payment_method, 'mobile_money_number' => $order->mobile_money_number, 'amount' => $order->paid_amount]];

                foreach ($payments as $payment) {
                    $amount = $payment->amount;
                    if ($payment->payment_method === 'cash') {
                        $expectedBreakdowns['cash_amount'] += $amount;
                    } else {
                        $provider = strtolower(trim($payment->mobile_money_number ?? ''));
                        if (str_contains($provider, 'm-pesa') || str_contains($provider, 'mpesa')) $expectedBreakdowns['mpesa_amount'] += $amount;
                        elseif (str_contains($provider, 'mixx')) $expectedBreakdowns['mixx_amount'] += $amount;
                        elseif (str_contains($provider, 't-pesa') || str_contains($provider, 'tpesa') || str_contains($provider, 'ttcl')) $expectedBreakdowns['tpesa_amount'] += $amount;
                        elseif (str_contains($provider, 'tigo')) $expectedBreakdowns['tigo_pesa_amount'] += $amount;
                        elseif (str_contains($provider, 'halo')) $expectedBreakdowns['halopesa_amount'] += $amount;
                        elseif (str_contains($provider, 'airtel')) $expectedBreakdowns['airtel_money_amount'] += $amount;
                        elseif (str_contains($provider, 'nbc')) $expectedBreakdowns['nbc_amount'] += $amount;
                        elseif (str_contains($provider, 'nmb')) $expectedBreakdowns['nmb_amount'] += $amount;
                        elseif (str_contains($provider, 'crdb')) $expectedBreakdowns['crdb_amount'] += $amount;
                        elseif (str_contains($provider, 'kcb')) $expectedBreakdowns['kcb_amount'] += $amount;
                        elseif (str_contains($provider, 'azania')) $expectedBreakdowns['azania_amount'] += $amount;
                        elseif (str_contains($provider, 'equity')) $expectedBreakdowns['equity_amount'] += $amount;
                        elseif (str_contains($provider, 'absa')) $expectedBreakdowns['absa_amount'] += $amount;
                        elseif (str_contains($provider, 'dtb')) $expectedBreakdowns['dtb_amount'] += $amount;
                        elseif (str_contains($provider, 'exim')) $expectedBreakdowns['exim_amount'] += $amount;
                        elseif (str_contains($provider, 'stanbic')) $expectedBreakdowns['stanbic_amount'] += $amount;
                        elseif (str_contains($provider, 'visa')) $expectedBreakdowns['visa_card_amount'] += $amount;
                        elseif (str_contains($provider, 'master')) $expectedBreakdowns['mastercard_amount'] += $amount;
                        elseif ($payment->payment_method === 'card') $expectedBreakdowns['bank_card_amount'] += $amount;
                        elseif (str_contains($provider, 'bank') || str_contains($payment->payment_method, 'bank') || str_contains($payment->payment_method, 'transfer')) {
                            $expectedBreakdowns['bank_transfer_amount'] += $amount;
                        }
                        else $expectedBreakdowns['mpesa_amount'] += $amount; // Default
                    }
                }
            }
        }

        // --- EXPENSES LOGIC ---
        $shiftExpenses = \App\Models\CounterExpense::where('user_id', $ownerId)
            ->when($activeShift, fn($q) => $q->where('staff_shift_id', $activeShift->id))
            ->when(!$activeShift, fn($q) => $q->whereBetween('expense_date', [$startDate, $endDate]))
            ->get();
            
        $expensesByDate = $shiftExpenses->groupBy(fn($e) => $e->expense_date->format('Y-m-d'));
        
        foreach ($shiftExpenses as $expense) {
            $method = $expense->payment_method ?: 'cash';
            $amount = $expense->amount;
            if ($method === 'cash') $expectedBreakdowns['cash_amount'] -= $amount;
            else {
                $key = $method . '_amount';
                if (isset($expectedBreakdowns[$key])) $expectedBreakdowns[$key] -= $amount;
                else {
                    if (str_contains($method, 'mpesa')) $expectedBreakdowns['mpesa_amount'] -= $amount;
                    elseif (str_contains($method, 'tpesa') || str_contains($method, 't-pesa') || str_contains($method, 'ttcl')) $expectedBreakdowns['tpesa_amount'] -= $amount;
                    elseif (str_contains($method, 'tigo')) $expectedBreakdowns['tigo_pesa_amount'] -= $amount;
                    elseif (str_contains($method, 'airtel')) $expectedBreakdowns['airtel_money_amount'] -= $amount;
                    elseif (str_contains($method, 'halo')) $expectedBreakdowns['halopesa_amount'] -= $amount;
                    elseif (str_contains($method, 'nmb')) $expectedBreakdowns['nmb_amount'] -= $amount;
                    elseif (str_contains($method, 'crdb')) $expectedBreakdowns['crdb_amount'] -= $amount;
                }
            }
        }

        return view('bar.counter.reconciliation', compact(
            'waiters', 'week', 'displayDate', 'date', 'manager', 
            'todayHandover', 'expectedBreakdowns', 'latestClosedShift', 
            'shiftId', 'expensesByDate', 'shiftExpenses'
        ));
    }

    /**
     * Store a daily counter expense
     */
    public function storeExpense(Request $request)
    {
        $staff = $this->getCurrentStaff();
        $ownerId = $this->getOwnerId();
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'expense_date' => 'required|date',
            'payment_method' => 'required|string',
            'shift_id' => 'nullable|exists:staff_shifts,id'
        ]);

        \App\Models\CounterExpense::create([
            'user_id' => $ownerId,
            'staff_id' => $staff->id,
            'staff_shift_id' => $validated['shift_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'expense_date' => $validated['expense_date'],
            'payment_method' => $validated['payment_method']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense recorded successfully.'
        ]);
    }

    /**
     * Delete an expense
     */
    public function destroyExpense($id)
    {
        $staff = $this->getCurrentStaff();
        $ownerId = $this->getOwnerId();
        
        $expense = \App\Models\CounterExpense::where('id', $id)
            ->where('user_id', $ownerId)
            ->firstOrFail();
            
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.'
        ]);
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
            ->whereBetween('created_at', [$validated['date'] . ' 00:00:00', date('Y-m-d 23:59:59', strtotime($validated['date'] . ' +6 days'))])
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
            ->whereBetween('created_at', [$validated['date'] . ' 00:00:00', date('Y-m-d 23:59:59', strtotime($validated['date'] . ' +6 days'))])
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
                    ->whereBetween('created_at', [$validated['date'] . ' 00:00:00', date('Y-m-d 23:59:59', strtotime($validated['date'] . ' +6 days'))])
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
                ->whereBetween('created_at', [$validated['date'] . ' 00:00:00', date('Y-m-d 23:59:59', strtotime($validated['date'] . ' +6 days'))])
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
                        if ($payment->payment_method === 'card' || $payment->payment_method === 'bank_transfer' || $payment->payment_method === 'bank') {
                            $pKey = $payment->payment_method;
                        } elseif ($payment->payment_method === 'cash') {
                            $pKey = 'cash';
                        } else {
                            $pKey = strtolower(trim(str_replace(' ', '_', $payment->mobile_money_number ?? 'mobile')));
                        }
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
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = $currentStaff && strtolower($currentStaff->role->name ?? '') === 'accountant';

        // Verify waiter belongs to owner (unless accountant)
        if (!$isAccountant && $waiter->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $shiftId = $request->get('shift_id');
        $activeShift = null;
        if ($shiftId) {
            $activeShift = \App\Models\StaffShift::find($shiftId);
        } else {
            $activeShift = \App\Models\StaffShift::where('staff_id', $currentStaff->id)
                ->where('status', 'open')
                ->first();
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
            ->when($activeShift && !$isAccountant, function($q) use ($activeShift) {
                $q->where('shift_id', $activeShift->id);
            })
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
            'circulation_money' => 'nullable|numeric|min:0',
            'mpesa_amount' => 'nullable|numeric|min:0',
            'nmb_amount' => 'nullable|numeric|min:0',
            'kcb_amount' => 'nullable|numeric|min:0',
            'crdb_amount' => 'nullable|numeric|min:0',
            'nbc_amount' => 'nullable|numeric|min:0',
            'equity_amount' => 'nullable|numeric|min:0',
            'absa_amount' => 'nullable|numeric|min:0',
            'dtb_amount' => 'nullable|numeric|min:0',
            'exim_amount' => 'nullable|numeric|min:0',
            'azania_amount' => 'nullable|numeric|min:0',
            'visa_amount' => 'nullable|numeric|min:0',
            'mastercard_amount' => 'nullable|numeric|min:0',
            'mixx_amount' => 'nullable|numeric|min:0',
            'tigo_pesa_amount' => 'nullable|numeric|min:0',
            'tpesa_amount' => 'nullable|numeric|min:0',
            'airtel_money_amount' => 'nullable|numeric|min:0',
            'halopesa_amount' => 'nullable|numeric|min:0',
            'stanbic_amount' => 'nullable|numeric|min:0',
            'bank_card_amount' => 'nullable|numeric|min:0',
            'bank_transfer_amount' => 'nullable|numeric|min:0',
            'mobile_money_amount' => 'nullable|numeric|min:0',
        ]);

        // Calculate total amount
        $breakdown = [
            'cash' => $request->input('cash_amount', 0),
            'mpesa' => $request->input('mpesa_amount', 0),
            'nmb' => $request->input('nmb_amount', 0),
            'kcb' => $request->input('kcb_amount', 0),
            'crdb' => $request->input('crdb_amount', 0),
            'nbc' => $request->input('nbc_amount', 0),
            'equity' => $request->input('equity_amount', 0),
            'absa' => $request->input('absa_amount', 0),
            'dtb' => $request->input('dtb_amount', 0),
            'exim' => $request->input('exim_amount', 0),
            'azania' => $request->input('azania_amount', 0),
            'visa' => $request->input('visa_amount', 0),
            'mastercard' => $request->input('mastercard_amount', 0),
            'mixx' => $request->input('mixx_amount', 0),
            'tigo_pesa' => $request->input('tigo_pesa_amount', 0),
            'tpesa' => $request->input('tpesa_amount', 0),
            'airtel_money' => $request->input('airtel_money_amount', 0),
            'halopesa' => $request->input('halopesa_amount', 0),
            'stanbic' => $request->input('stanbic_amount', 0),
            'bank_card' => $request->input('bank_card_amount', 0),
            'bank_transfer' => $request->input('bank_transfer_amount', 0),
            'mobile_money' => $request->input('mobile_money_amount', 0),
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

        // Find an active manager for the owner to be the recipient
        $manager = Staff::where('user_id', $ownerId)
            ->whereHas('role', function($q) {
                $q->where('slug', 'manager');
            })
            ->where('is_active', true)
            ->first();

        // If no manager, try accountant as fallback
        if (!$manager) {
            $manager = Staff::where('user_id', $ownerId)
                ->whereHas('role', function($q) {
                    $q->where('slug', 'accountant');
                })
                ->where('is_active', true)
                ->first();
        }

        // 1. Find the current open shift BEFORE creating handover
        $openShift = \App\Models\StaffShift::where('staff_id', $staff->id)->where('status', 'open')->first();

        // Calculate Profit for this handover
        $profitAmount = \App\Models\OrderItem::whereHas('order', function($q) use ($ownerId, $date, $openShift) {
            $q->where('user_id', $ownerId)
              ->whereDate('created_at', $date)
              ->where('status', 'served');
            
            // Filter by the specific shift to ensure multiple shifts on the same day don't aggregate profits
            if ($openShift) $q->where('shift_id', $openShift->id);
        })
        ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
        ->selectRaw('SUM((order_items.unit_price - COALESCE(product_variants.buying_price_per_unit, 0)) * order_items.quantity) as profit')
        ->value('profit') ?? 0;

        $handover = FinancialHandover::create([
            'user_id' => $ownerId,
            'accountant_id' => $staff->id,
            'staff_shift_id' => $openShift ? $openShift->id : null,
            'handover_type' => 'staff_to_manager',
            'recipient_id' => $manager ? $manager->id : null,
            'department' => 'bar',
            'amount' => $totalAmount,
            'circulation_money' => $request->input('circulation_money', 0),
            'profit_amount' => $profitAmount,
            'payment_breakdown' => $breakdown,
            'handover_date' => $date,
            'status' => 'pending',
            'notes' => $request->notes
        ]);

        // 2. Automatically close the Counter's shift when they complete handover
        if ($openShift) {
            $cashAmountInput = $request->input('cash_amount', 0);
            $digitalAmountTotal = $totalAmount - $cashAmountInput;
            
            // Expected closing balance should be: Opening Cash + Cash collected from waiters
            // For now, we use the totalAmount as the basis but subtract digital components for the 'cash' tracking
            $expectedCashAtHand = $openShift->opening_balance + $cashAmountInput; 

            $openShift->update([
                'closing_balance' => $cashAmountInput,
                'total_sales_cash' => $cashAmountInput,
                'total_sales_digital' => $digitalAmountTotal,
                'expected_closing_balance' => $expectedCashAtHand,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => 'Shift closed automatically via Manager Handover.'
            ]);
        }

        // Send SMS notification to accountant
        try {
            $smsService = new \App\Services\HandoverSmsService();
            $smsService->sendHandoverSubmissionSms($handover, $ownerId);
        } catch (\Exception $e) {
            \Log::error('SMS notification failed for handover: ' . $e->getMessage());
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Handover mapped and sent to Manager successfully! Awaiting confirmation.'
            ]);
        }

        return back()->with('success', 'Handover mapped and sent to Manager successfully! Awaiting confirmation.');
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
