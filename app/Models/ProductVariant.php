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
    ];

    protected $casts = [
        'items_per_package' => 'integer',
        'buying_price_per_unit' => 'decimal:2',
        'selling_price_per_unit' => 'decimal:2',
        'selling_price_per_tot' => 'decimal:2',
        'is_active' => 'boolean',
        'can_sell_in_tots' => 'boolean',
    ];

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
