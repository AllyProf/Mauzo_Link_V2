<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'request_number',
        'items_list',
        'estimated_amount',
        'issued_amount',
        'status',
        'processed_by',
        'processed_at',
        'reason',
        'notes',
    ];

    protected $casts = [
        'items_list' => 'array',
        'processed_at' => 'datetime',
        'estimated_amount' => 'decimal:2',
        'issued_amount' => 'decimal:2',
    ];

    /**
     * Generate a unique request number.
     */
    public static function generateRequestNo($userId)
    {
        $prefix = 'RQ';
        $year = date('Y');
        $month = date('m');
        
        $lastRequest = self::where('user_id', $userId)
            ->where('request_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('request_number', 'desc')
            ->first();
        
        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function requester()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function processor()
    {
        return $this->belongsTo(Staff::class, 'processed_by');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function progressStatusClasses()
    {
        return match($this->status) {
            'pending' => 'badge-warning',
            'approved' => 'badge-info',
            'issued' => 'badge-success',
            'rejected' => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
