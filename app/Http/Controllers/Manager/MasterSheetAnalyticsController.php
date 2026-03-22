<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\DailyCashLedger;
use App\Models\DailyExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MasterSheetAnalyticsController extends Controller
{
    private function getOwnerId()
    {
        return session('is_staff') ? \App\Models\Staff::find(session('staff_id'))->user_id : Auth::id();
    }

    public function index(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $days = $request->input('days', 30);
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($days - 1);

        // 1. Summary Cards Data
        $summary = DailyCashLedger::where('user_id', $ownerId)
            ->whereBetween('ledger_date', [$startDate, $endDate])
            ->where('status', 'closed')
            ->selectRaw('SUM(total_cash_received) as revenue, SUM(total_expenses) as expenses, SUM(profit_submitted_to_boss) as profit, AVG(total_cash_received) as avg_revenue')
            ->first();

        // 2. Trend Data (Last X Days)
        $history = DailyCashLedger::where('user_id', $ownerId)
            ->whereBetween('ledger_date', [$startDate, $endDate])
            ->orderBy('ledger_date', 'asc')
            ->get();

        $chartLabels = [];
        $revenueData = [];
        $profitData = [];
        $expenseData = [];

        foreach ($history as $row) {
            $chartLabels[] = Carbon::parse($row->ledger_date)->format('M d');
            $revenueData[] = floatval($row->total_cash_received);
            $profitData[] = floatval($row->status === 'closed' ? $row->profit_submitted_to_boss : $row->profit_generated);
            $expenseData[] = floatval($row->total_expenses);
        }

        // 3. Expense Distribution (Pie Chart)
        $expenseGroups = DailyExpense::where('user_id', $ownerId)
            ->whereHas('ledger', function($q) use ($startDate, $endDate) {
                $q->whereBetween('ledger_date', [$startDate, $endDate]);
            })
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // 4. Monthly Totals Breakdown
        $monthlyStats = DailyCashLedger::where('user_id', $ownerId)
            ->where('status', 'closed')
            ->whereYear('ledger_date', now()->year)
            ->selectRaw('MONTH(ledger_date) as month, SUM(total_cash_received) as revenue, SUM(total_expenses) as expenses, SUM(profit_submitted_to_boss) as profit')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        // 5. Pending collections for Manager oversight
        $pendingCollections = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->where('status', 'pending')
            ->where('department', 'Master Sheet')
            ->with('staff')
            ->get();

        return view('manager.master_sheet_analytics', compact(
            'summary', 'chartLabels', 'revenueData', 'profitData', 'expenseData', 
            'expenseGroups', 'monthlyStats', 'days', 'pendingCollections'
        ));
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

        return redirect()->back()->with('success', 'Profit receipt confirmed successfully. Records updated.');
    }

    public function resetHandover($id)
    {
        $ownerId = $this->getOwnerId();
        $handover = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->where('id', $id)
            ->where('status', 'confirmed')
            ->firstOrFail();

        $handover->update([
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        return redirect()->back()->with('success', 'Profit receipt has been reset to pending. You can now verify it again.');
    }

    public function collections()
    {
        $ownerId = $this->getOwnerId();
        
        $pendingCollections = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->where('status', 'pending')
            ->where('department', 'Master Sheet')
            ->with('staff')
            ->orderBy('handover_date', 'desc')
            ->get();

        $receivedCollections = \App\Models\FinancialHandover::where('user_id', $ownerId)
            ->where('status', 'confirmed')
            ->where('department', 'Master Sheet')
            ->with('staff')
            ->orderBy('confirmed_at', 'desc')
            ->limit(30)
            ->get();

        // Statistical summaries
        $collectionStats = [
            'total_pending' => $pendingCollections->sum('amount'),
            'total_received_month' => \App\Models\FinancialHandover::where('user_id', $ownerId)
                ->where('status', 'confirmed')
                ->where('department', 'Master Sheet')
                ->whereMonth('confirmed_at', now()->month)
                ->sum('amount'),
            'avg_collection' => \App\Models\FinancialHandover::where('user_id', $ownerId)
                ->where('status', 'confirmed')
                ->where('department', 'Master Sheet')
                ->avg('amount') ?? 0,
            'count_all' => \App\Models\FinancialHandover::where('user_id', $ownerId)
                ->where('status', 'confirmed')
                ->where('department', 'Master Sheet')
                ->count()
        ];

        return view('manager.profit_collections', compact('pendingCollections', 'receivedCollections', 'collectionStats'));
    }
}
