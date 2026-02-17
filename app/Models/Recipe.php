<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'food_item_id',
        'name',
        'description',
        'instructions',
        'prep_time_minutes',
        'cook_time_minutes',
        'servings',
        'is_active',
    ];

    protected $casts = [
        'prep_time_minutes' => 'integer',
        'cook_time_minutes' => 'integer',
        'servings' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the owner (user) that owns this recipe.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the food item for this recipe.
     */
    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class, 'food_item_id');
    }

    /**
     * Get all ingredients for this recipe.
     */
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity_required', 'unit', 'notes')
            ->withTimestamps();
    }

    /**
     * Get recipe ingredients (pivot table).
     */
    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
