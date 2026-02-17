<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\BarTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    use HandlesStaffPermissions;
    /**
     * Display a listing of tables.
     */
    public function index()
    {
        // Check permission (handles both users and staff)
        if (!$this->hasPermission('bar_tables', 'view')) {
            abort(403, 'You do not have permission to view tables.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        $tables = BarTable::where('user_id', $ownerId)
            ->orderBy('table_number')
            ->get();

        return view('bar.tables.index', compact('tables'));
    }

    /**
     * Show the form for creating a new table.
     */
    public function create()
    {
        // Check permission
        if (!$this->hasPermission('bar_tables', 'create')) {
            abort(403, 'You do not have permission to create tables.');
        }

        return view('bar.tables.create');
    }

    /**
     * Store a newly created table.
     */
    public function store(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_tables', 'create')) {
            abort(403, 'You do not have permission to create tables.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        $validated = $request->validate([
            'table_number' => 'required|string|max:50|unique:bar_tables,table_number,NULL,id,user_id,' . $ownerId,
            'table_name' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['user_id'] = $ownerId;
        $validated['is_active'] = $validated['is_active'] ?? true;

        BarTable::create($validated);

        return redirect()->route('bar.tables.index')
            ->with('success', 'Table created successfully.');
    }

    /**
     * Display the specified table.
     */
    public function show(BarTable $table)
    {
        // Check permission (handles both users and staff)
        if (!$this->hasPermission('bar_tables', 'view')) {
            abort(403, 'You do not have permission to view tables.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Verify table belongs to owner
        if ($table->user_id !== $ownerId) {
            abort(403, 'You do not have permission to view this table.');
        }

        // Load related orders
        $table->load(['orders' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]);

        return view('bar.tables.show', compact('table'));
    }

    /**
     * Show the form for editing the specified table.
     */
    public function edit(BarTable $table)
    {
        // Check permission
        if (!$this->hasPermission('bar_tables', 'edit')) {
            abort(403, 'You do not have permission to edit tables.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Verify table belongs to owner
        if ($table->user_id !== $ownerId) {
            abort(403, 'You do not have permission to edit this table.');
        }

        return view('bar.tables.edit', compact('table'));
    }

    /**
     * Update the specified table.
     */
    public function update(Request $request, BarTable $table)
    {
        // Check permission
        if (!$this->hasPermission('bar_tables', 'edit')) {
            abort(403, 'You do not have permission to edit tables.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Verify table belongs to owner
        if ($table->user_id !== $ownerId) {
            abort(403, 'You do not have permission to edit this table.');
        }

        $validated = $request->validate([
            'table_number' => 'required|string|max:50|unique:bar_tables,table_number,' . $table->id . ',id,user_id,' . $ownerId,
            'table_name' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $table->update($validated);

        return redirect()->route('bar.tables.index')
            ->with('success', 'Table updated successfully.');
    }

    /**
     * Remove the specified table.
     */
    public function destroy(BarTable $table)
    {
        // Check permission
        if (!$this->hasPermission('bar_tables', 'delete')) {
            abort(403, 'You do not have permission to delete tables.');
        }

        // Get owner ID (for staff, get their owner)
        $ownerId = $this->getOwnerId();

        // Verify table belongs to owner
        if ($table->user_id !== $ownerId) {
            abort(403, 'You do not have permission to delete this table.');
        }

        // Check if table has active orders
        if ($table->activeOrders()->count() > 0) {
            return redirect()->route('bar.tables.index')
                ->with('error', 'Cannot delete table with active orders. Please complete or cancel all orders first.');
        }

        $table->delete();

        return redirect()->route('bar.tables.index')
            ->with('success', 'Table deleted successfully.');
    }
}
