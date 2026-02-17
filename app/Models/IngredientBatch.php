<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class IngredientBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ingredient_id',
        'ingredient_receipt_id',
        'batch_number',
        'initial_quantity',
        'remaining_quantity',
        'unit',
        'expiry_date',
        'received_date',
        'cost_per_unit',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'initial_quantity' => 'decimal:2',
        'remaining_quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'expiry_date' => 'date',
        'received_date' => 'date',
    ];

    /**
     * Get the owner (user) that owns this batch.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the ingredient.
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Get the receipt that created this batch.
     */
    public function receipt()
    {
        return $this->belongsTo(IngredientReceipt::class, 'ingredient_receipt_id');
    }

    /**
     * Get all food order ingredients that used this batch.
     */
    public function foodOrderIngredients()
    {
        return $this->hasMany(FoodOrderIngredient::class, 'ingredient_batch_id');
    }

    /**
     * Get all stock movements for this batch.
     */
    public function stockMovements()
    {
        return $this->hasMany(IngredientStockMovement::class, 'ingredient_batch_id');
    }

    /**
     * Check if batch is expired.
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }
        return Carbon::now()->isAfter($this->expiry_date);
    }

    /**
     * Check if batch is depleted.
     */
    public function isDepleted()
    {
        return $this->remaining_quantity <= 0;
    }

    /**
     * Check if batch is available for use.
     */
    public function isAvailable()
    {
        return $this->status === 'active' && !$this->isDepleted() && !$this->isExpired();
    }

    /**
     * Get used quantity (initial - remaining).
     */
    public function getUsedQuantityAttribute()
    {
        return $this->initial_quantity - $this->remaining_quantity;
    }
}
