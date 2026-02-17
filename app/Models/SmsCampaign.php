<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'message',
        'type',
        'template_id',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'success_count',
        'estimated_cost',
        'actual_cost',
        'scheduled_at',
        'sent_at',
        'completed_at',
        'recipient_filters',
        'ab_test_variants',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'recipient_filters' => 'array',
        'ab_test_variants' => 'array',
    ];

    /**
     * Get the user who owns this campaign
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template used for this campaign
     */
    public function template()
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    /**
     * Get all recipients for this campaign
     */
    public function recipients()
    {
        return $this->hasMany(SmsCampaignRecipient::class, 'campaign_id');
    }

    /**
     * Get successful recipients
     */
    public function successfulRecipients()
    {
        return $this->recipients()->where('status', 'sent')->orWhere('status', 'delivered');
    }

    /**
     * Get failed recipients
     */
    public function failedRecipients()
    {
        return $this->recipients()->where('status', 'failed');
    }
}
