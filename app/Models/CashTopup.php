<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTopup extends Model
{
    protected $fillable = [
        'user_id',
        'accountant_id',
        'amount',
        'topup_date',
        'source',
        'notes'
    ];

    protected $casts = [
        'topup_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function accountant()
    {
        return $this->belongsTo(Staff::class, 'accountant_id');
    }
}
