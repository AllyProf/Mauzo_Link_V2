<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashIssue extends Model
{
    protected $fillable = [
        'user_id',
        'issued_by',
        'staff_id',
        'amount',
        'purpose',
        'status',
        'issue_date',
        'notes'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function recipient()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
