<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'filters',
        'customer_count',
        'is_active',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who owns this segment
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
