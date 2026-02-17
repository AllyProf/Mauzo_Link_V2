<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBusinessType extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_type_id',
        'is_primary',
        'is_enabled',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the business type
     */
    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }
}
