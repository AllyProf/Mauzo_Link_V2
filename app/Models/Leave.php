<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'leave_type', // annual, sick, casual, emergency, unpaid
        'start_date',
        'end_date',
        'days',
        'reason',
        'status', // pending, approved, rejected, cancelled
        'approved_by',
        'approved_at',
        'rejection_reason',
        'attachment', // Leave application document
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'integer',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the owner (user) who manages this leave
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the user who approved this leave
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if leave is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if leave is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }
}

