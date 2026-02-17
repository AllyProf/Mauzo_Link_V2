<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Http\Request;

class BeverageInventoryController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Display beverage inventory overview
     */
    public function index()
    {
        // Check permission
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view beverage inventory.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get all beverage products (products in beverage/alcoholic categories)
        $products = Product::where('user_id', $ownerId)
            ->where(function($query) {
                $query->where('category', 'like', '%beverage%')
                      ->orWhere('category', 'like', '%drink%')
                      ->orWhere('category', 'like', '%alcohol%')
                      ->orWhere('category', 'like', '%beer%')
                      ->orWhere('category', 'like', '%wine%')
                      ->orWhere('category', 'like', '%spirit%');
            })
            ->with(['variants.stockLocations' => function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            }])
            ->orderBy('name')
            ->get();

        // Calculate totals and prepare detailed stock data
        $totalWarehouseStock = 0;
        $totalCounterStock = 0;
        $totalWarehouseValue = 0;
        $totalCounterValue = 0;
        $totalValue = 0;
        
        // Prepare detailed stock overview by variant
        $stockOverview = collect();
        
        // Track products with stock for filtering
        $productsWithStock = collect();

        foreach ($products as $product) {
            $productTotalStock = 0;
            $productHasStock = false;
            
            foreach ($product->variants as $variant) {
                $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
                $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                
                $warehouseQty = $warehouseStock ? $warehouseStock->quantity : 0;
                $counterQty = $counterStock ? $counterStock->quantity : 0;
                $totalQty = $warehouseQty + $counterQty;
                
                // Check if this variant has stock
                if ($totalQty > 0) {
                    $productHasStock = true;
                }
                
                $warehouseValue = $warehouseQty * ($warehouseStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0);
                $counterValue = $counterQty * ($counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0);
                
                $totalWarehouseStock += $warehouseQty;
                $totalCounterStock += $counterQty;
                $totalWarehouseValue += $warehouseValue;
                $totalCounterValue += $counterValue;
                $totalValue += $warehouseValue;
                
                // Only add to overview if it has stock
                if ($totalQty > 0) {
                    // Calculate packaging information
                    $packagingType = $variant->packaging ?? 'Packages';
                    $itemsPerPackage = $variant->items_per_package ?? 1;
                    $warehousePackages = $warehouseQty > 0 ? floor($warehouseQty / $itemsPerPackage) : 0;
                    $counterPackages = $counterQty > 0 ? floor($counterQty / $itemsPerPackage) : 0;
                    $totalPackages = $warehousePackages + $counterPackages;
                    
                    $stockOverview->push([
                        'product_name' => $product->name,
                        'variant' => $variant->measurement . ' - ' . $variant->packaging,
                        'variant_id' => $variant->id,
                        'warehouse_quantity' => $warehouseQty,
                        'counter_quantity' => $counterQty,
                        'total_quantity' => $totalQty,
                        'warehouse_value' => $warehouseValue,
                        'counter_value' => $counterValue,
                        'warehouse_buying_price' => $warehouseStock->average_buying_price ?? $variant->buying_price_per_unit ?? 0,
                        'counter_selling_price' => $counterStock->selling_price ?? $variant->selling_price_per_unit ?? 0,
                        'open_bottle_tots' => \App\Models\OpenBottle::where('user_id', $ownerId)
                                            ->where('product_variant_id', $variant->id)
                                            ->value('tots_remaining') ?? 0,
                        'total_tots' => $variant->total_tots,
                        'is_low_stock' => $totalQty < 10,
                        'packaging_type' => $packagingType,
                        'items_per_package' => $itemsPerPackage,
                        'warehouse_packages' => $warehousePackages,
                        'counter_packages' => $counterPackages,
                        'total_packages' => $totalPackages,
                    ]);
                }
            }
            
            // Only include product in "All Beverages" table if it has stock
            if ($productHasStock) {
                $productsWithStock->push($product);
            }
        }

        // Calculate recommendations
        $recommendations = $this->calculateRecommendations($stockOverview, $products);
        
        return view('bar.beverage-inventory.index', compact(
            'products', 
            'productsWithStock',
            'totalWarehouseStock', 
            'totalCounterStock', 
            'totalWarehouseValue',
            'totalCounterValue',
            'totalValue',
            'stockOverview',
            'recommendations'
        ));
    }

    /**
     * Show form to add beverage stock
     */
    public function addBeverage()
    {
        // Check permission
        if (!$this->hasPermission('inventory', 'create')) {
            abort(403, 'You do not have permission to add beverages.');
        }

        // Redirect directly to stock receipts create page
        return redirect()->route('bar.stock-receipts.create');
    }

    /**
     * Show stock levels for all beverages
     */
    public function stockLevels()
    {
        // Check permission
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view stock levels.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get all beverage variants with stock locations
        $variants = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where(function($q) {
                      $q->where('category', 'like', '%beverage%')
                        ->orWhere('category', 'like', '%drink%')
                        ->orWhere('category', 'like', '%alcohol%')
                        ->orWhere('category', 'like', '%beer%')
                        ->orWhere('category', 'like', '%wine%')
                        ->orWhere('category', 'like', '%spirit%');
                  });
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        }])
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return view('bar.beverage-inventory.stock-levels', compact('variants'));
    }

    /**
     * Show low stock alerts
     */
    public function lowStockAlerts()
    {
        // Check permission
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view low stock alerts.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get all beverage variants with low stock
        $variants = ProductVariant::whereHas('product', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId)
                  ->where(function($q) {
                      $q->where('category', 'like', '%beverage%')
                        ->orWhere('category', 'like', '%drink%')
                        ->orWhere('category', 'like', '%alcohol%')
                        ->orWhere('category', 'like', '%beer%')
                        ->orWhere('category', 'like', '%wine%')
                        ->orWhere('category', 'like', '%spirit%');
                  });
        })
        ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        }])
        ->get()
        ->filter(function($variant) {
            $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
            $counterStock = $variant->stockLocations->where('location', 'counter')->first();
            
            $totalStock = ($warehouseStock ? $warehouseStock->quantity : 0) + 
                         ($counterStock ? $counterStock->quantity : 0);
            
            // Low stock is less than 10 units
            return $totalStock < 10;
        })
        ->values();

        return view('bar.beverage-inventory.low-stock-alerts', compact('variants'));
    }
    
    /**
     * Display warehouse stock for Stock Keeper
     */
    public function warehouseStock()
    {
        // Check permission
        if (!$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to view warehouse stock.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get all beverage products with warehouse stock
        $products = Product::where('user_id', $ownerId)
            ->where(function($query) {
                $query->where('category', 'like', '%beverage%')
                      ->orWhere('category', 'like', '%drink%')
                      ->orWhere('category', 'like', '%alcohol%')
                      ->orWhere('category', 'like', '%beer%')
                      ->orWhere('category', 'like', '%wine%')
                      ->orWhere('category', 'like', '%spirit%');
            })
            ->with(['variants.stockLocations' => function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)
                      ->where('location', 'warehouse');
            }])
            ->orderBy('name')
            ->get();

        // Prepare warehouse stock data
        $warehouseStock = collect();
        $totalWarehouseStock = 0;
        $totalWarehouseValue = 0;
        $productsWithWarehouseStock = collect();

        foreach ($products as $product) {
            $productHasWarehouseStock = false;
            $productWarehouseTotal = 0;
            $productWarehouseValue = 0;
            
            foreach ($product->variants as $variant) {
                $warehouseStockLocation = $variant->stockLocations->where('location', 'warehouse')->first();
                
                if ($warehouseStockLocation && $warehouseStockLocation->quantity > 0) {
                    $productHasWarehouseStock = true;
                    $quantity = $warehouseStockLocation->quantity;
                    $value = $quantity * ($warehouseStockLocation->average_buying_price ?? $variant->buying_price_per_unit ?? 0);
                    
                    $productWarehouseTotal += $quantity;
                    $productWarehouseValue += $value;
                    $totalWarehouseStock += $quantity;
                    $totalWarehouseValue += $value;
                    
                    $buyingPrice = $warehouseStockLocation->average_buying_price ?? $variant->buying_price_per_unit ?? 0;
                    $sellingPrice = $warehouseStockLocation->selling_price ?? $variant->selling_price_per_unit ?? 0;
                    $totalCostSold = $quantity * $sellingPrice;
                    $expectedProfit = $totalCostSold - $value;
                    
                    // Get packaging type (e.g., Crates, Cartons, Boxes)
                    $packagingType = $variant->packaging ?? 'Packages';
                    $packagingCount = floor($quantity / $variant->items_per_package);
                    
                    $warehouseStock->push([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_image' => $product->image,
                        'variant_id' => $variant->id,
                        'variant' => $variant->measurement . ' - ' . $variant->packaging,
                        'quantity' => $quantity,
                        'value' => $value,
                        'buying_price' => $buyingPrice,
                        'selling_price' => $sellingPrice,
                        'total_cost_sold' => $totalCostSold,
                        'expected_profit' => $expectedProfit,
                        'items_per_package' => $variant->items_per_package,
                        'packages' => $packagingCount,
                        'packaging_type' => $packagingType,
                        'is_low_stock' => $quantity < 10,
                    ]);
                }
            }
            
            if ($productHasWarehouseStock) {
                $productsWithWarehouseStock->push([
                    'product' => $product,
                    'total_quantity' => $productWarehouseTotal,
                    'total_value' => $productWarehouseValue,
                ]);
            }
        }

        return view('bar.beverage-inventory.warehouse-stock', compact(
            'warehouseStock',
            'productsWithWarehouseStock',
            'totalWarehouseStock',
            'totalWarehouseValue'
        ));
    }
    
    /**
     * Calculate recommendations for inventory management
     */
    private function calculateRecommendations($stockOverview, $products)
    {
        $recommendations = [
            'low_stock' => [],
            'stock_movements' => [],
            'reorder_suggestions' => [],
        ];
        
        // Low Stock Alerts with Reorder Suggestions
        foreach ($stockOverview as $stock) {
            if ($stock['total_quantity'] < 10) {
                // Calculate suggested reorder quantity (aim for 30-50 units based on current stock)
                $suggestedReorder = max(30, 50 - $stock['total_quantity']);
                
                $recommendations['low_stock'][] = [
                    'product_name' => $stock['product_name'],
                    'variant' => $stock['variant'],
                    'current_stock' => $stock['total_quantity'],
                    'warehouse_stock' => $stock['warehouse_quantity'],
                    'counter_stock' => $stock['counter_quantity'],
                    'suggested_reorder' => $suggestedReorder,
                    'priority' => $stock['total_quantity'] == 0 ? 'critical' : ($stock['total_quantity'] < 5 ? 'high' : 'medium'),
                ];
                
                // Add to reorder suggestions
                $recommendations['reorder_suggestions'][] = [
                    'product_name' => $stock['product_name'],
                    'variant' => $stock['variant'],
                    'quantity' => $suggestedReorder,
                    'reason' => 'Low stock alert',
                ];
            }
        }
        
        // Stock Movement Recommendations (Warehouse to Counter)
        foreach ($stockOverview as $stock) {
            $warehouseQty = $stock['warehouse_quantity'];
            $counterQty = $stock['counter_quantity'];
            $totalQty = $stock['total_quantity'];
            
            // Recommend transfer if:
            // 1. Warehouse has stock (> 20 units)
            // 2. Counter is low (< 50 units or < 30% of total)
            // 3. Total stock is adequate (> 10 units)
            if ($warehouseQty > 20 && $counterQty < 50 && $totalQty > 10) {
                // Calculate suggested transfer amount
                $targetCounter = min(100, max(50, (int)($totalQty * 0.4))); // Target 40% of total or 50-100 units
                $suggestedTransfer = min($targetCounter - $counterQty, $warehouseQty - 10); // Keep at least 10 in warehouse
                
                if ($suggestedTransfer > 0) {
                    $recommendations['stock_movements'][] = [
                        'product_name' => $stock['product_name'],
                        'variant' => $stock['variant'],
                        'warehouse_stock' => $warehouseQty,
                        'counter_stock' => $counterQty,
                        'suggested_transfer' => $suggestedTransfer,
                        'priority' => $counterQty < 20 ? 'high' : 'medium',
                        'variant_id' => $this->getVariantId($products, $stock['product_name'], $stock['variant']),
                    ];
                }
            }
        }
        
        // Sort by priority
        usort($recommendations['low_stock'], function($a, $b) {
            $priorityOrder = ['critical' => 3, 'high' => 2, 'medium' => 1];
            return ($priorityOrder[$b['priority']] ?? 0) - ($priorityOrder[$a['priority']] ?? 0);
        });
        
        usort($recommendations['stock_movements'], function($a, $b) {
            $priorityOrder = ['high' => 2, 'medium' => 1];
            return ($priorityOrder[$b['priority']] ?? 0) - ($priorityOrder[$a['priority']] ?? 0);
        });
        
        return $recommendations;
    }
    
    /**
     * Get variant ID from product name and variant string
     */
    private function getVariantId($products, $productName, $variantString)
    {
        foreach ($products as $product) {
            if ($product->name === $productName) {
                foreach ($product->variants as $variant) {
                    $variantFullName = $variant->measurement . ' - ' . $variant->packaging;
                    if ($variantFullName === $variantString) {
                        return $variant->id;
                    }
                }
            }
        }
        return null;
    }
}

