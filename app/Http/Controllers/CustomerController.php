<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use HandlesStaffPermissions;

    public function index()
    {
        // Check permission
        if (!$this->hasPermission('customers', 'view')) {
            abort(403, 'You do not have permission to view customers.');
        }
        
        return view('customers.index');
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
