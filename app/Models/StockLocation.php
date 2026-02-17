<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_variant_id',
        'location',
        'quantity',
        'average_buying_price',
        'selling_price',
        'selling_price_per_tot',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'average_buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'selling_price_per_tot' => 'decimal:2',
    ];

    /**
     * Get the owner (user) that owns this stock location.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the product variant for this stock location.
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Check if stock is available.
     */
    public function isAvailable()
    {
        return $this->quantity > 0;
    }

    /**
     * Check if stock is low (less than 10% of average).
     */
    public function isLowStock()
    {
        // This can be customized based on business rules
        return $this->quantity < 10;
    }
}
