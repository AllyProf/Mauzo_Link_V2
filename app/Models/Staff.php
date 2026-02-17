<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'full_name',
        'email',
        'gender',
        'nida',
        'phone_number',
        'password',
        'next_of_kin',
        'next_of_kin_phone',
        'location_branch',
        'business_type_id',
        'role_id',
        'salary_paid',
        'religion',
        'nida_attachment',
        'voter_id_attachment',
        'professional_certificate_attachment',
        'is_active',
        'last_login_at',
        'api_token',
        'api_token_expires_at',
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    protected $casts = [
        'salary_paid' => 'decimal:2',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'api_token_expires_at' => 'datetime',
    ];

    /**
     * Get the owner (user) who registered this staff
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the business type for this staff
     */
    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * Get the role assigned to this staff
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the biometric device mapping for this staff
     */
    public function biometricMapping()
    {
        return $this->hasOne(BiometricDeviceMapping::class, 'staff_id');
    }

    /**
     * Generate unique staff ID
     */
    public static function generateStaffId($userId)
    {
        $prefix = 'STF';
        $year = date('Y');
        $month = date('m');
        
        // Get last staff ID for this user
        $lastStaff = self::where('user_id', $userId)
            ->where('staff_id', 'like', $prefix . $year . $month . '%')
            ->orderBy('staff_id', 'desc')
            ->first();
        
        if ($lastStaff) {
            // Extract number from last staff ID
            $lastNumber = (int) substr($lastStaff->staff_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Extract last name from full name and convert to uppercase
     */
    public static function generatePasswordFromLastName($fullName)
    {
        $nameParts = explode(' ', trim($fullName));
        $lastName = end($nameParts);
        return strtoupper($lastName);
    }

    /**
     * Get all daily reconciliations for this waiter
     */
    public function dailyReconciliations()
    {
        return $this->hasMany(WaiterDailyReconciliation::class, 'waiter_id');
    }

    /**
     * Get all SMS notifications sent to this waiter
     */
    public function smsNotifications()
    {
        return $this->hasMany(WaiterSmsNotification::class, 'waiter_id');
    }

    /**
     * Get all orders placed by this waiter
     */
    public function orders()
    {
        return $this->hasMany(BarOrder::class, 'waiter_id');
    }

    /**
     * Get all attendance records for this staff
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'staff_id');
    }

    /**
     * Get all leave requests for this staff
     */
    public function leaves()
    {
        return $this->hasMany(Leave::class, 'staff_id');
    }

    /**
     * Get all payroll records for this staff
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'staff_id');
    }

    /**
     * Get all performance reviews for this staff
     */
    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class, 'staff_id');
    }
}
