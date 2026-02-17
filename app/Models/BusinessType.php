<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get users with this business type
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_business_types')
            ->withPivot('is_primary', 'is_enabled')
            ->withTimestamps();
    }

    /**
     * Get menu items for this business type
     */
    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'business_type_menu_items')
            ->withPivot('is_enabled', 'sort_order')
            ->withTimestamps()
            ->orderBy('business_type_menu_items.sort_order');
    }

    /**
     * Get enabled menu items for this business type
     */
    public function enabledMenuItems()
    {
        return $this->menuItems()
            ->wherePivot('is_enabled', true);
    }
}
