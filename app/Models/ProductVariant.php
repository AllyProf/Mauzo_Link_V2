<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'measurement',
        'packaging',
        'items_per_package',
        'buying_price_per_unit',
        'selling_price_per_unit',
        'can_sell_in_tots',
        'total_tots',
        'selling_price_per_tot',
        'barcode',
        'qr_code',
        'image',
        'is_active',
        'selling_type',
        'unit',
        'low_stock_threshold',
    ];

    protected $appends = ['display_name', 'portion_unit_name'];

    protected $casts = [
        'items_per_package' => 'integer',
        'buying_price_per_unit' => 'decimal:2',
        'selling_price_per_unit' => 'decimal:2',
        'selling_price_per_tot' => 'decimal:2',
        'is_active' => 'boolean',
        'can_sell_in_tots' => 'boolean',
    ];

    /**
     * Get the dynamic portion unit name (Glass, Shot, etc)
     */
    public function getPortionUnitNameAttribute()
    {
        $category = strtolower($this->product->category ?? '');
        
        if (strpos($category, 'wine') !== false) {
            return 'Glass';
        }
        
        if (strpos($category, 'spirit') !== false || 
            strpos($category, 'whiskey') !== false || 
            strpos($category, 'vodka') !== false || 
            strpos($category, 'gin') !== false ||
            strpos($category, 'rum') !== false ||
            strpos($category, 'tequila') !== false ||
            strpos($category, 'brandy') !== false ||
            strpos($category, 'liqueur') !== false) {
            return 'Shot';
        }
        
        return 'Tot';
    }

    /**
     * Format a number of units (bottles/pieces) into a human-readable string
     * showing Crates/Cartons and Bottles, or Bottles and Glasses/Shots.
     */
    public function formatUnits($units)
    {
        $units = (float)$units;
        if ($units <= 0) return '0';

        // 1. Handle Bulk Packaging (Crates/Cartons)
        if ($this->items_per_package > 1) {
            $fullPackages = floor($units / $this->items_per_package);
            $remainingUnits = round(fmod($units, $this->items_per_package));
            
            $pkgName = $this->packaging ?: 'Package';
            
            $parts = [];
            if ($fullPackages > 0) {
                $parts[] = $fullPackages . ' ' . $pkgName . ($fullPackages > 1 ? 's' : '');
            }
            if ($remainingUnits > 0 || empty($parts)) {
                $parts[] = $remainingUnits . ' btl' . ($remainingUnits > 1 ? 's' : '');
            }
            
            return implode(', ', $parts);
        }

        // 2. Handle Portions (Glasses/Shots) - check if it's a fractional bottle
        if ($this->can_sell_in_tots && $this->total_tots > 0) {
            $fullBottles = floor($units);
            // Convert decimal part back to number of glasses
            $remainingUnits = round(($units - $fullBottles) * $this->total_tots);
            
            $unitName = $this->portion_unit_name;
            
            $parts = [];
            if ($fullBottles > 0) {
                $parts[] = $fullBottles . ' btl' . ($fullBottles > 1 ? 's' : '');
            }
            if ($remainingUnits > 0 || empty($parts)) {
                $parts[] = $remainingUnits . ' ' . $unitName . ($remainingUnits > 1 ? ($unitName === 'Glass' ? 'es' : 's') : '');
            }
            
            return implode(', ', $parts);
        }

        // 3. Simple unit/bottle
        return $units . ' btl' . ($units > 1 ? 's' : '');
    }

    /**
     * Get clean display name for Mobile POS.
     * Uses ProductHelper for consistent naming logic.
     */
    public function getDisplayNameAttribute()
    {
        return \App\Helpers\ProductHelper::generateDisplayName(
            $this->product->name ?? 'N/A', 
            ($this->measurement ?? '') . ' - ' . ($this->packaging ?? ''),
            $this->name
        );
    }

    /**
     * Get the product that owns this variant.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get stock locations for this variant.
     */
    public function stockLocations()
    {
        return $this->hasMany(StockLocation::class);
    }

    /**
     * Get warehouse stock for this variant.
     */
    public function warehouseStock()
    {
        return $this->hasOne(StockLocation::class)
            ->where('location', 'warehouse');
    }

    /**
     * Get counter stock for this variant.
     */
    public function counterStock()
    {
        return $this->hasOne(StockLocation::class)
            ->where('location', 'counter');
    }

    /**
     * Get stock receipts for this variant.
     */
    public function stockReceipts()
    {
        return $this->hasMany(StockReceipt::class);
    }

    /**
     * Get stock transfers for this variant.
     */
    public function stockTransfers()
    {
        return $this->hasMany(StockTransfer::class);
    }

    /**
     * Get order items for this variant.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get stock movements for this variant.
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get full name (Product Name - Measurement).
     */
    public function getFullNameAttribute()
    {
        return $this->product->name . ' - ' . $this->measurement . ($this->unit ?? '');
    }

    /**
     * Get profit per unit.
     */
    public function getProfitPerUnitAttribute()
    {
        return $this->selling_price_per_unit - $this->buying_price_per_unit;
    }
}
