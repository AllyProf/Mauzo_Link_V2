<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use App\Models\WaiterDailyReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaiterSalesController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Display waiter sales dashboard
     */
    public function salesDashboard(Request $request)
    {
        $waiter = $this->getCurrentStaff();
        
        if (!$waiter || !$waiter->is_active) {
            abort(403, 'You must be logged in as an active waiter.');
        }

        // Check if staff has waiter role
        $role = $waiter->role;
        if (!$role || strtolower($role->name) !== 'waiter') {
            abort(403, 'You do not have permission to access the waiter sales dashboard.');
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', now()->format('Y-m-d'));

        // Get all orders for this waiter on this date
        $orders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $waiter->id)
            ->whereDate('created_at', $date)
            ->with(['items.productVariant.product', 'kitchenOrderItems', 'table', 'orderPayments'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals
        $totalSales = $orders->sum('total_amount');
        $cashCollected = $orders->where('payment_method', 'cash')->sum('paid_amount') + 
                        $orders->sum(function($order) {
                            return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                        });
        $mobileMoneyCollected = $orders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                               $orders->sum(function($order) {
                                   return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                               });
        $totalOrders = $orders->count();
        $expectedAmount = $totalSales; // Expected = Total sales

        // Check if reconciliation already submitted
        $reconciliation = WaiterDailyReconciliation::where('waiter_id', $waiter->id)
            ->where('reconciliation_date', $date)
            ->first();

        return view('bar.waiter.sales', compact(
            'orders', 
            'totalSales', 
            'cashCollected', 
            'mobileMoneyCollected', 
            'totalOrders', 
            'expectedAmount',
            'date', 
            'reconciliation',
            'waiter'
        ));
    }

    /**
     * Submit daily reconciliation
     */
    public function submitReconciliation(Request $request)
    {
        $waiter = $this->getCurrentStaff();
        
        if (!$waiter || !$waiter->is_active) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ownerId = $this->getOwnerId();
        $date = $request->input('date', now()->format('Y-m-d'));
        $submittedAmount = $request->input('submitted_amount', 0);
        $notes = $request->input('notes', '');

        // Check if already submitted
        $existing = WaiterDailyReconciliation::where('waiter_id', $waiter->id)
            ->where('reconciliation_date', $date)
            ->first();

        if ($existing && $existing->isSubmitted()) {
            return response()->json([
                'error' => 'Reconciliation already submitted for this date.'
            ], 400);
        }

        // Get all orders for this waiter on this date
        $orders = BarOrder::where('user_id', $ownerId)
            ->where('waiter_id', $waiter->id)
            ->whereDate('created_at', $date)
            ->get();

        // Calculate totals
        $totalSales = $orders->sum('total_amount');
        $cashCollected = $orders->where('payment_method', 'cash')->sum('paid_amount') + 
                        $orders->sum(function($order) {
                            return $order->orderPayments->where('payment_method', 'cash')->sum('amount');
                        });
        $mobileMoneyCollected = $orders->where('payment_method', 'mobile_money')->sum('paid_amount') + 
                              $orders->sum(function($order) {
                                  return $order->orderPayments->where('payment_method', 'mobile_money')->sum('amount');
                              });
        $expectedAmount = $totalSales;
        $difference = $submittedAmount - $expectedAmount;

        DB::beginTransaction();
        try {
            if ($existing) {
                // Update existing
                $existing->update([
                    'total_sales' => $totalSales,
                    'cash_collected' => $cashCollected,
                    'mobile_money_collected' => $mobileMoneyCollected,
                    'expected_amount' => $expectedAmount,
                    'submitted_amount' => $submittedAmount,
                    'difference' => $difference,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'notes' => $notes,
                ]);
                $reconciliation = $existing;
            } else {
                // Create new
                $reconciliation = WaiterDailyReconciliation::create([
                    'user_id' => $ownerId,
                    'waiter_id' => $waiter->id,
                    'reconciliation_date' => $date,
                    'total_sales' => $totalSales,
                    'cash_collected' => $cashCollected,
                    'mobile_money_collected' => $mobileMoneyCollected,
                    'expected_amount' => $expectedAmount,
                    'submitted_amount' => $submittedAmount,
                    'difference' => $difference,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'notes' => $notes,
                ]);
            }

            // Link orders to reconciliation
            BarOrder::where('user_id', $ownerId)
                ->where('waiter_id', $waiter->id)
                ->whereDate('created_at', $date)
                ->update(['reconciliation_id' => $reconciliation->id]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reconciliation submitted successfully.',
                'reconciliation' => $reconciliation
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error submitting reconciliation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to submit reconciliation: ' . $e->getMessage()
            ], 500);
        }
    }
}
