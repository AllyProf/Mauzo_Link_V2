<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    use HandlesStaffPermissions;
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('suppliers', 'view')) {
            abort(403, 'You do not have permission to view suppliers.');
        }

        $ownerId = $this->getOwnerId();
        $query = Supplier::where('user_id', $ownerId);

        // Filter by supplier type if provided (food, beverage, general)
        $type = $request->get('type', 'all');
        if ($type !== 'all' && in_array($type, ['food', 'beverage', 'general'])) {
            // Explicitly filter by supplier_type
            if ($type === 'general') {
                // For 'general', include both 'general' and NULL (treat NULL as general)
                $query->where(function($q) {
                    $q->where('supplier_type', 'general')
                      ->orWhereNull('supplier_type');
                });
            } else {
                // For 'food' or 'beverage', only show exact matches (exclude NULL and other types)
                $query->where('supplier_type', '=', $type);
            }
        }

        $suppliers = $query->orderBy('company_name')->paginate(20);

        // Append type parameter to pagination links if it's set
        if ($type !== 'all') {
            $suppliers->appends(['type' => $type]);
        }

        // Determine page title and description based on type
        $pageTitle = 'Suppliers';
        $pageDescription = 'Manage your suppliers';
        
        if ($type === 'food') {
            $pageTitle = 'Food Suppliers';
            $pageDescription = 'Manage your food ingredient suppliers';
        } elseif ($type === 'beverage') {
            $pageTitle = 'Beverage Suppliers';
            $pageDescription = 'Manage your beverage suppliers';
        }

        return view('bar.suppliers.index', compact('suppliers', 'pageTitle', 'pageDescription', 'type'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('suppliers', 'create')) {
            abort(403, 'You do not have permission to create suppliers.');
        }

        $type = $request->get('type', 'general');
        return view('bar.suppliers.create', compact('type'));
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('suppliers', 'create')) {
            abort(403, 'You do not have permission to create suppliers.');
        }

        $ownerId = $this->getOwnerId();
        
        $validated = $request->validate([
            'supplier_type' => 'nullable|in:food,beverage,general',
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = $ownerId;
        $validated['supplier_type'] = $validated['supplier_type'] ?? 'general';
        $validated['is_active'] = true;

        Supplier::create($validated);

        // Redirect back with type filter if it was set
        $redirectRoute = route('bar.suppliers.index');
        if ($validated['supplier_type'] !== 'general') {
            $redirectRoute .= '?type=' . $validated['supplier_type'];
        }

        return redirect($redirectRoute)
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($supplier->user_id !== $ownerId) {
            abort(403, 'You do not have access to this supplier.');
        }

        // Check permission
        if (!$this->hasPermission('suppliers', 'view')) {
            abort(403, 'You do not have permission to view suppliers.');
        }

        // Load related data
        $supplier->load(['products', 'stockReceipts.productVariant.product']);

        return view('bar.suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($supplier->user_id !== $ownerId) {
            abort(403, 'You do not have access to this supplier.');
        }

        // Check permission
        if (!$this->hasPermission('suppliers', 'edit')) {
            abort(403, 'You do not have permission to edit suppliers.');
        }

        return view('bar.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($supplier->user_id !== $ownerId) {
            abort(403, 'You do not have access to this supplier.');
        }

        // Check permission
        if (!$this->hasPermission('suppliers', 'edit')) {
            abort(403, 'You do not have permission to edit suppliers.');
        }

        $validated = $request->validate([
            'supplier_type' => 'nullable|in:food,beverage,general',
            'company_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (!isset($validated['supplier_type'])) {
            $validated['supplier_type'] = 'general';
        }

        $supplier->update($validated);

        return redirect()->route('bar.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($supplier->user_id !== $ownerId) {
            abort(403, 'You do not have access to this supplier.');
        }

        // Check permission
        if (!$this->hasPermission('suppliers', 'delete')) {
            abort(403, 'You do not have permission to delete suppliers.');
        }

        // Check if supplier has products or stock receipts
        if ($supplier->products()->count() > 0 || $supplier->stockReceipts()->count() > 0) {
            return redirect()->route('bar.suppliers.index')
                ->with('error', 'Cannot delete supplier. They have associated products or stock receipts.');
        }

        $supplier->delete();

        return redirect()->route('bar.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
