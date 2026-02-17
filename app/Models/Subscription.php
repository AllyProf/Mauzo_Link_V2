<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'is_trial',
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_trial' => 'boolean',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan for this subscription
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' 
            || ($this->status === 'trial' && $this->isTrialValid())
            || ($this->status === 'pending' && $this->plan && $this->plan->slug === 'free'); // Allow pending free plans temporarily
    }

    /**
     * Check if trial is still valid
     */
    public function isTrialValid(): bool
    {
        if (!$this->is_trial || !$this->trial_ends_at) {
            return false;
        }
        return Carbon::now()->lessThanOrEqualTo($this->trial_ends_at);
    }

    /**
     * Get days remaining in trial
     */
    public function getTrialDaysRemaining(): int
    {
        if (!$this->is_trial || !$this->trial_ends_at) {
            return 0;
        }
        return max(0, Carbon::now()->diffInDays($this->trial_ends_at, false));
    }
}


