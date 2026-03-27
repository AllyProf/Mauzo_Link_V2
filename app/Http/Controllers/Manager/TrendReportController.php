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
        $range = $request->get('range', '30');
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

        // 1. Core Financial Trends
        $history = DailyCashLedger::where('user_id', $ownerId)
            ->whereBetween('ledger_date', [$startDate, $endDate])
            ->where('status', 'closed')
            ->orderBy('ledger_date', 'asc')
            ->get();

        $chartData = [
            'labels' => [],
            'revenue' => [],
            'profit' => [],
            'expense' => [],
            'margin' => []
        ];

        foreach ($history as $row) {
            $revenue = floatval($row->total_cash_received + $row->total_digital_received);
            $profit = floatval($row->profit_submitted_to_boss);
            $expense = floatval($row->total_expenses);
            
            $chartData['labels'][] = $row->ledger_date->format('M d');
            $chartData['revenue'][] = $revenue;
            $chartData['profit'][] = $profit;
            $chartData['expense'][] = $expense;
            $chartData['margin'][] = $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0;
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
        $monthlyComparison = DailyCashLedger::where('user_id', $ownerId)
            ->where('status', 'closed')
            ->where('ledger_date', '>=', Carbon::today()->subMonths(6))
            ->selectRaw('DATE_FORMAT(ledger_date, "%Y-%m") as month, SUM(total_cash_received + total_digital_received) as revenue, SUM(profit_submitted_to_boss) as profit')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        return view('manager.reports.trends', compact('chartData', 'totals', 'circulationData', 'monthlyComparison', 'range'));
    }
}
