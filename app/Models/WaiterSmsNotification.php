<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaiterSmsNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'waiter_id',
        'order_id',
        'phone_number',
        'message',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the waiter
     */
    public function waiter()
    {
        return $this->belongsTo(Staff::class, 'waiter_id');
    }

    /**
     * Get the order
     */
    public function order()
    {
        return $this->belongsTo(BarOrder::class, 'order_id');
    }

    /**
     * Check if SMS was sent successfully
     */
    public function isSent()
    {
        return $this->status === 'sent';
    }

    /**
     * Check if SMS failed
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }
}
