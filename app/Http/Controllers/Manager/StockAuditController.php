<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\StockTransfer;
use App\Models\TransferSale;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

        // Get completed transfers
        $query = StockTransfer::where('user_id', $ownerId)
            ->whereIn('status', ['completed', 'verified'])
            ->whereBetween('verified_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->with(['productVariant.product', 'transferSales']);

        $transfers = $query->orderBy('verified_at', 'desc')->get();

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

            if ($statusFilter === 'selling' && $isFullySold) continue;
            if ($statusFilter === 'sold_out' && !$isFullySold) continue;

            if ($isFullySold) $fullySoldBatchCount++;
            $totalExpected += $expectedRevenue;
            $totalCollected += $actualRevenue;

            $auditData[] = [
                'id' => $transfer->id,
                'number' => $transfer->transfer_number,
                'date' => $transfer->verified_at->format('M d, Y'),
                'product' => $transfer->productVariant->product->name . ' (' . $transfer->productVariant->measurement . ')',
                'qty' => $transfer->total_units,
                'sold_qty' => $soldQty,
                'expected_revenue' => $expectedRevenue,
                'actual_revenue' => $actualRevenue,
                'progress' => $progressPercent,
                'is_fully_sold' => $isFullySold,
                'status' => $isFullySold ? 'Fully Sold' : 'Selling...',
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
}
