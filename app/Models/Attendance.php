<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'status', // present, absent, late, half_day, leave
        'notes',
        'location', // GPS location if available
        'ip_address',
        'biometric_enroll_id',
        'verify_mode',
        'device_ip',
        'is_biometric',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    /**
     * Get the owner (user) who manages this attendance
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

    /**
     * Calculate working hours
     */
    public function getWorkingHoursAttribute()
    {
        if ($this->check_in_time && $this->check_out_time) {
            return $this->check_in_time->diffInHours($this->check_out_time);
        }
        return 0;
    }

    /**
     * Check if staff is late (after 9 AM)
     */
    public function isLate()
    {
        if ($this->check_in_time) {
            $expectedTime = $this->attendance_date->setTime(9, 0, 0);
            return $this->check_in_time->gt($expectedTime);
        }
        return false;
    }
}

