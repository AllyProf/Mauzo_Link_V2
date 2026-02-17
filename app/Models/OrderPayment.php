<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'mobile_money_number',
        'transaction_reference',
        'transaction_id',
        'payment_status',
        'verified_at',
        'verified_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the order for this payment
     */
    public function order()
    {
        return $this->belongsTo(BarOrder::class, 'order_id');
    }

    /**
     * Get the user who verified this payment
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if payment is verified
     */
    public function isVerified()
    {
        return $this->payment_status === 'verified';
    }

    /**
     * Check if payment is pending verification
     */
    public function isPending()
    {
        return $this->payment_status === 'pending';
    }
}
