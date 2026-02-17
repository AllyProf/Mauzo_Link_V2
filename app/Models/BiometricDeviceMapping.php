<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricDeviceMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'enroll_id',
        'device_ip',
        'device_port',
        'is_registered',
        'registered_at',
        'last_sync_at',
        'notes',
    ];

    protected $casts = [
        'is_registered' => 'boolean',
        'registered_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'device_port' => 'integer',
    ];

    /**
     * Get the owner (user) who manages this mapping
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
