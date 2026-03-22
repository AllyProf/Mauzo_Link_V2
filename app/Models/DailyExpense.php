<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyExpense extends Model
{
    protected $fillable = [
        'daily_cash_ledger_id',
        'user_id',
        'logged_by',
        'category',
        'description',
        'amount',
        'payment_method',
        'is_approved'
    ];

    public function ledger()
    {
        return $this->belongsTo(DailyCashLedger::class, 'daily_cash_ledger_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logger()
    {
        return $this->belongsTo(Staff::class, 'logged_by');
    }
}
