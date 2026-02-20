<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\StockTransfer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Services\StockTransferSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransferController extends Controller
{
    use HandlesStaffPermissions;
    /**
     * Display a listing of stock transfers.
     */
    public function index()
    {
        // Check permission - allow both stock_transfer and inventory permissions, or counter/stock keeper roles
        $canView = $this->hasPermission('stock_transfer', 'view') || $this->hasPermission('inventory', 'view');
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canView && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canView = true;
                }
            }
        }
        
        if (!$canView) {
            abort(403, 'You do not have permission to view stock transfers.');
        }

        $ownerId = $this->getOwnerId();
        $transfers = StockTransfer::where('user_id', $ownerId)
            ->with(['productVariant.product', 'productVariant.counterStock', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->orderBy('transfer_number', 'desc')
            ->paginate(20);

        // Check for batch success flag from available page
        if (request()->has('batch_success')) {
            session()->flash('success', 'Batch transfer request sent successfully. All items are waiting for approval.');
        }

        // Calculate expected revenue and profit for all transfers
        $transfers->getCollection()->transform(function($transfer) use ($ownerId) {
            $financials = $transfer->calculateFinancials();
            $transfer->expected_revenue = $financials['revenue'];
            $transfer->expected_profit = $financials['profit'];
            $transfer->is_tot_calculation = $financials['is_tot'];

            if ($transfer->status === 'completed' && $transfer->productVariant) {
                // Calculate real-time generated profit (from paid orders after transfer completion)
                $sellingPrice = $financials['selling_price'];
                $buyingPrice = $financials['buying_price'];
                $transfer->real_time_profit = $this->calculateRealTimeProfit($transfer, $ownerId, $sellingPrice, $buyingPrice);
                $revenueData = $this->calculateRealTimeRevenue($transfer, $ownerId);
                $transfer->real_time_revenue = $revenueData['total'];
                $transfer->real_time_revenue_recorded = $revenueData['recorded'];
                $transfer->real_time_revenue_submitted = $revenueData['submitted'];
                $transfer->real_time_revenue_pending = $revenueData['pending'];
            } else {
                $transfer->real_time_profit = 0;
                $transfer->real_time_revenue = 0;
            }
            
            return $transfer;
        });

        return view('bar.stock-transfers.index', compact('transfers'));
    }

    /**
     * Display available products from warehouse in card layout.
     */
    public function available()
    {
        // Check permission - allow both stock_transfer and inventory permissions, or counter/stock keeper roles
        $canView = $this->hasPermission('stock_transfer', 'view') || $this->hasPermission('inventory', 'view');
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canView && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canView = true;
                }
            }
        }
        
        if (!$canView) {
            abort(403, 'You do not have permission to view available products.');
        }
        
        $ownerId = $this->getOwnerId();

        // Get products with variants that have warehouse stock
        $products = Product::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with(['variants' => function($query) use ($ownerId) {
                $query->whereHas('warehouseStock', function($q) use ($ownerId) {
                    $q->where('user_id', $ownerId)
                      ->where('quantity', '>', 0);
                })->with(['warehouseStock' => function($q) use ($ownerId) {
                    $q->where('user_id', $ownerId)
                      ->where('location', 'warehouse');
                }]);
            }])
            ->whereHas('variants.warehouseStock', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)
                      ->where('quantity', '>', 0);
            })
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Create a unified list of Categories and Brands for the Tab Bar
        $quickFilters = $products->pluck('category')
            ->merge($products->pluck('brand'))
            ->unique()
            ->filter()
            ->values()
            ->sort()
            ->all();

        // Separate Brands for the dropdown still (if needed for fallback)
        $brands = $products->pluck('brand')->unique()->filter()->values()->all();
        $categories = $products->pluck('category')->unique()->filter()->values()->all();

        // Process products and variants into a flat list of individual inventory items
        $inventoryItems = $products->flatMap(function($product) {
            return $product->variants->filter(function($variant) {
                return $variant->warehouseStock && $variant->warehouseStock->quantity > 0;
            })->map(function($variant) use ($product) {
                $warehouseStock = $variant->warehouseStock;
                $warehousePackages = floor($warehouseStock->quantity / $variant->items_per_package);
                
                return [
                    'variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant->name,
                    'brand' => $product->brand,
                    'category' => $product->category,
                    'description' => $product->description,
                    'image' => $product->image,
                    'measurement' => $variant->measurement,
                    'packaging' => $variant->packaging,
                    'unit' => $variant->unit,
                    'items_per_package' => $variant->items_per_package,
                    'warehouse_quantity' => $warehouseStock->quantity,
                    'warehouse_packages' => $warehousePackages,
                    'average_buying_price' => $warehouseStock->average_buying_price,
                    'selling_price' => $warehouseStock->selling_price,
                    'unit_label' => $variant->unit ?? 'btl',
                    'can_sell_in_tots' => $variant->can_sell_in_tots,
                    'total_tots_per_unit' => $variant->total_tots,
                    'selling_price_per_tot' => $variant->selling_price_per_tot,
                ];
            });
        })->values();

        return view('bar.stock-transfers.available', compact('inventoryItems', 'categories', 'brands', 'quickFilters'));
    }

    /**
     * Show the form for creating a new stock transfer.
     */
    public function create()
    {
        // Check permission - allow stock_transfer create or inventory edit, or counter/stock keeper roles
        $canCreate = $this->hasPermission('stock_transfer', 'create') || $this->hasPermission('inventory', 'edit');
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canCreate && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canCreate = true;
                }
            }
        }
        
        if (!$canCreate) {
            abort(403, 'You do not have permission to create stock transfers.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get products with variants that have warehouse stock
        $products = Product::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with(['variants' => function($query) use ($ownerId) {
                $query->whereHas('warehouseStock', function($q) use ($ownerId) {
                    $q->where('user_id', $ownerId)
                      ->where('quantity', '>', 0);
                });
            }])
            ->whereHas('variants.warehouseStock', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)
                      ->where('quantity', '>', 0);
            })
            ->orderBy('name')
            ->get();

        // Prepare products data for JavaScript
        $productsData = $products->map(function($product) use ($ownerId) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'brand' => $product->brand,
                'image' => $product->image,
                'category' => $product->category,
                'description' => $product->description,
                'variants' => $product->variants->map(function($variant) use ($ownerId, $product) {
                    $warehouseStock = $variant->warehouseStock()->where('user_id', $ownerId)->first();
                    return [
                        'id' => $variant->id,
                        'product_id' => $product->id,
                        'measurement' => $variant->measurement,
                        'packaging' => $variant->packaging,
                        'items_per_package' => $variant->items_per_package,
                        'warehouse_quantity' => $warehouseStock ? $warehouseStock->quantity : 0,
                        'warehouse_packages' => $warehouseStock ? floor($warehouseStock->quantity / $variant->items_per_package) : 0,
                    ];
                })->filter(function($variant) {
                    return $variant['warehouse_quantity'] > 0;
                })->values()->all()
            ];
        })->filter(function($product) {
            return count($product['variants']) > 0;
        })->values()->all();

        return view('bar.stock-transfers.create', compact('products', 'productsData'));
    }

    /**
     * Store a newly created stock transfer.
     */
    public function store(Request $request)
    {
        // Check permission - allow stock_transfer create or inventory edit, or counter/stock keeper roles
        $canCreate = $this->hasPermission('stock_transfer', 'create') || $this->hasPermission('inventory', 'edit');
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canCreate && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canCreate = true;
                }
            }
        }
        
        if (!$canCreate) {
            abort(403, 'You do not have permission to create stock transfers.');
        }

        $ownerId = $this->getOwnerId();

        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity_requested' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        // Verify product variant belongs to user
        $productVariant = ProductVariant::where('id', $validated['product_variant_id'])
            ->whereHas('product', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })
            ->first();

        if (!$productVariant) {
            return back()->withErrors(['product_variant_id' => 'Invalid product variant selected.'])->withInput();
        }

        // Check warehouse stock availability
        $warehouseStock = StockLocation::where('user_id', $ownerId)
            ->where('product_variant_id', $productVariant->id)
            ->where('location', 'warehouse')
            ->first();

        if (!$warehouseStock || $warehouseStock->quantity < 1) {
            return back()->withErrors(['product_variant_id' => 'No stock available in warehouse for this product variant.'])->withInput();
        }

        $totalUnits = $validated['quantity_requested'] * $productVariant->items_per_package;
        
        // Check if requested quantity exceeds available stock
        if ($totalUnits > $warehouseStock->quantity) {
            $availablePackages = floor($warehouseStock->quantity / $productVariant->items_per_package);
            return back()->withErrors([
                'quantity_requested' => "Insufficient stock. Only {$availablePackages} package(s) available in warehouse ({$warehouseStock->quantity} units)."
            ])->withInput();
        }

        // Determine who is making the request
        $requestedById = $ownerId; // Default to owner
        if (session('is_staff') && session('staff_id')) {
            // If it's a staff member, get their user ID (staff table has user_id)
            $staff = \App\Models\Staff::find(session('staff_id'));
            if ($staff && $staff->user_id) {
                $requestedById = $staff->user_id;
            }
        } elseif (Auth::check()) {
            // If it's a logged-in user (not staff), use their ID
            $requestedById = Auth::id();
        }

        DB::beginTransaction();
        try {
            // Generate transfer number
            $transferNumber = StockTransfer::generateTransferNumber($ownerId);

            // Create stock transfer (pending status)
            $transfer = StockTransfer::create([
                'user_id' => $ownerId,
                'product_variant_id' => $validated['product_variant_id'],
                'transfer_number' => $transferNumber,
                'quantity_requested' => $validated['quantity_requested'],
                'total_units' => $totalUnits,
                'status' => 'pending',
                'requested_by' => $requestedById,
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            // Reload transfer with relationships for SMS
            $transfer->load(['productVariant.product', 'productVariant']);

            // Send SMS notification to stock keeper
            try {
                \Log::info('Attempting to send stock transfer SMS notification', [
                    'transfer_id' => $transfer->id,
                    'owner_id' => $ownerId,
                    'transfer_number' => $transfer->transfer_number
                ]);
                
                $smsService = new StockTransferSmsService();
                $result = $smsService->sendTransferRequestNotification($transfer, $ownerId);
                
                \Log::info('Stock transfer SMS notification attempt completed', [
                    'transfer_id' => $transfer->id,
                    'result' => $result ? 'true' : 'false',
                    'owner_id' => $ownerId,
                    'transfer_number' => $transfer->transfer_number
                ]);
            } catch (\Exception $smsException) {
                // Log SMS error but don't fail the transaction
                \Log::error('Failed to send stock transfer request SMS notification', [
                    'transfer_id' => $transfer->id,
                    'owner_id' => $ownerId,
                    'error' => $smsException->getMessage(),
                    'file' => $smsException->getFile(),
                    'line' => $smsException->getLine(),
                    'trace' => $smsException->getTraceAsString()
                ]);
            }

            return redirect()->route('bar.stock-transfers.index')
                ->with('success', 'Stock transfer request created successfully. Waiting for approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Stock transfer creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create stock transfer: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Store a batch of stock transfer requests.
     */
    public function batchStore(Request $request)
    {
        // Check permission
        $canCreate = $this->hasPermission('stock_transfer', 'create') || $this->hasPermission('inventory', 'edit');
        if (!$canCreate && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canCreate = true;
                }
            }
        }
        
        if (!$canCreate) {
            return response()->json(['error' => 'You do not have permission to request transfers.'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity_requested' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $ownerId = $this->getOwnerId();
        $requestedById = Auth::id() ?? $ownerId;

        // Determine who is making the request (staff check)
        if (session('is_staff') && session('staff_id')) {
            $staff = \App\Models\Staff::find(session('staff_id'));
            if ($staff && $staff->user_id) {
                $requestedById = $staff->user_id;
            }
        }

        DB::beginTransaction();
        try {
            $transferNumber = StockTransfer::generateTransferNumber($ownerId);
            $createdTransfers = [];

            foreach ($validated['items'] as $itemData) {
                $variant = ProductVariant::where('id', $itemData['product_variant_id'])->first();
                if (!$variant) continue;

                $totalUnits = $itemData['quantity_requested'] * ($variant->items_per_package ?? 1);

                $transfer = StockTransfer::create([
                    'user_id' => $ownerId,
                    'product_variant_id' => $variant->id,
                    'transfer_number' => $transferNumber,
                    'quantity_requested' => $itemData['quantity_requested'],
                    'total_units' => $totalUnits,
                    'status' => 'pending',
                    'requested_by' => $requestedById,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $createdTransfers[] = $transfer;
            }

            DB::commit();

            // Send batch SMS notification
            try {
                $smsService = new StockTransferSmsService();
                $smsService->sendBatchTransferRequestNotification($createdTransfers, $ownerId, $transferNumber);
            } catch (\Exception $e) {
                \Log::error('Batch SMS notification failed: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Batch transfer request submitted successfully.',
                'transfer_number' => $transferNumber
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process batch: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Display the specified stock transfer.
     */
    public function show(StockTransfer $stockTransfer)
    {
        $ownerId = $this->getOwnerId();
        
        // Check if current user is accountant (can view any transfer)
        $currentStaff = $this->getCurrentStaff();
        $isAccountant = false;
        if ($currentStaff) {
            $currentStaff->load('role');
            $isAccountant = strtolower($currentStaff->role->name ?? '') === 'accountant';
        }
        
        // Check ownership (unless accountant)
        if (!$isAccountant && $stockTransfer->user_id !== $ownerId) {
            abort(403, 'You do not have access to this stock transfer.');
        }

        // Check permission - allow stock_transfer, inventory, finance, or reports permissions, or accountant role
        $canView = $this->hasPermission('stock_transfer', 'view') || 
                   $this->hasPermission('inventory', 'view') ||
                   $this->hasPermission('finance', 'view') ||
                   $this->hasPermission('reports', 'view');
        
        // Allow accountant role even without explicit permission
        if (!$canView && $isAccountant) {
            $canView = true;
        }
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canView && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canView = true;
                }
            }
        }
        
        if (!$canView) {
            abort(403, 'You do not have permission to view stock transfers.');
        }

        $stockTransfer->load(['productVariant.product', 'productVariant.counterStock', 'requestedBy', 'approvedBy', 'verifiedBy']);

        // Calculate expected revenue for completed transfers
        $expectedRevenue = null;
        if ($stockTransfer->status === 'completed' && $stockTransfer->productVariant) {
            $counterStock = StockLocation::where('user_id', $ownerId)
                ->where('product_variant_id', $stockTransfer->product_variant_id)
                ->where('location', 'counter')
                ->first();
            
            if ($counterStock && $counterStock->quantity > 0 && $counterStock->selling_price > 0) {
                $expectedRevenue = $counterStock->quantity * $counterStock->selling_price;
            } else {
                $warehouseStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $stockTransfer->product_variant_id)
                    ->where('location', 'warehouse')
                    ->first();
                
                $sellingPrice = $warehouseStock->selling_price ?? $stockTransfer->productVariant->selling_price_per_unit ?? 0;
                $expectedRevenue = $stockTransfer->total_units * $sellingPrice;
            }
        }

        // Return JSON response for AJAX requests
        if (request()->ajax() || request()->wantsJson()) {
            // Fetch all items in this batch
            $batchItems = StockTransfer::where('transfer_number', $stockTransfer->transfer_number)
                ->with(['productVariant.product'])
                ->get();

            $formattedBatchItems = $batchItems->map(function($item) {
                $pkg = strtolower($item->productVariant->packaging ?? 'packages');
                $pkgSingular = rtrim($pkg, 's');
                if ($pkgSingular == 'boxe') $pkgSingular = 'box';
                $pkgDisplay = $item->quantity_requested == 1 ? $pkgSingular : $pkg;

                $unit = strtolower($item->productVariant->unit ?? 'btl');
                if (in_array($unit, ['ml', 'cl', 'l'])) $unit = 'bottle';
                $unitDisplay = $item->total_units == 1 ? $unit : Str::plural($unit);

                $financials = $item->calculateFinancials();

                return [
                    'id' => $item->id,
                    'product_name' => $item->productVariant->product->name ?? 'N/A',
                    'variant_measurement' => $item->productVariant->measurement ?? null,
                    'quantity_requested' => $item->quantity_requested,
                    'packaging_display' => $pkgDisplay,
                    'total_units' => $item->total_units,
                    'unit_display' => $unitDisplay,
                    'expected_profit' => $financials['profit'],
                    'expected_revenue' => $financials['revenue'],
                    'is_tot' => $financials['is_tot']
                ];
            });

            return response()->json([
                'success' => true,
                'is_batch' => $batchItems->count() > 1,
                'batch_items' => $formattedBatchItems,
                'transfer' => [
                    'id' => $stockTransfer->id,
                    'transfer_number' => $stockTransfer->transfer_number,
                    'status' => $stockTransfer->status,
                    'notes' => $stockTransfer->notes,
                    'rejection_reason' => $stockTransfer->rejection_reason,
                    'created_at' => $stockTransfer->created_at ? $stockTransfer->created_at->format('M d, Y H:i') : null,
                    'approved_at' => $stockTransfer->approved_at ? $stockTransfer->approved_at->format('M d, Y H:i') : null,
                    'completed_date' => $stockTransfer->updated_at ? $stockTransfer->updated_at->format('M d, Y H:i') : null,
                    'requested_by_name' => $stockTransfer->requestedBy ? ($stockTransfer->requestedBy->name ?? 'N/A') : 'N/A',
                    'approved_by_name' => $stockTransfer->approvedBy ? ($stockTransfer->approvedBy->name ?? 'N/A') : 'N/A',
                    'verified_by' => $stockTransfer->verifiedBy ? $stockTransfer->verifiedBy->name : null,
                    'verified_at' => $stockTransfer->verified_at ? $stockTransfer->verified_at->format('M d, Y H:i') : null,
                ]
            ]);
        }

        return view('bar.stock-transfers.show', compact('stockTransfer', 'expectedRevenue'));
    }

    /**
     * Approve a stock transfer.
     */
    public function approve(StockTransfer $stockTransfer)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($stockTransfer->user_id !== $ownerId) {
            abort(403, 'You do not have access to this stock transfer.');
        }

        // Check permission - only stock keepers can approve
        $canApprove = $this->hasPermission('stock_transfer', 'edit');
        
        // Allow stock keeper role even without explicit permission
        if (!$canApprove && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['stock keeper', 'stockkeeper'])) {
                    $canApprove = true;
                }
            }
        }
        
        // Block counter staff from approving
        if (!$canApprove && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter'])) {
                    abort(403, 'Only stock keepers can approve stock transfers.');
                }
            }
        }
        
        if (!$canApprove) {
            abort(403, 'You do not have permission to approve stock transfers.');
        }

        // Check if already processed
        if ($stockTransfer->status !== 'pending') {
            return back()->withErrors(['error' => 'This transfer has already been processed.']);
        }

        // Fetch all transfers in this batch
        $batchItems = StockTransfer::where('transfer_number', $stockTransfer->transfer_number)->get();

        DB::beginTransaction();
        try {
            foreach ($batchItems as $item) {
                // Check warehouse stock availability for each item
                $warehouseStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->where('location', 'warehouse')
                    ->first();

                if (!$warehouseStock || $warehouseStock->quantity < $item->total_units) {
                    throw new \Exception("Insufficient stock in warehouse for " . ($item->productVariant->product->name ?? 'one of the items') . ".");
                }

                $item->update([
                    'status' => 'approved',
                    'approved_by' => $ownerId,
                    'approved_at' => now(),
                ]);
            }

            DB::commit();

            // Send batch SMS notification
            try {
                $smsService = new StockTransferSmsService();
                $smsService->sendBatchTransferStatusNotification($batchItems, 'approved', $ownerId);
            } catch (\Exception $smsException) {
                \Log::error('Failed to send batch stock transfer approval SMS notification: ' . $smsException->getMessage());
            }

            return redirect()->route('bar.stock-transfers.index')
                ->with('success', 'Stock transfer batch approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Batch stock transfer approval failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to approve batch: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a stock transfer.
     */
    public function reject(StockTransfer $stockTransfer)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($stockTransfer->user_id !== $ownerId) {
            abort(403, 'You do not have access to this stock transfer.');
        }

        // Check permission - only stock keepers can reject
        $canReject = $this->hasPermission('stock_transfer', 'edit');
        
        // Allow stock keeper role even without explicit permission
        if (!$canReject && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['stock keeper', 'stockkeeper'])) {
                    $canReject = true;
                }
            }
        }
        
        // Block counter staff from rejecting
        if (!$canReject && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter'])) {
                    abort(403, 'Only stock keepers can reject stock transfers.');
                }
            }
        }
        
        if (!$canReject) {
            abort(403, 'You do not have permission to reject stock transfers.');
        }

        // Fetch all transfers in this batch
        $batchItems = StockTransfer::where('transfer_number', $stockTransfer->transfer_number)->get();

        DB::beginTransaction();
        try {
            foreach ($batchItems as $item) {
                $item->update([
                    'status' => 'rejected',
                    'approved_by' => $ownerId,
                    'approved_at' => now(),
                ]);
            }

            DB::commit();

            // Send batch SMS notification
            try {
                $smsService = new StockTransferSmsService();
                $smsService->sendBatchTransferStatusNotification($batchItems, 'rejected', $ownerId);
            } catch (\Exception $smsException) {
                \Log::error('Failed to send batch stock transfer rejection SMS notification: ' . $smsException->getMessage());
            }

            return redirect()->route('bar.stock-transfers.index')
                ->with('success', 'Stock transfer batch rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to reject batch: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark stock transfer as prepared.
     */
    public function markAsPrepared(StockTransfer $stockTransfer)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($stockTransfer->user_id !== $ownerId) {
            abort(403, 'You do not have access to this stock transfer.');
        }

        // Check permission
        if (!$this->hasPermission('stock_transfer', 'edit')) {
            abort(403, 'You do not have permission to mark transfers as prepared.');
        }

        // Fetch all transfers in this batch
        $batchItems = StockTransfer::where('transfer_number', $stockTransfer->transfer_number)->get();

        DB::beginTransaction();
        try {
            foreach ($batchItems as $item) {
                if ($item->status === 'approved') {
                    $item->update([
                        'status' => 'prepared',
                    ]);
                }
            }

            DB::commit();

            // Send batch SMS notification
            try {
                $smsService = new StockTransferSmsService();
                $smsService->sendBatchTransferStatusNotification($batchItems, 'prepared', $ownerId);
            } catch (\Exception $smsException) {
                \Log::error('Failed to send batch stock transfer prepared SMS notification: ' . $smsException->getMessage());
            }

            return redirect()->route('bar.stock-transfers.index')
                ->with('success', 'Batch transfer marked as prepared successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Mark as prepared failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to mark transfer as prepared: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark stock transfer as moved (completed).
     */
    public function markAsMoved(StockTransfer $stockTransfer)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($stockTransfer->user_id !== $ownerId) {
            abort(403, 'You do not have access to this stock transfer.');
        }

        // Check permission
        if (!$this->hasPermission('stock_transfer', 'edit')) {
            abort(403, 'You do not have permission to mark transfers as moved.');
        }

        // Fetch all transfers in this batch
        $batchItems = StockTransfer::where('transfer_number', $stockTransfer->transfer_number)->get();

        DB::beginTransaction();
        try {
            foreach ($batchItems as $item) {
                // Skip if not approved (safety check)
                if ($item->status !== 'approved' && $item->status !== 'prepared') continue;

                $warehouseStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->where('location', 'warehouse')
                    ->first();

                if (!$warehouseStock || $warehouseStock->quantity < $item->total_units) {
                    throw new \Exception("Insufficient warehouse stock for " . ($item->productVariant->product->name ?? 'one of the items') . ".");
                }

                // Get or create counter stock location
                $counterStock = StockLocation::firstOrCreate(
                    [
                        'user_id' => $ownerId,
                        'product_variant_id' => $item->product_variant_id,
                        'location' => 'counter',
                    ],
                    [
                        'quantity' => 0,
                        'average_buying_price' => $warehouseStock->average_buying_price,
                        'selling_price' => $warehouseStock->selling_price,
                        'selling_price_per_tot' => $warehouseStock->selling_price_per_tot,
                    ]
                );

                // Deduct from warehouse
                $warehouseStock->decrement('quantity', $item->total_units);

                // Weighted Average Costing for Counter
                $existingCounterQty = $counterStock->quantity;
                $currentCounterAve = $counterStock->average_buying_price;
                $incomingQty = $item->total_units;
                $warehouseAve = $warehouseStock->average_buying_price;

                $newCounterAve = ($existingCounterQty + $incomingQty) > 0 
                    ? (($existingCounterQty * $currentCounterAve) + ($incomingQty * $warehouseAve)) / ($existingCounterQty + $incomingQty)
                    : $warehouseAve;

                // Add to counter and update prices
                $counterStock->update([
                    'quantity' => $existingCounterQty + $incomingQty,
                    'average_buying_price' => $newCounterAve,
                    'selling_price' => $warehouseStock->selling_price,
                    'selling_price_per_tot' => $warehouseStock->selling_price_per_tot,
                ]);

                // Update transfer status
                $item->update(['status' => 'completed']);

                // Record stock movement
                StockMovement::create([
                    'user_id' => $ownerId,
                    'product_variant_id' => $item->product_variant_id,
                    'movement_type' => 'transfer',
                    'from_location' => 'warehouse',
                    'to_location' => 'counter',
                    'quantity' => $item->total_units,
                    'unit_price' => $warehouseStock->average_buying_price,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $item->id,
                    'created_by' => $ownerId,
                    'notes' => 'Stock moved from warehouse to counter (Batch)',
                ]);
            }

            DB::commit();

            // Send batch SMS notification
            try {
                $smsService = new StockTransferSmsService();
                $smsService->sendBatchTransferStatusNotification($batchItems, 'completed', $ownerId);
            } catch (\Exception $smsException) {
                \Log::error('Failed to send batch stock transfer completion SMS notification: ' . $smsException->getMessage());
            }

            return redirect()->route('bar.stock-transfers.index')
                ->with('success', 'Batch transfer completed. All items have been moved to counter stock.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Batch mark as moved failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to complete batch movement: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a stock transfer with reason.
     */
    public function rejectWithReason(Request $request, StockTransfer $stockTransfer)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($stockTransfer->user_id !== $ownerId) {
            abort(403, 'You do not have access to this stock transfer.');
        }

        // Check permission - only stock keepers can reject
        $canReject = $this->hasPermission('stock_transfer', 'edit');
        
        // Allow stock keeper role even without explicit permission
        if (!$canReject && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['stock keeper', 'stockkeeper'])) {
                    $canReject = true;
                }
            }
        }
        
        // Block counter staff from rejecting
        if (!$canReject && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter'])) {
                    abort(403, 'Only stock keepers can reject stock transfers.');
                }
            }
        }
        
        if (!$canReject) {
            abort(403, 'You do not have permission to reject stock transfers.');
        }

        // Check if already processed
        if ($stockTransfer->status !== 'pending') {
            return back()->withErrors(['error' => 'This transfer has already been processed.']);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $stockTransfer->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'approved_by' => $ownerId,
                'approved_at' => now(),
            ]);

            DB::commit();

            // Send SMS notification to counter staff
            try {
                $smsService = new StockTransferSmsService();
                $smsService->sendTransferStatusNotification($stockTransfer, 'rejected', $ownerId, $validated['rejection_reason']);
            } catch (\Exception $smsException) {
                \Log::error('Failed to send stock transfer rejection SMS notification: ' . $smsException->getMessage());
            }

            return redirect()->route('bar.stock-transfers.index')
                ->with('success', 'Stock transfer rejected successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Reject transfer failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to reject transfer: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Calculate real-time profit for a completed stock transfer.
     * This calculates profit from recorded payments (OrderPayments) that contain items from this transfer.
     */
    private function calculateRealTimeProfit($transfer, $ownerId, $sellingPrice, $buyingPrice)
    {
        if ($transfer->status !== 'completed' || !$transfer->productVariant) {
            return 0;
        }

        // Use approved_at as completion date (when transfer was approved and moved to counter)
        // If not available, use created_at as fallback
        $completedDate = $transfer->approved_at ?? $transfer->created_at;
        
        // Find all order items from this product variant created after transfer completion
        $orderItems = \App\Models\OrderItem::where('product_variant_id', $transfer->product_variant_id)
            ->whereHas('order', function($query) use ($ownerId, $completedDate) {
                $query->where('user_id', $ownerId)
                      ->where('created_at', '>=', $completedDate);
            })
            ->with(['order.orderPayments'])
            ->get();

        $totalProfit = 0;
        foreach ($orderItems as $item) {
            $order = $item->order;
            
            // Check if order has recorded payments (OrderPayments)
            if ($order && $order->orderPayments && $order->orderPayments->count() > 0) {
                // Get total recorded payments for this order
                $recordedPayments = $order->orderPayments->sum('amount');
                $orderTotal = $order->items->sum('total_price');
                
                if ($orderTotal > 0) {
                    // Calculate the proportion of recorded payments
                    $paymentRatio = min(1, $recordedPayments / $orderTotal); // Cap at 1 (100%)
                    
                    // Calculate profit: (selling price - buying price) * quantity * payment ratio
                    $itemProfit = ($item->unit_price - $buyingPrice) * $item->quantity * $paymentRatio;
                    $totalProfit += $itemProfit;
                }
            }
        }

        return $totalProfit;
    }

    /**
     * Calculate real-time revenue for a completed stock transfer.
     * Returns array with 'recorded', 'submitted', 'pending', and 'total' amounts.
     */
    private function calculateRealTimeRevenue($transfer, $ownerId)
    {
        if ($transfer->status !== 'completed' || !$transfer->productVariant) {
            return [
                'recorded' => 0,
                'submitted' => 0,
                'pending' => 0,
                'total' => 0
            ];
        }

        // Use approved_at as completion date (when transfer was approved and moved to counter)
        // If not available, use created_at as fallback
        $completedDate = $transfer->approved_at ?? $transfer->created_at;
        
        // Get all order items matching this transfer's product variant
        $orderItems = \App\Models\OrderItem::where('product_variant_id', $transfer->product_variant_id)
            ->whereHas('order', function($query) use ($ownerId, $completedDate) {
                $query->where('user_id', $ownerId)
                      ->where('created_at', '>=', $completedDate);
            })
            ->with(['order.orderPayments', 'order.reconciliation'])
            ->get();

        // Calculate recorded amount: Sum of all OrderPayment amounts (both pending and verified)
        $recordedAmount = 0;
        $orderIds = $orderItems->pluck('order_id')->unique();
        
        foreach ($orderIds as $orderId) {
            $order = \App\Models\BarOrder::with('orderPayments')->find($orderId);
            if ($order) {
                // Sum all OrderPayments for this order (both pending and verified)
                $recordedAmount += $order->orderPayments->sum('amount');
            }
        }

        // Calculate submitted amount: From WaiterDailyReconciliation records
        $submittedAmount = 0;
        
        // Group order items by order_id and waiter_id to handle reconciliations
        $ordersByWaiterAndDate = $orderItems->groupBy(function($item) {
            $order = $item->order;
            if ($order && $order->waiter_id && $order->created_at) {
                return $order->waiter_id . '_' . $order->created_at->format('Y-m-d');
            }
            return 'no_waiter';
        });

        foreach ($ordersByWaiterAndDate as $key => $items) {
            if ($key === 'no_waiter') continue;
            
            list($waiterId, $date) = explode('_', $key, 2);
            
            // Get reconciliation for this waiter on this date
            $reconciliation = \App\Models\WaiterDailyReconciliation::where('waiter_id', $waiterId)
                ->where('reconciliation_date', $date)
                ->where('user_id', $ownerId)
                ->first();
            
            if ($reconciliation && in_array($reconciliation->status, ['submitted', 'partial', 'verified'])) {
                // Get all bar orders for this waiter on this date
                $waiterOrders = \App\Models\BarOrder::where('waiter_id', $waiterId)
                    ->whereDate('created_at', $date)
                    ->where('user_id', $ownerId)
                    ->with('items')
                    ->get();
                
                // Calculate total bar items value for this waiter on this date
                $totalBarItemsValue = $waiterOrders->sum(function($o) {
                    return $o->items->sum('total_price');
                });
                
                if ($totalBarItemsValue > 0 && $reconciliation->expected_amount > 0) {
                    // Calculate total value of items matching this transfer for this waiter/date
                    $transferItemsValue = $items->sum('total_price');
                    
                    // Calculate submission ratio (how much was actually submitted vs expected)
                    $submissionRatio = $reconciliation->submitted_amount / $reconciliation->expected_amount;
                    
                    // Apply submission ratio to transfer items value
                    $submittedAmount += $transferItemsValue * $submissionRatio;
                }
            }
        }

        $pendingAmount = max(0, $recordedAmount - $submittedAmount);
        $totalAmount = $recordedAmount; // Total = all recorded payments

        return [
            'recorded' => $recordedAmount,
            'submitted' => $submittedAmount,
            'pending' => $pendingAmount,
            'total' => $totalAmount
        ];
    }

    /**
     * API endpoint to get real-time profit for stock transfers.
     */
    public function getRealTimeProfit(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('stock_transfer', 'view')) {
            return response()->json(['error' => 'You do not have permission to view stock transfers.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $transferIds = $request->input('transfer_ids', []);

        if (empty($transferIds)) {
            return response()->json(['error' => 'No transfer IDs provided'], 400);
        }

        $transfers = StockTransfer::where('user_id', $ownerId)
            ->whereIn('id', $transferIds)
            ->with('productVariant')
            ->get();

        $results = [];
        foreach ($transfers as $transfer) {
            if ($transfer->status === 'completed' && $transfer->productVariant) {
                $counterStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $transfer->product_variant_id)
                    ->where('location', 'counter')
                    ->first();
                
                $warehouseStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $transfer->product_variant_id)
                    ->where('location', 'warehouse')
                    ->first();
                
                $sellingPrice = $counterStock->selling_price ?? $warehouseStock->selling_price ?? $transfer->productVariant->selling_price_per_unit ?? 0;
                $buyingPrice = $warehouseStock->average_buying_price ?? $transfer->productVariant->buying_price_per_unit ?? 0;
                
                $realTimeProfit = $this->calculateRealTimeProfit($transfer, $ownerId, $sellingPrice, $buyingPrice);
                $revenueData = $this->calculateRealTimeRevenue($transfer, $ownerId);
                $expectedAmount = $transfer->total_units * $sellingPrice;
                
                $results[$transfer->id] = [
                    'real_time_profit' => $realTimeProfit,
                    'real_time_revenue' => $revenueData['total'],
                    'real_time_revenue_recorded' => $revenueData['recorded'],
                    'real_time_revenue_submitted' => $revenueData['submitted'],
                    'real_time_revenue_pending' => $revenueData['pending'],
                    'expected_amount' => $expectedAmount,
                ];
            } else {
                $results[$transfer->id] = [
                    'real_time_profit' => 0,
                    'real_time_revenue' => 0,
                    'real_time_revenue_recorded' => 0,
                    'real_time_revenue_submitted' => 0,
                    'real_time_revenue_pending' => 0,
                    'expected_amount' => 0,
                ];
            }
        }

        return response()->json(['success' => true, 'data' => $results]);
    }

    /**
     * Display transfer history with expected amount, real-time generated amount, and balance status.
     */
    public function history()
    {
        // Check permission - allow both stock_transfer and inventory permissions, or counter/stock keeper roles
        $canView = $this->hasPermission('stock_transfer', 'view') || $this->hasPermission('inventory', 'view');
        
        // Allow counter and stock keeper roles even without explicit permission
        if (!$canView && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['counter', 'bar counter', 'stock keeper', 'stockkeeper'])) {
                    $canView = true;
                }
            }
        }
        
        if (!$canView) {
            abort(403, 'You do not have permission to view stock transfer history.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get only completed transfers
        $transfers = StockTransfer::where('user_id', $ownerId)
            ->where('status', 'completed')
            ->with(['productVariant.product', 'productVariant.counterStock', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate expected revenue, real-time revenue, balance status, and percentage remaining
        $transfers->getCollection()->transform(function($transfer) use ($ownerId) {
            if ($transfer->productVariant) {
                // Get counter stock to get current selling price
                $counterStock = StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $transfer->product_variant_id)
                    ->where('location', 'counter')
                    ->first();
                $financials = $transfer->calculateFinancials();
                $transfer->expected_amount = $financials['revenue'];
                $transfer->expected_profit = $financials['profit'];
                $transfer->is_tot_calculation = $financials['is_tot'];

                // Calculate real-time generated revenue
                $revenueData = $this->calculateRealTimeRevenue($transfer, $ownerId);
                $transfer->real_time_amount = $revenueData['total'];
                $transfer->real_time_recorded = $revenueData['recorded'];
                $transfer->real_time_submitted = $revenueData['submitted'];
                $transfer->real_time_pending = $revenueData['pending'];
                
                // Calculate percentage remaining (based on total recorded)
                if ($transfer->expected_amount > 0) {
                    $transfer->percentage_remaining = (($transfer->expected_amount - $transfer->real_time_amount) / $transfer->expected_amount) * 100;
                    $transfer->percentage_remaining = max(0, min(100, $transfer->percentage_remaining)); // Clamp between 0 and 100
                } else {
                    $transfer->percentage_remaining = 0;
                }
                
                // Determine balance status
                // If fully submitted and reconciled, it's balanced
                if ($transfer->real_time_submitted >= $transfer->expected_amount) {
                    $transfer->balance_status = 'balanced';
                    $transfer->balance_status_label = 'Balanced';
                    $transfer->balance_status_class = 'success';
                } 
                // If recorded amount meets or exceeds expected, but not yet submitted, show "Pending Reconciliation"
                elseif ($transfer->real_time_amount >= $transfer->expected_amount) {
                    $transfer->balance_status = 'pending_reconciliation';
                    $transfer->balance_status_label = 'Pending Reconciliation';
                    $transfer->balance_status_class = 'info';
                }
                // If there are recorded payments but not enough, show "Partially Recorded"
                elseif ($transfer->real_time_amount > 0) {
                    $transfer->balance_status = 'partially_recorded';
                    $transfer->balance_status_label = 'Partially Recorded';
                    $transfer->balance_status_class = 'warning';
                }
                // No payments recorded yet
                else {
                    $transfer->balance_status = 'unbalanced';
                    $transfer->balance_status_label = 'Unbalanced';
                    $transfer->balance_status_class = 'warning';
                }
            } else {
                $transfer->expected_amount = 0;
                $transfer->real_time_amount = 0;
                $transfer->percentage_remaining = 100;
                $transfer->balance_status = 'unbalanced';
                $transfer->balance_status_label = 'Unbalanced';
                $transfer->balance_status_class = 'warning';
            }
            
            return $transfer;
        });

        return view('bar.stock-transfers.history', compact('transfers'));
    }
}

