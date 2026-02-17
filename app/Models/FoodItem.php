<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'variant_name',
        'description',
        'image',
        'price',
        'variants',
        'prep_time_minutes',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'prep_time_minutes' => 'integer',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
        'variants' => 'array',
    ];

    /**
     * Get the owner (user) that owns this food item.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the recipe for this food item.
     */
    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'food_item_id');
    }

    /**
     * Get full name with variant
     */
    public function getFullNameAttribute()
    {
        if ($this->variant_name) {
            return $this->name . ' (' . $this->variant_name . ')';
        }
        return $this->name;
    }
}
