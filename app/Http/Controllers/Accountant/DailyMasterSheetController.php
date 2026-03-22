<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailyCashLedger;
use App\Models\DailyExpense;
use App\Models\FinancialHandover;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Traits\HandlesStaffPermissions;

class DailyMasterSheetController extends Controller
{
    use HandlesStaffPermissions;

    public function history(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $query = DailyCashLedger::where('user_id', $ownerId);

        if ($request->filled('start_date')) {
            $query->where('ledger_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('ledger_date', '<=', $request->end_date);
        }

        $ledgers = $query->orderBy('ledger_date', 'desc')->paginate(5);
            
        $ledgers->getCollection()->transform(function($ledger) use ($ownerId) {
            // SYNC OPEN LEDGERS: If it's still active, pull the latest previous close balance
            if ($ledger->status === 'open') {
                $latestPrev = $this->getPreviousClosingCash($ownerId, $ledger->ledger_date);
                if ($ledger->opening_cash != $latestPrev) {
                    $ledger->opening_cash = $latestPrev;
                    $ledger->save();
                }
            }

            $handovers = FinancialHandover::where('user_id', $ownerId)
                ->whereDate('handover_date', $ledger->ledger_date)
                ->get();
            
            $cash = 0;
            $digital = 0;
            foreach ($handovers as $h) {
                $cash += floatval($h->amount);
                // Shortage Note Check
                if (preg_match('/\[ShortagePaidTotal:(\d+)\]/', $h->notes ?? '', $sm)) {
                    $cash += floatval($sm[1]);
                }
                // Digital Breakdown
                $breakdown = $h->payment_breakdown;
                if (is_string($breakdown)) $breakdown = json_decode($breakdown, true);
                if (is_array($breakdown)) {
                    $methods = ['mpesa', 'nmb', 'kcb', 'crdb', 'mixx', 'tigo_pesa', 'airtel_money', 'halopesa'];
                    foreach ($methods as $m) {
                        $digital += floatval($breakdown[$m] ?? 0);
                    }
                }
            }
            $ledger->handoverCash = $cash;
            $ledger->handoverDigital = $digital;

            $ledger->expenseList = $ledger->expenses()->orderBy('created_at', 'desc')->get();
            $ledger->pettyCashList = \App\Models\PettyCashIssue::where('user_id', $ownerId)
                ->whereDate('issue_date', $ledger->ledger_date)
                ->where('status', 'issued')
                ->get();

            // Manager Confirmation Status for the Profit
            $managerHandover = FinancialHandover::where('user_id', $ownerId)
                ->whereDate('handover_date', $ledger->ledger_date)
                ->where('handover_type', 'accountant_to_owner')
                ->where('department', 'Master Sheet')
                ->first();
            
            $ledger->managerReceiptStatus = $managerHandover ? $managerHandover->status : 'none';
            $ledger->isManagerReceived = ($managerHandover && $managerHandover->status === 'confirmed');

            return $ledger;
        });

        return view('accountant.daily_master_sheet_history', compact('ledgers'));
    }

    public function index(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        
        $ownerId = $this->getOwnerId();
        $staffId = session('is_staff') ? session('staff_id') : (Auth::user()->staff->id ?? null);

        $ledger = DailyCashLedger::firstOrCreate(
            ['user_id' => $ownerId, 'ledger_date' => $date],
            [
                'accountant_id' => $staffId,
                'opening_cash' => $this->getPreviousClosingCash($ownerId, $date),
                'status' => 'open'
            ]
        );

        // SYNC: If ledger is still open, always re-fetch opening cash from the latest previous close
        // This handles cases where a previous day was closed AFTER today's ledger was already initialized.
        if ($ledger->status === 'open') {
            $latestOpening = $this->getPreviousClosingCash($ownerId, $date);
            if ($ledger->opening_cash != $latestOpening) {
                $ledger->opening_cash = $latestOpening;
                $ledger->save();
            }
        }

        // Calculate received from handovers
        $handovers = FinancialHandover::where('user_id', $ownerId)->whereDate('handover_date', $date)->get();
        
        $totalCash = 0;
        $totalDigital = 0;
        $digitalBreakdowns = [];

        foreach ($handovers as $handover) {
            // Add the FULL handover amount to total physical cash (since accountant withdraws it all)
            $totalCash += floatval($handover->amount);
            
            // ADDITION: Include any shortage payments made to this handover (Accountant Audit Money)
            if (preg_match('/\[ShortagePaidTotal:(\d+)\]/', $handover->notes ?? '', $sm)) {
                $totalCash += floatval($sm[1]);
            }

            $breakdown = $handover->payment_breakdown;
            if (is_string($breakdown)) {
                $breakdown = json_decode($breakdown, true);
            }

            if (is_array($breakdown)) {
                // Track digital categories for display purposes only
                $methods = ['mpesa', 'nmb', 'kcb', 'crdb', 'mixx', 'tigo_pesa', 'airtel_money', 'halopesa'];
                foreach ($methods as $m) {
                    $amt = floatval($breakdown[$m] ?? 0);
                    if ($amt > 0) {
                        $totalDigital += $amt;
                        $methodName = str_replace('_', ' ', $m);
                        $digitalBreakdowns[$methodName] = ($digitalBreakdowns[$methodName] ?? 0) + $amt;
                    }
                }
            }
        }

        // Real-time sales profit for today (based on items depleted from batches)
        $transferSales = \App\Models\TransferSale::whereHas('stockTransfer', function($q) use ($ownerId) {
                $q->where('user_id', $ownerId);
            })
            ->whereDate('created_at', $date)
            ->with(['stockTransfer.productVariant'])
            ->get();
            
        $stockProfit = 0;
        foreach ($transferSales as $sale) {
            $variant = $sale->stockTransfer->productVariant;
            $warehouseStock = \App\Models\StockLocation::where('user_id', $ownerId)
                ->where('product_variant_id', $sale->stockTransfer->product_variant_id)
                ->where('location', 'warehouse')
                ->first();
                
            $buyingPrice = $warehouseStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0;
            $cost = $sale->quantity * $buyingPrice;
            $stockProfit += ($sale->total_price - $cost);
        }

        // Fetch manually logged daily expenses
        $expenses = $ledger->expenses()->orderBy('created_at', 'desc')->get();
        
        // Fetch Petty Cash Issues (which contains Purchase Requests issued money)
        $pettyCashIssues = \App\Models\PettyCashIssue::where('user_id', $ownerId)
            ->whereDate('issue_date', $date)
            ->where('status', 'issued')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $totalPettyCash = $pettyCashIssues->sum('amount');
        $totalExpensesCombined = $ledger->total_expenses + $totalPettyCash;

        // Update the ledger with the freshly computed totals (if open)
        if ($ledger->status === 'open') {
            $ledger->update([
                'total_cash_received' => $totalCash,
                'total_digital_received' => $totalDigital,
                'profit_generated' => $stockProfit,
                'expected_closing_cash' => $ledger->opening_cash + $totalCash - $totalExpensesCombined
            ]);
            // Update the model instance for the view AFTER the db update
            $ledger->expected_closing_cash = $ledger->opening_cash + $totalCash - $totalExpensesCombined;
        }
        
        $ledger->setAttribute('total_expenses_combined', $totalExpensesCombined);

        return view('accountant.daily_master_sheet', compact(
            'date', 'ledger', 'handovers', 'expenses', 'pettyCashIssues', 'digitalBreakdowns', 'totalPettyCash',
            'totalCash', 'totalDigital', 'stockProfit'
        ));
    }

    private function getPreviousClosingCash($ownerId, $date)
    {
        $prevLedger = DailyCashLedger::where('user_id', $ownerId)
            ->where('ledger_date', '<', $date)
            ->where('status', 'closed')
            ->orderBy('ledger_date', 'desc')
            ->first();

        return $prevLedger ? $prevLedger->carried_forward : 0;
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'ledger_id' => 'required|exists:daily_cash_ledgers,id',
            'category' => 'required|string',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        $ledger = DailyCashLedger::findOrFail($request->ledger_id);
        
        if ($ledger->status !== 'open') {
            return response()->json(['success' => false, 'error' => 'Ledger is already closed/verified.']);
        }

        // CONTROL: Ensure expense doesn't exceed the "Amount to Cycle"
        // We calculate this as: (Consolidated Cash) - Profit Generated
        $totalBusinessValue = $ledger->opening_cash + $ledger->total_cash_received;
        $profitGenerated = floatval($ledger->profit_generated);
        $maxAllowedExpenses = $totalBusinessValue - $profitGenerated;

        if (($ledger->total_expenses + $request->amount) > $maxAllowedExpenses) {
            $available = max(0, $maxAllowedExpenses - $ledger->total_expenses);
            return response()->json([
                'success' => false, 
                'error' => "Expenditure limit reached. You cannot spend more than the business operating fund (TSh " . number_format($available) . " remaining). The rest is reserved for Profit."
            ], 422);
        }

        $user = \Auth::user();
        
        \App\Models\DailyExpense::create([
            'daily_cash_ledger_id' => $ledger->id,
            'user_id' => $ledger->user_id,
            'logged_by' => $user->staff->id ?? null,
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount
        ]);

        // Recalculate ledger totals
        $ledger->total_expenses = $ledger->expenses()->sum('amount');
        $ledger->expected_closing_cash = $ledger->opening_cash + $ledger->total_cash_received - $ledger->total_expenses;
        $ledger->save();

        return response()->json(['success' => true, 'message' => 'Expense logged successfully.']);
    }

    public function deleteExpense($id)
    {
        $expense = \App\Models\DailyExpense::findOrFail($id);
        $ledger = $expense->ledger;

        if ($ledger->status !== 'open') {
            return response()->json(['success' => false, 'error' => 'Cannot delete from a closed ledger.'], 403);
        }

        $expense->delete();

        // Recalculate
        $ledger->total_expenses = $ledger->expenses()->sum('amount');
        $ledger->expected_closing_cash = $ledger->opening_cash + $ledger->total_cash_received - $ledger->total_expenses;
        $ledger->save();

        return response()->json(['success' => true, 'message' => 'Expense removed.']);
    }

    public function closeDay(Request $request)
    {
        $request->validate([
            'ledger_id' => 'required|exists:daily_cash_ledgers,id',
            'actual_closing_cash' => 'required|numeric|min:0',
            'profit_submitted_to_boss' => 'required|numeric|min:0',
            'carried_forward' => 'required|numeric|min:0'
        ]);

        $ledger = DailyCashLedger::findOrFail($request->ledger_id);
        
        if ($ledger->status !== 'open') {
            return redirect()->back()->with('error', 'Ledger is already closed.');
        }

        $ledger->update([
            'actual_closing_cash' => $request->actual_closing_cash,
            'profit_submitted_to_boss' => $request->profit_submitted_to_boss,
            'carried_forward' => $request->carried_forward,
            'status' => 'closed',
            'closed_at' => now(),
            'accountant_id' => Auth::user()->staff->id ?? null
        ]);

        // ── AUTO-GENERATE FINANCIAL HANDOVER (Accountant to Manager/Boss)
        if ($request->profit_submitted_to_boss > 0) {
            $ownerId = session('is_staff') ? \App\Models\Staff::find(session('staff_id'))->user_id : Auth::id();
            \App\Models\FinancialHandover::updateOrCreate(
                [
                    'user_id' => $ownerId,
                    'handover_date' => $ledger->ledger_date,
                    'handover_type' => 'accountant_to_owner',
                    'department' => 'Master Sheet'
                ],
                [
                    'accountant_id' => Auth::user()->staff->id ?? null,
                    'amount' => $request->profit_submitted_to_boss,
                    'status' => 'pending',
                    'payment_method' => 'cash',
                ]
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Day closed and verified successfully. Financial records are now locked.',
                'redirect' => route('accountant.daily-master-sheet', ['date' => $ledger->ledger_date])
            ]);
        }

        return redirect()->back()->with('success', 'Day closed successfully. Values locked.');
    }
}
