<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialHandover extends Model
{
    protected $fillable = [
        'user_id',
        'accountant_id', // Performing Staff (Accountant, Chef, Counter)
        'handover_type', // accountant_to_owner, staff_to_accountant
        'recipient_id', // Target Staff (Accountant) or User ID (Owner)
        'department', // food, bar, accounts
        'amount',
        'payment_breakdown', // Detailed platform amounts
        'payment_method',
        'handover_date',
        'status',
        'confirmed_at',
        'notes'
    ];

    protected $casts = [
        'handover_date' => 'date',
        'confirmed_at' => 'datetime',
        'amount' => 'decimal:2',
        'payment_breakdown' => 'array'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'accountant_id');
    }

    public function recipientStaff()
    {
        return $this->belongsTo(Staff::class, 'recipient_id');
    }

    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
