<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessTypeMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_type_id',
        'menu_item_id',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the business type
     */
    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Get the menu item
     */
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
