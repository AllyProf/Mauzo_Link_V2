<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'shift_number',
        'opening_balance',
        'closing_balance',
        'total_sales_cash',
        'total_sales_digital',
        'expected_closing_balance',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_sales_cash' => 'decimal:2',
        'total_sales_digital' => 'decimal:2',
        'expected_closing_balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function orders()
    {
        return $this->hasMany(BarOrder::class, 'shift_id');
    }

    public static function generateShiftNumber($userId)
    {
        $prefix = 'SHF';
        $lastShift = self::where('user_id', $userId)->latest()->first();
        $number = $lastShift ? ((int) str_replace($prefix . '-', '', $lastShift->shift_number) + 1) : 1;
        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
