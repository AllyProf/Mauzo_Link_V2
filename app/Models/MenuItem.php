<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'route',
        'url',
        'parent_id',
        'sort_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get parent menu item
     */
    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get child menu items
     */
    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Get business types that have this menu item
     */
    public function businessTypes()
    {
        return $this->belongsToMany(BusinessType::class, 'business_type_menu_items')
            ->withPivot('is_enabled', 'sort_order')
            ->withTimestamps();
    }

    /**
     * Check if menu item is a parent (has children)
     */
    public function isParent()
    {
        return $this->children()->count() > 0;
    }

    /**
     * Get full URL for menu item
     */
    public function getFullUrlAttribute()
    {
        if ($this->route) {
            return route($this->route);
        }
        return $this->url ?? '#';
    }
}
