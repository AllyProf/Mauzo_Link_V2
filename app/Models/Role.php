<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'is_system_role',
        'is_active',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who owns this role
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get users with this role
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withTimestamps();
    }

    /**
     * Get permissions for this role
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission($module, $action)
    {
        // If permissions are loaded, check the collection first (faster)
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains(function($permission) use ($module, $action) {
                return $permission->module === $module && $permission->action === $action;
            });
        }
        
        // Otherwise, query the database
        return $this->permissions()
            ->where('module', $module)
            ->where('action', $action)
            ->exists();
    }
}
