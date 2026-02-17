<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use HandlesStaffPermissions;

    public function index()
    {
        // Check permission
        if (!$this->hasPermission('products', 'view')) {
            abort(403, 'You do not have permission to view products.');
        }
        
        return view('products.index');
    }

    public function categories()
    {
        // Check permission
        if (!$this->hasPermission('products', 'view')) {
            abort(403, 'You do not have permission to view product categories.');
        }
        
        return view('products.categories');
    }

    public function inventory()
    {
        // Check permission
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view inventory.');
        }
        
        return view('products.inventory');
    }
}
