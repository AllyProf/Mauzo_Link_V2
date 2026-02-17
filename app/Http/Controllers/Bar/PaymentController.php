<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarPayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Check permission
        if (!$this->hasPermission('bar_payments', 'view')) {
            abort(403, 'You do not have permission to view payments.');
        }

        $ownerId = $this->getOwnerId();
        
        $payments = BarPayment::where('user_id', $ownerId)
            ->with(['order', 'processedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('bar.payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Check permission
        if (!$this->hasPermission('bar_payments', 'view')) {
            abort(403, 'You do not have permission to view payment details.');
        }

        $ownerId = $this->getOwnerId();
        
        $payment = BarPayment::where('user_id', $ownerId)
            ->with(['order.items', 'processedBy'])
            ->findOrFail($id);

        return view('bar.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
