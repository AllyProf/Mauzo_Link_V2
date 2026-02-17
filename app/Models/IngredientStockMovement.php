<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ingredient_id',
        'ingredient_batch_id',
        'movement_type',
        'quantity',
        'unit',
        'from_location',
        'to_location',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Get the owner (user) that owns this movement.
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
     * Get the ingredient batch.
     */
    public function ingredientBatch()
    {
        return $this->belongsTo(IngredientBatch::class);
    }

    /**
     * Get the staff member who created this movement.
     */
    public function createdByStaff()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * Get the polymorphic reference (receipt, order item, etc.).
     */
    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }
}
