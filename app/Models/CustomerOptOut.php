<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOptOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'customer_name',
        'reason',
        'opted_out_at',
    ];

    protected $casts = [
        'opted_out_at' => 'datetime',
    ];

    /**
     * Get the user who owns this opt-out record
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
