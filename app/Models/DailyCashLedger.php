<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCashLedger extends Model
{
    protected $fillable = [
        'user_id',
        'accountant_id',
        'ledger_date',
        'opening_cash',
        'total_cash_received',
        'total_digital_received',
        'total_expenses',
        'expected_closing_cash',
        'actual_closing_cash',
        'profit_generated',
        'profit_submitted_to_boss',
        'carried_forward',
        'status',
        'closed_at'
    ];

    protected $casts = [
        'ledger_date' => 'date',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accountant()
    {
        return $this->belongsTo(Staff::class, 'accountant_id');
    }

    public function expenses()
    {
        return $this->hasMany(DailyExpense::class);
    }
}
