<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    use HandlesStaffPermissions;

    public function index(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('customers', 'view')) {
            abort(403, 'You do not have permission to view customers.');
        }

        $ownerId = $this->getOwnerId();
        $search = $request->input('search');

        // Fetch unique customers from the orders table
        // We group by phone number to treat the same number as the same customer
        $query = BarOrder::where('user_id', $ownerId)
            ->whereNotNull('customer_phone')
            ->select(
                'customer_phone',
                DB::raw('MAX(customer_name) as name'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('MAX(created_at) as last_visit')
            )
            ->groupBy('customer_phone');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('last_visit', 'desc')->paginate(20);
        
        return view('customers.index', compact('customers', 'search'));
    }

    public function groups()
    {
        // Check permission
        if (!$this->hasPermission('customers', 'view')) {
            abort(403, 'You do not have permission to view customer groups.');
        }
        
        return view('customers.groups');
    }
}
