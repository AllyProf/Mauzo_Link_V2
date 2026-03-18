<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\StockTransfer;
use App\Models\TransferSale;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockAuditController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Display the Stock-to-Cash Audit Dashboard for Managers
     */
    public function index(Request $request)
    {
        // Check permission - usually managers have full access
        if (!$this->hasPermission('reports', 'view') && !$this->hasPermission('finance', 'view')) {
            abort(403, 'You do not have permission to view stock audits.');
        }

        $ownerId = $this->getOwnerId();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $statusFilter = $request->get('status', 'all'); // 'all', 'selling', 'sold_out'

        // Get completed/approved/prepared transfers
        $query = StockTransfer::where('user_id', $ownerId)
            ->whereIn('status', ['completed', 'verified', 'approved', 'prepared'])
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->with(['productVariant.product', 'transferSales']);

        $transfers = $query->orderBy('created_at', 'desc')->get();

        $auditData = [];
        $totalExpected = 0;
        $totalCollected = 0;
        $fullySoldBatchCount = 0;

        foreach ($transfers as $transfer) {
            $financials = $transfer->calculateFinancials();
            $expectedRevenue = $financials['revenue'];
            
            // Calculate actual sales attributed to this transfer
            $actualRevenue = $transfer->transferSales->sum('total_price');
            $soldQty = $transfer->transferSales->sum('quantity');
            
            $progressPercent = $transfer->total_units > 0 ? round(($soldQty / $transfer->total_units) * 100, 1) : 0;
            $isFullySold = $progressPercent >= 100;
            $isAudited = (bool)$transfer->audited_at;

            if ($statusFilter === 'selling' && ($isFullySold || $isAudited)) continue;
            if ($statusFilter === 'sold_out' && (!$isFullySold || $isAudited)) continue;
            if ($statusFilter === 'audited' && !$isAudited) continue;

            if ($isFullySold && !$isAudited) $fullySoldBatchCount++;
            $totalExpected += $expectedRevenue;
            $totalCollected += $actualRevenue;

            $auditData[] = [
                'id' => $transfer->id,
                'number' => $transfer->transfer_number,
                'date' => $transfer->created_at->format('M d, Y'),
                'product' => $transfer->productVariant->product->name . ' (' . $transfer->productVariant->measurement . ')',
                'qty' => $transfer->total_units,
                'sold_qty' => $soldQty,
                'expected_revenue' => $expectedRevenue,
                'actual_revenue' => $actualRevenue,
                'progress' => $progressPercent,
                'is_fully_sold' => $isFullySold,
                'is_audited' => $isAudited,
                'status' => $isAudited ? 'Audited & Received' : ($isFullySold ? 'Fully Sold' : 'Selling...'),
            ];
        }

        return view('manager.stock_audit', compact(
            'auditData',
            'totalExpected',
            'totalCollected',
            'fullySoldBatchCount',
            'startDate',
            'endDate',
            'statusFilter'
        ));
    }

    /**
     * Get granular sale attribution details for a transfer
     */
    public function getDetails(StockTransfer $transfer)
    {
        if (!$this->hasPermission('reports', 'view')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $sales = TransferSale::where('stock_transfer_id', $transfer->id)
            ->with(['orderItem.order.waiter'])
            ->get()
            ->map(function($ts) {
                return [
                    'order_number' => $ts->orderItem->order->order_number ?? 'N/A',
                    'waiter' => $ts->orderItem->order->waiter->name ?? 'System',
                    'qty' => $ts->quantity,
                    'unit_price' => $ts->unit_price,
                    'total_price' => $ts->total_price,
                    'date' => $ts->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'transfer_number' => $transfer->transfer_number,
            'sales' => $sales
        ]);
    }

    /**
     * Audit and Finalize a Stock Batch (Manager only)
     */
    public function auditBatch(StockTransfer $transfer)
    {
        // Check permission - restricted to Manager/Owner
        if (!$this->hasPermission('reports', 'edit') && !$this->hasPermission('finance', 'edit')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($transfer->audited_at) {
            return response()->json(['success' => false, 'message' => 'Batch already audited.']);
        }

        try {
            DB::beginTransaction();

            $transfer->update([
                'audited_at' => now(),
                'audited_by' => auth()->id(),
                'status' => 'verified' // Move to verified if not already
            ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Batch audited successfully. Revenue collection verified.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
