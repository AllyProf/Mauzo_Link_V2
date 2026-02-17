<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'food_item_id', // Link to food_items table
        'food_item_name', // Keep for backward compatibility
        'variant_name',
        'quantity',
        'unit_price',
        'total_price',
        'special_instructions',
        'status',
        'prepared_by',
        'prepared_at',
        'ready_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'prepared_at' => 'datetime',
        'ready_at' => 'datetime',
    ];

    /**
     * Get the order that owns this kitchen item.
     */
    public function order()
    {
        return $this->belongsTo(BarOrder::class, 'order_id');
    }

    /**
     * Get the food item for this kitchen order item.
     */
    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class, 'food_item_id');
    }

    /**
     * Get the chef who prepared this item.
     */
    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Get all ingredients used for this kitchen order item.
     */
    public function ingredients()
    {
        return $this->hasMany(FoodOrderIngredient::class, 'kitchen_order_item_id');
    }

    /**
     * Get the recipe for this food item (if available).
     */
    public function recipe()
    {
        return $this->hasOneThrough(
            Recipe::class,
            FoodItem::class,
            'id', // Foreign key on food_items table
            'food_item_id', // Foreign key on recipes table
            'food_item_id', // Local key on kitchen_order_items table
            'id' // Local key on food_items table
        );
    }

    /**
     * Check if item is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if item is preparing.
     */
    public function isPreparing()
    {
        return $this->status === 'preparing';
    }

    /**
     * Check if item is ready.
     */
    public function isReady()
    {
        return $this->status === 'ready';
    }

    /**
     * Check if item is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}
