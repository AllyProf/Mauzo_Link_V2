<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodOrderIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'kitchen_order_item_id',
        'ingredient_id',
        'ingredient_batch_id',
        'quantity_used',
        'unit',
        'cost_at_time',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'cost_at_time' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the kitchen order item that uses this ingredient.
     */
    public function kitchenOrderItem()
    {
        return $this->belongsTo(KitchenOrderItem::class, 'kitchen_order_item_id');
    }

    /**
     * Get the ingredient.
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Get the ingredient batch used.
     */
    public function ingredientBatch()
    {
        return $this->belongsTo(IngredientBatch::class);
    }
}
