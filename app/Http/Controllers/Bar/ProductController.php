<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    use HandlesStaffPermissions;
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('products', 'view')) {
            abort(403, 'You do not have permission to view products.');
        }

        $ownerId = $this->getOwnerId();
        $search = $request->get('search');
        $category = $request->get('category');
        
        // Comprehensive list of categories precisely matching the bar context
        $standardCategories = collect([
            'BEER/LAGER', 'BRANDY/WHISKY/RUM/GIN', 'CAN BEER', 
            'ENERGIES', 'MIXED', 'SODA', 'SOFT DRINKS', 
            'SPIRITS', 'WATER', 'WINE BY BOTTLE'
        ]);

        // Get unique categories currently in use plus standard ones
        $existingCategories = Product::where('user_id', $ownerId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->map(fn($c) => strtoupper($c));
        
        $categories = $standardCategories->merge($existingCategories)->unique()->sort();

        $query = ProductVariant::with(['product.supplier', 'product'])
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('products.user_id', $ownerId);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('product_variants.name', 'LIKE', "%{$search}%")
                  ->orWhere('products.brand', 'LIKE', "%{$search}%")
                  ->orWhere('products.name', 'LIKE', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('products.category', $category);
        }

        $variants = $query->orderBy('products.category')
            ->orderBy('product_variants.name')
            ->select('product_variants.*')
            ->paginate(12)
            ->appends(['search' => $search, 'category' => $category]);

        // Check permission for the view
        $canCreate = $this->hasPermission('products', 'create');
        if (!$canCreate && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['stock keeper', 'stockkeeper', 'counter', 'bar counter'])) {
                    $canCreate = true;
                }
            }
        }

        if ($request->ajax()) {
            return view('bar.products._product_list', compact('variants', 'canCreate'))->render();
        }

        return view('bar.products.index', compact('variants', 'categories', 'search', 'category', 'canCreate'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        // Check permission
        $canCreate = $this->hasPermission('products', 'create');
        
        // Allow create for stock keeper and counter roles even without explicit permission
        if (!$canCreate && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['stock keeper', 'stockkeeper', 'counter', 'bar counter'])) {
                    $canCreate = true;
                }
            }
        }
        
        if (!$canCreate) {
            abort(403, 'You do not have permission to create products.');
        }

        $ownerId = $this->getOwnerId();
        
        $suppliers = Supplier::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('company_name')
            ->get();

        return view('bar.products.create', compact('suppliers'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        // Check permission
        $canCreate = $this->hasPermission('products', 'create');
        
        // Allow create for stock keeper and counter roles even without explicit permission
        if (!$canCreate && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['stock keeper', 'stockkeeper', 'counter', 'bar counter'])) {
                    $canCreate = true;
                }
            }
        }
        
        if (!$canCreate) {
            abort(403, 'You do not have permission to create products.');
        }

        $ownerId = $this->getOwnerId();

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'variants.*.measurement' => 'required|numeric',
            'variants.*.unit' => 'required|string|max:20',
            'variants.*.selling_type' => 'required|string|in:bottle,glass,mixed',
            'variants.*.total_tots' => 'nullable|integer|min:1',
            'variants.*.packaging' => 'required|string|in:Piece,Carton,Crate',
            'variants.*.items_per_package' => 'nullable|integer|min:1',
        ]);

        // Verify supplier belongs to owner
        if (isset($validated['supplier_id']) && $validated['supplier_id']) {
            $supplier = Supplier::where('id', $validated['supplier_id'])
                ->where('user_id', $ownerId)
                ->first();
            
            if (!$supplier) {
                return back()->withErrors(['supplier_id' => 'Invalid supplier selected.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Use Brand as the product name if provided, otherwise fallback to first variant name
            $brandName = $validated['brand'] ?? null;
            $firstVariantName = $validated['variants'][0]['name'];
            $productName = $brandName ? $brandName : $firstVariantName;

            $product = Product::create([
                'user_id' => $ownerId,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'name' => $productName,
                'brand' => $brandName,
                'category' => $validated['category'] ?? null,
                'description' => $validated['description'] ?? null,
                'barcode' => $validated['barcode'] ?? null,
                'is_active' => true,
            ]);

            // Create variants
            foreach ($validated['variants'] as $index => $variantData) {
                $vImagePath = null;
                if ($request->hasFile("variants.{$index}.image")) {
                    $vImage = $request->file("variants.{$index}.image");
                    $vImageName = time() . '_' . uniqid() . '.' . $vImage->getClientOriginalExtension();
                    $vImage->move(public_path('storage/products'), $vImageName);
                    $vImagePath = 'products/' . $vImageName;
                }

                \App\Models\ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variantData['name'],
                    'image' => $vImagePath,
                    'measurement' => $variantData['measurement'],
                    'unit' => $variantData['unit'],
                    'selling_type' => $variantData['selling_type'],
                    'packaging' => $variantData['packaging'],
                    'items_per_package' => $variantData['packaging'] === 'Piece' ? 1 : ($variantData['items_per_package'] ?? 1),
                    'buying_price_per_unit' => 0,
                    'selling_price_per_unit' => 0,
                    'can_sell_in_tots' => in_array($variantData['selling_type'], ['glass', 'mixed']),
                    'total_tots' => in_array($variantData['selling_type'], ['glass', 'mixed']) ? ($variantData['total_tots'] ?? null) : null,
                    'selling_price_per_tot' => 0,
                    'is_active' => true,
                ]);

                // Update product image with the first variant image if not set
                if ($index === 0 && $vImagePath) {
                    $product->update(['image' => $vImagePath]);
                }
            }

            DB::commit();

            return redirect()->route('bar.products.index')
                ->with('alert_success', 'Product registered successfully. You can set prices and stock during stock reception.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to register product: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($product->user_id !== $ownerId) {
            abort(403, 'You do not have access to this product.');
        }

        // Check permission
        if (!$this->hasPermission('products', 'view')) {
            abort(403, 'You do not have permission to view products.');
        }

        $product->load(['supplier', 'variants.stockLocations']);

        // If AJAX request, return JSON
        if (request()->ajax() || request()->wantsJson()) {
            // Format variants with stock information
            $variants = $product->variants->map(function($variant) use ($ownerId) {
                $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
                $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'image' => $variant->image ? asset('storage/' . $variant->image) : null,
                    'measurement' => $variant->measurement,
                    'unit' => $variant->unit,
                    'packaging' => $variant->packaging,
                    'items_per_package' => $variant->items_per_package,
                    'selling_type' => $variant->selling_type,
                    'can_sell_in_tots' => $variant->can_sell_in_tots,
                    'total_tots' => $variant->total_tots,
                    'selling_price_per_tot' => $variant->selling_price_per_tot,
                    'is_active' => $variant->is_active,
                    'warehouse_stock' => $warehouseStock ? ['quantity' => $warehouseStock->quantity] : null,
                    'counter_stock' => $counterStock ? ['quantity' => $counterStock->quantity] : null,
                ];
            });

            return response()->json([
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'category' => $product->category,
                    'description' => $product->description,
                    'image' => $product->image,
                    'is_active' => $product->is_active,
                    'supplier' => $product->supplier ? [
                        'company_name' => $product->supplier->company_name,
                    ] : null,
                    'variants' => $variants,
                ]
            ]);
        }

        return view('bar.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($product->user_id !== $ownerId) {
            abort(403, 'You do not have access to this product.');
        }

        // Check permission
        if (!$this->hasPermission('products', 'edit')) {
            abort(403, 'You do not have permission to edit products.');
        }

        $suppliers = Supplier::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('company_name')
            ->get();

        $product->load('variants');

        return view('bar.products.edit', compact('product', 'suppliers'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($product->user_id !== $ownerId) {
            abort(403, 'You do not have access to this product.');
        }

        // Check permission
        if (!$this->hasPermission('products', 'edit')) {
            abort(403, 'You do not have permission to edit products.');
        }

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'brand' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'is_active' => 'boolean',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|exists:product_variants,id',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'variants.*.measurement' => 'required|numeric',
            'variants.*.unit' => 'required|string|max:20',
            'variants.*.selling_type' => 'required|string|in:bottle,glass,mixed',
            'variants.*.total_tots' => 'nullable|integer|min:1',
            'variants.*.packaging' => 'required|string|in:Piece,Carton,Crate',
            'variants.*.items_per_package' => 'nullable|integer|min:1',
        ]);

        // Verify supplier belongs to owner
        if ($validated['supplier_id']) {
            $supplier = Supplier::where('id', $validated['supplier_id'])
                ->where('user_id', $ownerId)
                ->first();
            
            if (!$supplier) {
                return back()->withErrors(['supplier_id' => 'Invalid supplier selected.'])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Use the first variant's name as the base product name
            $baseName = $validated['variants'][0]['name'];
            $product->update(['name' => $baseName] + $validated);

            // Get existing variant IDs
            $existingVariantIds = $product->variants()->pluck('id')->toArray();
            $submittedVariantIds = [];

            // Update or create variants
            foreach ($validated['variants'] as $index => $variantData) {
                $vImagePath = null;
                if ($request->hasFile("variants.{$index}.image")) {
                    $vImage = $request->file("variants.{$index}.image");
                    $vImageName = time() . '_' . uniqid() . '.' . $vImage->getClientOriginalExtension();
                    $vImage->move(public_path('storage/products'), $vImageName);
                    $vImagePath = 'products/' . $vImageName;
                }

                if (isset($variantData['id']) && $variantData['id']) {
                    // Update existing variant
                    $variant = ProductVariant::where('id', $variantData['id'])
                        ->where('product_id', $product->id)
                        ->first();
                    
                    if ($variant) {
                        $updateData = [
                            'name' => $variantData['name'],
                            'measurement' => $variantData['measurement'],
                            'unit' => $variantData['unit'],
                            'packaging' => $variantData['packaging'],
                            'items_per_package' => $variantData['packaging'] === 'Piece' ? 1 : ($variantData['items_per_package'] ?? 1),
                            'selling_type' => $variantData['selling_type'],
                            'can_sell_in_tots' => in_array($variantData['selling_type'], ['glass', 'mixed']),
                            'total_tots' => in_array($variantData['selling_type'], ['glass', 'mixed']) ? ($variantData['total_tots'] ?? null) : null,
                        ];
                        
                        if ($vImagePath) {
                            // Delete old image if exists
                            if ($variant->image && file_exists(public_path('storage/' . $variant->image))) {
                                @unlink(public_path('storage/' . $variant->image));
                            }
                            $updateData['image'] = $vImagePath;
                        }
                        
                        $variant->update($updateData);
                        $submittedVariantIds[] = $variant->id;

                        // Update product main image if it's the first variant and has new image
                        if ($index === 0 && $vImagePath) {
                            $product->update(['image' => $vImagePath]);
                        }
                    }
                } else {
                    // Create new variant
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'name' => $variantData['name'],
                        'image' => $vImagePath,
                        'measurement' => $variantData['measurement'],
                        'unit' => $variantData['unit'],
                        'selling_type' => $variantData['selling_type'],
                        'packaging' => $variantData['packaging'],
                        'items_per_package' => $variantData['packaging'] === 'Piece' ? 1 : ($variantData['items_per_package'] ?? 1),
                        'buying_price_per_unit' => 0,
                        'selling_price_per_unit' => 0,
                        'can_sell_in_tots' => in_array($variantData['selling_type'], ['glass', 'mixed']),
                        'total_tots' => in_array($variantData['selling_type'], ['glass', 'mixed']) ? ($variantData['total_tots'] ?? null) : null,
                        'is_active' => true,
                    ]);
                    $submittedVariantIds[] = $variant->id;

                    if ($index === 0 && $vImagePath) {
                        $product->update(['image' => $vImagePath]);
                    }
                }
            }

            // Delete variants that were removed
            $variantsToDelete = array_diff($existingVariantIds, $submittedVariantIds);
            if (!empty($variantsToDelete)) {
                ProductVariant::whereIn('id', $variantsToDelete)
                    ->where('product_id', $product->id)
                    ->delete();
            }

            DB::commit();

            return redirect()->route('bar.products.index')
                ->with('alert_success', 'Product updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        $ownerId = $this->getOwnerId();
        
        // Check ownership
        if ($product->user_id !== $ownerId) {
            abort(403, 'You do not have access to this product.');
        }

        // Check permission
        $canDelete = $this->hasPermission('products', 'delete');
        
        // Allow delete for stock keeper and counter roles even without explicit permission
        if (!$canDelete && session('is_staff')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower(trim($staff->role->name ?? ''));
                if (in_array($roleName, ['stock keeper', 'stockkeeper', 'counter', 'bar counter'])) {
                    $canDelete = true;
                }
            }
        }
        
        if (!$canDelete) {
            abort(403, 'You do not have permission to delete products.');
        }

        // Check if product has stock receipts or orders
        if ($product->variants()->whereHas('stockReceipts')->exists() || 
            $product->variants()->whereHas('orderItems')->exists()) {
            return redirect()->route('bar.products.index')
                ->with('error', 'Cannot delete product. It has associated stock receipts or orders.');
        }

        $product->delete();

        return redirect()->route('bar.products.index')
            ->with('success', 'Product deleted successfully.');
    }
    /**
     * Get products by category (AJAX).
     */
    public function getByCategory(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $category = $request->category;
        $supplierId = $request->supplier_id;

        $products = Product::where('user_id', $ownerId)
            ->where('is_active', true)
            ->when($category, function($q) use ($category) {
                // Smart check: matches Category OR Brand (Distributor)
                return $q->where(function($qq) use ($category) {
                    $qq->where('category', $category)
                       ->orWhere('brand', 'LIKE', "%{$category}%");
                });
            })
            ->when($supplierId, function($q) use ($supplierId) {
                // Return products matching the supplier OR products with no supplier (orphans) that match the brand/category
                return $q->where(function($qq) use ($supplierId) {
                    $qq->where('supplier_id', $supplierId)
                       ->orWhereNull('supplier_id');
                });
            })
            ->with(['variants' => function($q) {
                $q->where('is_active', true);
            }])
            ->get();

        $variantsData = $products->flatMap(function($product) use ($ownerId) {
            return $product->variants->map(function($variant) use ($product, $ownerId) {
                $warehouseStock = \App\Models\StockLocation::where('user_id', $ownerId)
                    ->where('product_variant_id', $variant->id)
                    ->where('location', 'warehouse')
                    ->first();
                
                $existingQuantity = $warehouseStock ? $warehouseStock->quantity : 0;
                $itemsPerPackage = $variant->items_per_package ?? 1;
                $existingPackages = $itemsPerPackage > 0 ? floor($existingQuantity / $itemsPerPackage) : 0;
                
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'product' => [
                        'name' => $product->name,
                        'brand' => $product->brand,
                    ],
                    'measurement' => $variant->measurement,
                    'packaging' => $variant->packaging,
                    'unit' => $variant->unit,
                    'items_per_package' => $variant->items_per_package,
                    'buying_price_per_unit' => $variant->buying_price_per_unit ? (float)$variant->buying_price_per_unit : null,
                    'selling_price_per_unit' => $variant->selling_price_per_unit ? (float)$variant->selling_price_per_unit : null,
                    'can_sell_in_tots' => $variant->can_sell_in_tots,
                    'total_tots' => $variant->total_tots,
                    'selling_price_per_tot' => $variant->selling_price_per_tot ? (float)$variant->selling_price_per_tot : null,
                    'existing_quantity' => $existingQuantity,
                    'existing_packages' => $existingPackages,
                    'conversion_qty' => $itemsPerPackage,
                ];
            });
        })->values();

        return response()->json($variantsData);
    }
}
