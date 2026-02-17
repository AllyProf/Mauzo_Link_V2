<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'category',
        'language',
        'placeholders',
        'description',
        'is_system_template',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_system_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who owns this template (null for system templates)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get campaigns using this template
     */
    public function campaigns()
    {
        return $this->hasMany(SmsCampaign::class, 'template_id');
    }
}
