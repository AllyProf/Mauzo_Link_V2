<?php

namespace App\Http\Controllers\Traits;

use App\Models\Staff;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

trait HandlesStaffPermissions
{
    /**
     * Get the current user or staff owner
     */
    protected function getCurrentUser()
    {
        if (session('is_staff')) {
            $staff = Staff::find(session('staff_id'));
            return $staff ? $staff->owner : null;
        }
        
        return Auth::user();
    }

    /**
     * Get the owner ID (for staff, get their owner's ID)
     */
    protected function getOwnerId()
    {
        if (session('is_staff')) {
            return session('staff_user_id');
        }
        
        $user = Auth::user();
        return $user ? $user->id : null;
    }

    /**
     * Check if current user/staff has permission
     */
    protected function hasPermission($module, $action)
    {
        // Check if staff member
        if (session('is_staff')) {
            $staffId = session('staff_id');
            \Log::info('hasPermission called for staff', [
                'staff_id' => $staffId,
                'module' => $module,
                'action' => $action
            ]);
            
            $staff = Staff::with('role.permissions')->find($staffId);
            
            if (!$staff || !$staff->is_active) {
                \Log::warning('Staff not found or inactive', [
                    'staff_id' => $staffId,
                    'staff_exists' => $staff !== null,
                    'is_active' => $staff->is_active ?? false
                ]);
                return false;
            }

            // Get staff's role
            $role = $staff->role;
            
            if (!$role) {
                \Log::warning('Staff has no role assigned', [
                    'staff_id' => $staffId,
                    'role_id' => $staff->role_id
                ]);
                return false;
            }

            // Manager role has all permissions
            // Check both name and slug for exact match (case-insensitive)
            $roleName = strtolower(trim($role->name ?? ''));
            $roleSlug = strtolower(trim($role->slug ?? ''));
            
            \Log::info('Checking role for Manager', [
                'staff_id' => $staff->id,
                'role_id' => $role->id,
                'role_name' => $role->name,
                'role_slug' => $role->slug,
                'role_name_lower' => $roleName,
                'role_slug_lower' => $roleSlug
            ]);
            
            // Check if role is Manager (exact match only - not "HR Manager" or similar)
            if ($roleName === 'manager' || $roleSlug === 'manager') {
                \Log::info('✅ Manager role detected - granting all permissions', [
                    'staff_id' => $staff->id,
                    'role_name' => $role->name,
                    'role_slug' => $role->slug,
                    'module' => $module,
                    'action' => $action
                ]);
                return true;
            }

            // Ensure permissions are loaded
            if (!$role->relationLoaded('permissions')) {
                $role->load('permissions');
            }

            // Check permission through role
            $hasPermission = $role->hasPermission($module, $action);
            \Log::info('Permission check result', [
                'staff_id' => $staff->id,
                'role_name' => $role->name,
                'module' => $module,
                'action' => $action,
                'has_permission' => $hasPermission
            ]);
            return $hasPermission;
        }

        // Regular user (not staff)
        $user = Auth::user();
        
        if (!$user) {
            \Log::warning('hasPermission called but no user authenticated', [
                'module' => $module,
                'action' => $action
            ]);
            return false;
        }

        // Check if user is owner or admin (owners and admins have all permissions)
        if ($user->role === 'customer' || $user->role === 'admin' || $user->hasRole('owner')) {
            \Log::info('Owner or Admin user detected - granting all permissions', [
                'user_id' => $user->id,
                'role' => $user->role,
                'module' => $module,
                'action' => $action
            ]);
            return true;
        }

        // Check user's permissions
        $hasPermission = $user->hasPermission($module, $action);
        \Log::info('Regular user permission check', [
            'user_id' => $user->id,
            'module' => $module,
            'action' => $action,
            'has_permission' => $hasPermission
        ]);
        return $hasPermission;
    }

    /**
     * Check if current user/staff has a specific role
     */
    protected function hasRole($roleSlug)
    {
        // Staff members don't have user roles
        if (session('is_staff')) {
            return false;
        }

        $user = Auth::user();
        return $user ? $user->hasRole($roleSlug) : false;
    }

    /**
     * Get current staff member if logged in as staff
     */
    protected function getCurrentStaff()
    {
        if (session('is_staff')) {
            return Staff::find(session('staff_id'));
        }
        
        return null;
    }
}

