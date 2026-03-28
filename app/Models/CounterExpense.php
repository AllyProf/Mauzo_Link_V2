<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounterExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'staff_shift_id',
        'amount',
        'description',
        'expense_date',
        'payment_method'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function shift()
    {
        return $this->belongsTo(StaffShift::class, 'staff_shift_id');
    }
}
