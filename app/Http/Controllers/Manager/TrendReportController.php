<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DailyCashLedger;
use App\Models\DailyExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrendReportController extends Controller
{
    private function getOwnerId()
    {
        return session('is_staff') ? \App\Models\Staff::find(session('staff_id'))->user_id : Auth::id();
    }

    public function index(Request $request)
    {
        $ownerId = $this->getOwnerId();
        
        // Range Filtering (Default to 30 days)
        $range = $request->get('range', '7');
        $endDate = Carbon::today();
        
        if ($range === '7') {
            $startDate = Carbon::today()->subDays(6);
        } elseif ($range === '90') {
            $startDate = Carbon::today()->subDays(89);
        } elseif ($range === 'year') {
            $startDate = Carbon::today()->startOfYear();
        } else {
            $startDate = Carbon::today()->subDays(29);
        }

        // Freeze dates as strings BEFORE any mutation from startOfDay/endOfDay calls
        $startStr = $startDate->format('Y-m-d') . ' 00:00:00';
        $endStr   = $endDate->format('Y-m-d') . ' 23:59:59';
        $periodRangeDays = $startDate->diffInDays($endDate);

        // 1. Live Aggregate Financial Trends
        $revenues = DB::table('orders')
            ->where('user_id', $ownerId)
            ->whereBetween('created_at', [$startStr, $endStr])
            ->where('payment_status', 'paid')
            ->selectRaw('DATE(created_at) as day_date, SUM(total_amount) as amount')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->pluck('amount', 'day_date');

        // Gross Profit calculated from order items: (selling - buying) * qty
        // This matches the reconciliation page's profit logic for accuracy
        $profits = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->where('orders.user_id', $ownerId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startStr, $endStr])
            ->selectRaw('DATE(orders.created_at) as day_date, SUM((order_items.unit_price - COALESCE(product_variants.buying_price_per_unit, 0)) * order_items.quantity) as amount')
            ->groupByRaw('DATE(orders.created_at)')
            ->get()
            ->pluck('amount', 'day_date');

        $pettyCash = DB::table('petty_cash_issues')
            ->where('user_id', $ownerId)
            ->whereBetween('issue_date', [$startStr, $endStr])
            ->where('status', 'issued')
            ->selectRaw('DATE(issue_date) as day_date, SUM(amount) as amount')
            ->groupByRaw('DATE(issue_date)')
            ->get()
            ->pluck('amount', 'day_date');

        $counterExpenses = DB::table('counter_expenses')
            ->where('user_id', $ownerId)
            ->whereBetween('expense_date', [$startStr, $endStr])
            ->selectRaw('DATE(expense_date) as day_date, SUM(amount) as amount')
            ->groupByRaw('DATE(expense_date)')
            ->get()
            ->pluck('amount', 'day_date');

        $chartData = [
            'labels' => [],
            'revenue' => [],
            'profit' => [],
            'expense' => [],
            'margin' => []
        ];

        // Loop day-by-day across the target range
        for ($i = $periodRangeDays; $i >= 0; $i--) {
            $day = (clone $endDate)->subDays($i);
            $dateStr = $day->format('Y-m-d');
            
            $revDay = floatval($revenues->get($dateStr, 0));
            $profDay = floatval($profits->get($dateStr, 0));
            $expDay = floatval($pettyCash->get($dateStr, 0)) + floatval($counterExpenses->get($dateStr, 0));

            $chartData['labels'][] = $day->format('M d');
            $chartData['revenue'][] = $revDay;
            $chartData['profit'][] = $profDay;
            $chartData['expense'][] = $expDay;
            $chartData['margin'][] = $revDay > 0 ? round(($profDay / $revDay) * 100, 1) : 0;
        }

        // 2. Summary Totals
        $totals = [
            'revenue' => array_sum($chartData['revenue']),
            'profit' => array_sum($chartData['profit']),
            'expense' => array_sum($chartData['expense']),
            'margin' => count($chartData['margin']) > 0 ? array_sum($chartData['margin']) / count($chartData['margin']) : 0
        ];
        
        // Calculate COGS (Buying Cost of goods) as the remainder
        // Revenue = COGS + Expenses + Profit
        $totals['cogs'] = max(0, $totals['revenue'] - $totals['profit'] - $totals['expense']);

        // 3. Profit vs Circulation (Circulation here = Total Cash Inflow)
        // This visualizes how much of the cash moving through the business actually stays as profit
        $circulationData = [
            'profit' => $totals['profit'],
            'cost_and_expense' => $totals['revenue'] - $totals['profit'],
            'cogs' => $totals['cogs'],
            'expense' => $totals['expense']
        ];

        // 4. Monthly Comparison (Last 6 Months)
        $monthlyComparison = collect();
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::today()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::today()->subMonths($i)->endOfMonth();
            $mStr = $monthStart->format('Y-m');

            $rev = DB::table('orders')->where('user_id', $ownerId)
                ->whereBetween('created_at', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()])
                ->where('payment_status', 'paid')
                ->sum('total_amount');
                
            $prof = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->where('orders.user_id', $ownerId)
                ->where('orders.payment_status', 'paid')
                ->whereBetween('orders.created_at', [$monthStart->toDateTimeString(), $monthEnd->toDateTimeString()])
                ->selectRaw('SUM((order_items.unit_price - COALESCE(product_variants.buying_price_per_unit, 0)) * order_items.quantity) as profit')
                ->value('profit');

            if ($rev > 0 || $prof > 0 || $i === 0) {
                $monthlyComparison->push((object)[
                    'month' => $mStr,
                    'revenue' => $rev,
                    'profit' => $prof
                ]);
            }
        }

        return view('manager.reports.trends', compact('chartData', 'totals', 'circulationData', 'monthlyComparison', 'range'));
    }
}
