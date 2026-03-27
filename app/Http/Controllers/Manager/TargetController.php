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
            ->where('target_type', 'monthly_bar')
            ->get()
            ->keyBy('target_type');

        // Staff Targets for today
        $date = $request->get('date', $request->get('target_date', date('Y-m-d')));
        $staffTargets = SalesTarget::where('user_id', $ownerId)
            ->where('target_date', $date)
            ->where('target_type', 'daily_staff')
            ->with('staff')
            ->get();

        // Get ONLY Waiters for daily targets
        $waiters = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->whereIn('slug', ['waiter', 'counter']);
            })
            ->get();

        // Real-time progress data
        $progress = $this->calculateProgress($ownerId, $month, $year, $date);

        // Effective Monthly Target (fallback to sum of staff daily targets)
        $barTarget = $monthlyTargets->has('monthly_bar') ? $monthlyTargets['monthly_bar']->target_amount : SalesTarget::where('user_id', $ownerId)
            ->where('target_type', 'daily_staff')
            ->whereMonth('target_date', $month)
            ->whereYear('target_date', $year)
            ->sum('target_amount');

        // Top 5 Monthly Beverage Drivers
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $topDrivers = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('orders.user_id', $ownerId)
            ->whereIn('orders.status', ['served', 'completed'])
            ->whereBetween('orders.created_at', [$startOfMonth, $endOfMonth])
            ->select(
                'products.name as brand',
                'product_variants.measurement',
                'product_variants.name as variant_name',
                DB::raw('SUM(order_items.quantity) as total_qty'),
                DB::raw('SUM(order_items.total_price) as total_revenue')
            )
            ->groupBy('product_variants.id', 'products.name', 'product_variants.measurement', 'product_variants.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        return view('manager.targets.index', compact(
            'monthlyTargets', 
            'staffTargets', 
            'waiters', 
            'month', 
            'year', 
            'date',
            'progress',
            'topDrivers',
            'barTarget'
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
        ]);

        // Bar Target
        SalesTarget::updateOrCreate(
            ['user_id' => $ownerId, 'target_type' => 'monthly_bar', 'month' => $validated['month'], 'year' => $validated['year']],
            ['target_amount' => $validated['bar_target'] ?? 0]
        );

        return back()->with('success', 'Monthly target for Bar updated successfully.');
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
            // Get all active waiters and counter staff
            $waiters = Staff::where('user_id', $ownerId)
                ->where('is_active', true)
                ->whereHas('role', function($q) {
                    $q->whereIn('slug', ['waiter', 'counter']);
                })
                ->get();
    
            foreach ($waiters as $waiter) {
                SalesTarget::updateOrCreate(
                    ['user_id' => $ownerId, 'staff_id' => $waiter->id, 'target_date' => $validated['target_date'], 'target_type' => 'daily_staff'],
                    ['target_amount' => $validated['target_amount']]
                );
            }
    
            return back()->with('success', 'Targets set successfully.');
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

        // Staff Performance (Daily vs Monthly MTD)
        $staffPerformances = [];
        $staffMonthPerformances = [];

        // Daily Achievements
        $dailyTotals = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $ownerId)
            ->whereIn('orders.status', ['served', 'completed'])
            ->whereDate('orders.created_at', $date)
            ->select('orders.waiter_id', DB::raw('SUM(order_items.total_price) as total'))
            ->groupBy('orders.waiter_id')
            ->get();

        foreach ($dailyTotals as $entry) {
            if ($entry->waiter_id) $staffPerformances[$entry->waiter_id] = $entry->total;
        }

        // Monthly Achievements (MTD)
        $monthlyTotals = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $ownerId)
            ->whereIn('orders.status', ['served', 'completed'])
            ->whereBetween('orders.created_at', [$startOfMonth, $endOfMonth])
            ->select('orders.waiter_id', DB::raw('SUM(order_items.total_price) as total'))
            ->groupBy('orders.waiter_id')
            ->get();

        foreach ($monthlyTotals as $entry) {
            if ($entry->waiter_id) $staffMonthPerformances[$entry->waiter_id] = $entry->total;
        }

        return [
            'bar_actual' => $actualBar,
            'staff_actual' => $staffPerformances,
            'staff_month_actual' => $staffMonthPerformances
        ];
    }

    /**
     * Reset the monthly drinks target for the current month.
     */
    public function resetMonthly()
    {
        if (!$this->hasPermission('reports', 'edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ownerId = $this->getOwnerId();
        $month = date('n');
        $year = date('Y');

        SalesTarget::where('user_id', $ownerId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('target_type', 'monthly_bar')
            ->delete();

        return redirect()->back()->with('success', 'Monthly target has been reset successfully. You can now set it afresh.');
    }

    /**
     * Reset all staff daily targets for the selected date.
     */
    public function resetDaily(Request $request)
    {
        if (!$this->hasPermission('reports', 'edit')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', date('Y-m-d'));

        SalesTarget::where('user_id', $ownerId)
            ->where('target_date', $date)
            ->where('target_type', 'daily_staff')
            ->delete();

        return redirect()->back()->with('success', 'Staff daily targets for ' . $date . ' have been reset successfully.');
    }
}
