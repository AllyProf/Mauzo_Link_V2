<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_variant_id',
        'transfer_number',
        'quantity_requested',
        'total_units',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'notes',
        'rejection_reason',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'total_units' => 'integer',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Generate a unique transfer number.
     */
    public static function generateTransferNumber($userId)
    {
        $prefix = 'ST';
        $year = date('Y');
        $month = date('m');
        
        $lastTransfer = self::where('user_id', $userId)
            ->where('transfer_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('transfer_number', 'desc')
            ->first();
        
        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the owner (user) that owns this transfer.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the product variant for this transfer.
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the staff member who requested the transfer.
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the staff member who approved the transfer.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the accountant who verified the transfer.
     */
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Check if transfer is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transfer is approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if transfer is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transfer is prepared.
     */
    public function isPrepared()
    {
        return $this->status === 'prepared';
    }

    /**
     * Check if transfer is verified by accountant.
     */
    public function isVerified()
    {
        return !is_null($this->verified_at) && !is_null($this->verified_by);
    }

    /**
     * Get all sales for this transfer.
     */
    public function transferSales()
    {
        return $this->hasMany(TransferSale::class);
    }
}
