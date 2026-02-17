<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\IngredientReceipt;
use App\Models\Ingredient;
use App\Models\IngredientBatch;
use App\Models\IngredientStockMovement;
use App\Models\Supplier;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientReceiptController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * List all ingredient receipts
     */
    public function index(Request $request)
    {
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view ingredient receipts.');
        }

        $ownerId = $this->getOwnerId();
        
        $query = IngredientReceipt::where('user_id', $ownerId)
            ->with(['ingredient', 'supplier', 'receivedByStaff'])
            ->orderBy('received_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by ingredient if provided
        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->ingredient_id);
        }

        // Filter by date range if provided
        if ($request->filled('date_from')) {
            $query->where('received_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('received_date', '<=', $request->date_to);
        }

        $receipts = $query->paginate(20);
        $ingredients = Ingredient::where('user_id', $ownerId)->where('is_active', true)->orderBy('name')->get();

        return view('bar.chef.ingredient-receipts.index', compact('receipts', 'ingredients'));
    }

    /**
     * Show form to create ingredient receipt
     */
    public function create()
    {
        if (!$this->hasPermission('inventory', 'create')) {
            abort(403, 'You do not have permission to create ingredient receipts.');
        }

        $ownerId = $this->getOwnerId();
        $ingredients = Ingredient::where('user_id', $ownerId)->where('is_active', true)->orderBy('name')->get();
        // Only show food suppliers and general suppliers (exclude beverage suppliers)
        // Include NULL values (treat as general) for suppliers created before supplier_type was added
        $suppliers = Supplier::where('user_id', $ownerId)
            ->where('is_active', true)
            ->where(function($query) {
                // Include: food, general, or NULL - but NOT beverage
                $query->whereIn('supplier_type', ['food', 'general'])
                      ->orWhereNull('supplier_type');
            })
            ->where(function($query) {
                // Explicitly exclude beverage suppliers
                $query->where('supplier_type', '<>', 'beverage')
                      ->orWhereNull('supplier_type');
            })
            ->orderBy('company_name')
            ->get();

        return view('bar.chef.ingredient-receipts.create', compact('ingredients', 'suppliers'));
    }

    /**
     * Store new ingredient receipt
     */
    public function store(Request $request)
    {
        if (!$this->hasPermission('inventory', 'create')) {
            abort(403, 'You do not have permission to create ingredient receipts.');
        }

        $ownerId = $this->getOwnerId();

        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity_received' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'received_date' => 'required|date',
            'batch_number' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total cost
            $totalCost = $validated['quantity_received'] * $validated['cost_per_unit'];

            // Generate receipt number
            $receiptNumber = IngredientReceipt::generateReceiptNumber($ownerId);

            // Create receipt
            $receipt = IngredientReceipt::create([
                'user_id' => $ownerId,
                'receipt_number' => $receiptNumber,
                'ingredient_id' => $validated['ingredient_id'],
                'supplier_id' => $validated['supplier_id'] ?? null,
                'quantity_received' => $validated['quantity_received'],
                'unit' => $validated['unit'],
                'cost_per_unit' => $validated['cost_per_unit'],
                'total_cost' => $totalCost,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'received_date' => $validated['received_date'],
                'batch_number' => $validated['batch_number'] ?? null,
                'location' => $validated['location'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'received_by' => session('staff_id'),
            ]);

            // Create ingredient batch
            $batch = IngredientBatch::create([
                'user_id' => $ownerId,
                'ingredient_id' => $validated['ingredient_id'],
                'ingredient_receipt_id' => $receipt->id,
                'batch_number' => $validated['batch_number'] ?? $receiptNumber,
                'initial_quantity' => $validated['quantity_received'],
                'remaining_quantity' => $validated['quantity_received'],
                'unit' => $validated['unit'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'received_date' => $validated['received_date'],
                'cost_per_unit' => $validated['cost_per_unit'],
                'location' => $validated['location'] ?? null,
                'status' => 'active',
            ]);

            // Update ingredient stock
            $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
            $ingredient->current_stock += $validated['quantity_received'];
            $ingredient->save();

            // Create stock movement record
            IngredientStockMovement::create([
                'user_id' => $ownerId,
                'ingredient_id' => $validated['ingredient_id'],
                'ingredient_batch_id' => $batch->id,
                'movement_type' => 'receipt',
                'quantity' => $validated['quantity_received'],
                'unit' => $validated['unit'],
                'from_location' => null,
                'to_location' => $validated['location'] ?? 'kitchen',
                'reference_type' => IngredientReceipt::class,
                'reference_id' => $receipt->id,
                'notes' => "Receipt: {$receiptNumber}",
                'created_by' => session('staff_id'),
            ]);

            DB::commit();

            return redirect()->route('bar.chef.ingredient-receipts')
                ->with('success', 'Ingredient receipt created successfully. Stock has been updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create ingredient receipt: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Show ingredient receipt details
     */
    public function show(IngredientReceipt $receipt)
    {
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view ingredient receipts.');
        }

        $ownerId = $this->getOwnerId();
        if ($receipt->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        $receipt->load(['ingredient', 'supplier', 'receivedByStaff', 'batches']);

        return view('bar.chef.ingredient-receipts.show', compact('receipt'));
    }
}
