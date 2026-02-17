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

    public function orders()
    {
        // Check permission
        if (!$this->hasPermission('sales', 'view')) {
            abort(403, 'You do not have permission to view orders.');
        }
        
        return view('sales.orders');
    }

    public function transactions()
    {
        // Check permission
        if (!$this->hasPermission('sales', 'view')) {
            abort(403, 'You do not have permission to view transactions.');
        }
        
        return view('sales.transactions');
    }
}
