<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'phone_number',
        'customer_name',
        'personalized_message',
        'status',
        'error_message',
        'sms_provider_response',
        'sent_at',
        'delivered_at',
        'cost',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the campaign this recipient belongs to
     */
    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }
}
