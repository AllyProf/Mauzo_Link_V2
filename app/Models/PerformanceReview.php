<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'review_period_start',
        'review_period_end',
        'review_date',
        'reviewer_id', // Staff/Manager who conducted the review
        'performance_rating', // 1-5 scale
        'goals_achieved',
        'goals_pending',
        'strengths',
        'areas_for_improvement',
        'training_needs',
        'recommendations',
        'next_review_date',
        'status', // draft, completed, acknowledged
        'staff_acknowledged_at',
        'notes',
    ];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'review_date' => 'date',
        'next_review_date' => 'date',
        'performance_rating' => 'decimal:1',
        'staff_acknowledged_at' => 'datetime',
    ];

    /**
     * Get the owner (user) who manages this review
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the staff member being reviewed
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the reviewer (staff/manager)
     */
    public function reviewer()
    {
        return $this->belongsTo(Staff::class, 'reviewer_id');
    }

    /**
     * Get performance rating label
     */
    public function getRatingLabelAttribute()
    {
        $ratings = [
            1 => 'Poor',
            2 => 'Below Average',
            3 => 'Average',
            4 => 'Good',
            5 => 'Excellent',
        ];
        
        return $ratings[round($this->performance_rating)] ?? 'Not Rated';
    }
}

