<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'unit',
        'current_stock',
        'min_stock_level',
        'max_stock_level',
        'location',
        'cost_per_unit',
        'supplier_info',
        'expiry_date',
        'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the owner (user) that owns this ingredient.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all recipes that use this ingredient.
     */
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
            ->withPivot('quantity_required', 'unit', 'notes')
            ->withTimestamps();
    }

    /**
     * Get all receipts for this ingredient.
     */
    public function receipts()
    {
        return $this->hasMany(IngredientReceipt::class);
    }

    /**
     * Get all batches for this ingredient.
     */
    public function batches()
    {
        return $this->hasMany(IngredientBatch::class);
    }

    /**
     * Get all stock movements for this ingredient.
     */
    public function stockMovements()
    {
        return $this->hasMany(IngredientStockMovement::class);
    }

    /**
     * Get all food order ingredients that used this ingredient.
     */
    public function foodOrderIngredients()
    {
        return $this->hasMany(FoodOrderIngredient::class);
    }

    /**
     * Get available batches (active, not depleted, not expired) ordered by FIFO.
     */
    public function availableBatches()
    {
        return $this->batches()
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->where(function($query) {
                $query->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>', now());
            })
            ->orderBy('received_date', 'asc') // FIFO: oldest first
            ->orderBy('expiry_date', 'asc'); // Expiring soon first
    }

    /**
     * Check if stock is low.
     */
    public function isLowStock()
    {
        return $this->current_stock <= $this->min_stock_level;
    }

    /**
     * Get total available quantity from all active batches.
     */
    public function getAvailableQuantityAttribute()
    {
        return $this->availableBatches()->sum('remaining_quantity');
    }
}
