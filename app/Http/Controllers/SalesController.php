<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    use HandlesStaffPermissions;

    public function pos()
    {
        // Check permission
        if (!$this->hasPermission('sales', 'view')) {
            abort(403, 'You do not have permission to access Point of Sale.');
        }
        
        return view('sales.pos');
    }

    public function orders(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('sales', 'view')) {
            abort(403, 'You do not have permission to view orders.');
        }
        
        $ownerId = $this->getOwnerId();
        
        $query = \App\Models\BarOrder::where('user_id', $ownerId)
                    ->with(['table', 'items.productVariant.product', 'kitchenOrderItems', 'createdBy', 'servedBy'])
                    ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);

        return view('sales.orders', compact('orders'));
    }

    public function transactions(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('sales', 'view')) {
            abort(403, 'You do not have permission to view transactions.');
        }
        
        $ownerId = $this->getOwnerId();

        $query = \App\Models\BarOrder::where('user_id', $ownerId)
            ->where('payment_status', '!=', 'pending')
            ->orderBy('updated_at', 'desc');

        if ($request->has('method') && $request->method !== 'all') {
            $query->where('payment_method', $request->method);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('updated_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('updated_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);

        // Summaries
        $totalTotal = $query->sum('total_amount');
        $totalPaid = $query->sum('paid_amount');

        return view('sales.transactions', compact('transactions', 'totalTotal', 'totalPaid'));
    }
}
