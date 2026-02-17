<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarPayment extends Model
{
    use HasFactory;

    protected $table = 'bar_payments';

    protected $fillable = [
        'user_id',
        'order_id',
        'payment_number',
        'amount',
        'payment_method',
        'status',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Generate a unique payment number.
     */
    public static function generatePaymentNumber($userId)
    {
        $prefix = 'PAY';
        $year = date('Y');
        $month = date('m');
        
        $lastPayment = self::where('user_id', $userId)
            ->where('payment_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('payment_number', 'desc')
            ->first();
        
        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the owner (user) that owns this payment.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the order for this payment.
     */
    public function order()
    {
        return $this->belongsTo(BarOrder::class, 'order_id');
    }

    /**
     * Get the staff member who processed the payment.
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
