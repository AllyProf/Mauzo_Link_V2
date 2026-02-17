<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Otp;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'business_name',
        'business_type',
        'phone',
        'address',
        'city',
        'country',
        'is_configured',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_configured' => 'boolean',
        ];
    }

    /**
     * Get the user's active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where(function($query) {
                $query->where('status', 'active')
                      ->orWhere(function($q) {
                          $q->where('status', 'trial')->where('is_trial', true);
                      });
            })
            ->latest();
    }

    /**
     * Get all subscriptions for this user
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get current plan
     */
    public function currentPlan()
    {
        $subscription = $this->activeSubscription;
        return $subscription ? $subscription->plan : null;
    }

    /**
     * Get all invoices for this user
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all payments for this user
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all OTPs for this user
     */
    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Get business types for this user
     */
    public function businessTypes()
    {
        return $this->belongsToMany(BusinessType::class, 'user_business_types')
            ->withPivot('is_primary', 'is_enabled')
            ->withTimestamps();
    }

    /**
     * Get primary business type
     */
    public function primaryBusinessType()
    {
        return $this->businessTypes()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Get enabled business types
     */
    public function enabledBusinessTypes()
    {
        return $this->businessTypes()
            ->wherePivot('is_enabled', true);
    }

    /**
     * Get roles for this user
     */
    public function userRoles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * Get roles owned by this user (for their business)
     */
    public function ownedRoles()
    {
        return $this->hasMany(Role::class, 'user_id');
    }

    /**
     * Get staff members registered by this user
     */
    public function staff()
    {
        return $this->hasMany(Staff::class, 'user_id');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole($roleSlug)
    {
        return $this->userRoles()
            ->where('slug', $roleSlug)
            ->exists();
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($module, $action)
    {
        return $this->userRoles()
            ->whereHas('permissions', function($query) use ($module, $action) {
                $query->where('module', $module)
                      ->where('action', $action);
            })
            ->exists();
    }

    /**
     * Check if user has completed configuration
     */
    public function isConfigured(): bool
    {
        return (bool) $this->is_configured;
    }

    /**
     * Scope to get only admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope to get only customer users
     */
    public function scopeCustomers($query)
    {
        return $query->where('role', 'customer');
    }

    /**
     * Scope to exclude admins (get only customers)
     */
    public function scopeNonAdmins($query)
    {
        return $query->where('role', '!=', 'admin');
    }
}
