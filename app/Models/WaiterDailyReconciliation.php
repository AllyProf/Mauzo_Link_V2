<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaiterDailyReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'waiter_id',
        'reconciliation_date',
        'reconciliation_type',
        'total_sales',
        'cash_collected',
        'mobile_money_collected',
        'expected_amount',
        'submitted_amount',
        'difference',
        'status',
        'submitted_at',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'total_sales' => 'decimal:2',
        'cash_collected' => 'decimal:2',
        'mobile_money_collected' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'submitted_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the restaurant owner
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the waiter
     */
    public function waiter()
    {
        return $this->belongsTo(Staff::class, 'waiter_id');
    }

    /**
     * Get the user who verified this reconciliation
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get all orders for this reconciliation
     */
    public function orders()
    {
        return $this->hasMany(BarOrder::class, 'reconciliation_id');
    }

    /**
     * Check if reconciliation is verified
     */
    public function isVerified()
    {
        return $this->status === 'verified';
    }

    /**
     * Check if reconciliation is submitted
     */
    public function isSubmitted()
    {
        return in_array($this->status, ['submitted', 'verified']);
    }
}
