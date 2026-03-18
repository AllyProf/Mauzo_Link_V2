<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\SalesTarget;
use App\Models\Staff;
use App\Models\BarOrder;
use App\Models\OrderItem;
use App\Models\KitchenOrderItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    use HandlesStaffPermissions;

    public function index(Request $request)
    {
        if (!$this->hasPermission('reports', 'view') && !$this->hasPermission('finance', 'view')) {
            abort(403);
        }

        $ownerId = $this->getOwnerId();
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        
        // Monthly Targets
        $monthlyTargets = SalesTarget::where('user_id', $ownerId)
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('target_type', ['monthly_bar', 'monthly_food'])
            ->get()
            ->keyBy('target_type');

        // Staff Targets for today
        $date = $request->get('date', date('Y-m-d'));
        $staffTargets = SalesTarget::where('user_id', $ownerId)
            ->where('target_date', $date)
            ->where('target_type', 'daily_staff')
            ->with('staff')
            ->get();

        // Get ONLY Waiters for daily targets
        $waiters = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->where('slug', 'waiter');
            })
            ->get();

        // Real-time progress data
        $progress = $this->calculateProgress($ownerId, $month, $year, $date);

        return view('manager.targets.index', compact(
            'monthlyTargets', 
            'staffTargets', 
            'waiters', 
            'month', 
            'year', 
            'date',
            'progress'
        ));
    }

    public function storeMonthly(Request $request)
    {
        if (!$this->hasPermission('reports', 'edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ownerId = $this->getOwnerId();
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'bar_target' => 'nullable|numeric|min:0',
            'food_target' => 'nullable|numeric|min:0',
        ]);

        // Bar Target
        SalesTarget::updateOrCreate(
            ['user_id' => $ownerId, 'target_type' => 'monthly_bar', 'month' => $validated['month'], 'year' => $validated['year']],
            ['target_amount' => $validated['bar_target'] ?? 0]
        );

        // Food Target
        SalesTarget::updateOrCreate(
            ['user_id' => $ownerId, 'target_type' => 'monthly_food', 'month' => $validated['month'], 'year' => $validated['year']],
            ['target_amount' => $validated['food_target'] ?? 0]
        );

        return back()->with('success', 'Monthly targets updated successfully.');
    }

    public function storeStaff(Request $request)
    {
        if (!$this->hasPermission('reports', 'edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
    
        $ownerId = $this->getOwnerId();
        $validated = $request->validate([
            'staff_id' => 'required', // Can be numeric or 'all'
            'target_amount' => 'required|numeric|min:0',
            'target_date' => 'required|date',
        ]);
    
        if ($validated['staff_id'] === 'all') {
            // Get all active waiters
            $waiters = Staff::where('user_id', $ownerId)
                ->where('is_active', true)
                ->whereHas('role', function($q) {
                    $q->where('slug', 'waiter');
                })
                ->get();
    
            foreach ($waiters as $waiter) {
                SalesTarget::updateOrCreate(
                    ['user_id' => $ownerId, 'staff_id' => $waiter->id, 'target_date' => $validated['target_date'], 'target_type' => 'daily_staff'],
                    ['target_amount' => $validated['target_amount']]
                );
            }
    
            return back()->with('success', 'Targets set for all waiters successfully.');
        } else {
            SalesTarget::updateOrCreate(
                ['user_id' => $ownerId, 'staff_id' => $validated['staff_id'], 'target_date' => $validated['target_date'], 'target_type' => 'daily_staff'],
                ['target_amount' => $validated['target_amount']]
            );
    
            return back()->with('success', 'Staff target updated successfully.');
        }
    }

    private function calculateProgress($ownerId, $month, $year, $date)
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Actual Bar Sales this month
        $actualBar = OrderItem::whereHas('order', function($q) use ($ownerId, $startOfMonth, $endOfMonth) {
                $q->where('user_id', $ownerId)
                  ->where('status', 'served')
                  ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })->sum('total_price');

        // Actual Food Sales this month
        $actualFood = KitchenOrderItem::whereHas('order', function($q) use ($ownerId, $startOfMonth, $endOfMonth) {
                $q->where('user_id', $ownerId)
                  ->where('status', 'served')
                  ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })->sum('total_price');

        // Staff Daily Performance
        $staffPerformances = [];
        $staffOrders = BarOrder::where('user_id', $ownerId)
            ->whereDate('created_at', $date)
            ->where('status', 'served')
            ->select('waiter_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('waiter_id')
            ->get();

        foreach ($staffOrders as $order) {
            $staffPerformances[$order->waiter_id] = $order->total;
        }

        return [
            'bar_actual' => $actualBar,
            'food_actual' => $actualFood,
            'staff_actual' => $staffPerformances
        ];
    }
}
