<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use Illuminate\Http\Request;
use App\Models\BusinessType;
use App\Models\Role;
use App\Models\Permission;
use App\Models\MenuItem;
use App\Models\UserBusinessType;
use App\Models\UserRole;
use App\Models\RolePermission;
use App\Models\BusinessTypeMenuItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessConfigurationController extends Controller
{
    use HandlesStaffPermissions;
    /**
     * Show configuration wizard
     */
    public function index()
    {
        // Staff members should not access business configuration setup
        if (session('is_staff')) {
            return redirect()->route('dashboard')
                ->with('error', 'Staff members cannot access business configuration. Please contact the business owner.');
        }
        
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if user can access configuration based on plan
        if (!$this->canAccessConfiguration($user)) {
            return redirect()->route('dashboard')
                ->with('error', 'Please complete your payment to access business configuration.');
        }

        // Check if already configured
        if ($user->is_configured) {
            return redirect()->route('dashboard')
                ->with('info', 'Your business is already configured.');
        }

        $step = request()->get('step', 1);
        $maxStep = 4;

        // Redirect to appropriate step
        if ($step == 1) {
            return $this->step1(request());
        } elseif ($step == 2) {
            return $this->step2(request());
        } elseif ($step == 3) {
            return $this->step3(request());
        } elseif ($step == 4) {
            return $this->step4(request());
        }

        return view('business-configuration.wizard', compact('step', 'maxStep'));
    }

    /**
     * Step 1: Business Information
     */
    public function step1(Request $request)
    {
        // Staff members should not access business configuration setup
        if (session('is_staff')) {
            return redirect()->route('dashboard')
                ->with('error', 'Staff members cannot access business configuration. Please contact the business owner.');
        }
        
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'business_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'country' => 'required|string|max:100',
            ]);

            $user->update($validated);
            
            // Mark step 1 as completed
            session(['config_step_1' => true]);

            return redirect()->route('business-configuration.step2');
        }

        $step = 1;
        $maxStep = 4;
        return view('business-configuration.wizard', compact('step', 'maxStep'))->with('include_step', 'step1');
    }

    /**
     * Step 2: Business Types Selection
     */
    public function step2(Request $request)
    {
        // Staff members should not access business configuration setup
        if (session('is_staff')) {
            return redirect()->route('dashboard')
                ->with('error', 'Staff members cannot access business configuration. Please contact the business owner.');
        }
        
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if step 1 is completed
        if (!session('config_step_1') && !$user->business_name) {
            return redirect()->route('business-configuration.step1')
                ->with('error', 'Please complete Step 1 first.');
        }

        $businessTypes = BusinessType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        $selectedTypes = $user->businessTypes()->pluck('business_types.id')->toArray();

        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'business_types' => 'required|array|min:1',
                'business_types.*' => 'exists:business_types,id',
            ]);

            // Remove existing business types
            $user->businessTypes()->detach();

            // Attach selected business types
            // First selected business type will be the primary one
            $businessTypes = $validated['business_types'];
            $primaryBusinessTypeId = $businessTypes[0]; // First one is primary

            foreach ($businessTypes as $index => $businessTypeId) {
                $isPrimary = $businessTypeId == $primaryBusinessTypeId;
                $user->businessTypes()->attach($businessTypeId, [
                    'is_primary' => $isPrimary,
                    'is_enabled' => true,
                ]);
            }

            // Auto-create default roles for selected business types
            $this->autoCreateDefaultRoles($user, $businessTypes);

            // Mark step 2 as completed
            session(['config_step_2' => true]);

            return redirect()->route('business-configuration.step3');
        }

        $step = 2;
        $maxStep = 4;
        return view('business-configuration.wizard', compact('step', 'maxStep', 'businessTypes', 'selectedTypes'))->with('include_step', 'step2');
    }

    /**
     * Step 3: Roles & Permissions Setup
     */
    public function step3(Request $request)
    {
        // Staff members should not access business configuration setup
        if (session('is_staff')) {
            return redirect()->route('dashboard')
                ->with('error', 'Staff members cannot access business configuration. Please contact the business owner.');
        }
        
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if step 2 is completed
        if (!session('config_step_2') && $user->businessTypes()->count() == 0) {
            return redirect()->route('business-configuration.step2')
                ->with('error', 'Please complete Step 2 first.');
        }

        $plan = $user->currentPlan();
        $isBasicPlan = $plan && $plan->slug === 'basic';

        // Get all permissions
        $permissions = Permission::where('is_active', true)
            ->orderBy('module')
            ->orderBy('action')
            ->get()
            ->groupBy('module');

        // Get existing roles
        $existingRoles = $user->ownedRoles()->where('is_active', true)->get();

        // Get business types to suggest appropriate roles
        $businessTypes = $user->businessTypes()->pluck('slug')->toArray();
        $suggestedRoles = \App\Services\RoleSuggestionService::getSuggestedRolesForBusinessTypes($businessTypes);

        if ($request->isMethod('post')) {
            // For Basic Plan, only create Owner role
            if ($isBasicPlan) {
                $this->createOwnerRole($user, $permissions->flatten());
            } else {
                // For Free/Pro plans, allow custom roles
                $validated = $request->validate([
                    'roles' => 'required|array|min:1',
                    'roles.*.name' => 'required|string|max:255',
                ]);

                // Get existing role IDs to track what should be kept
                $existingRoleIds = $existingRoles->pluck('id')->toArray();
                $submittedRoleIds = [];

                // Create or update roles
                foreach ($validated['roles'] as $roleKey => $roleData) {
                    // Check if this is an existing role (has numeric ID) or new role
                    if (is_numeric($roleKey)) {
                        // Existing role - update it
                        $role = Role::where('id', $roleKey)
                            ->where('user_id', $user->id)
                            ->first();
                        
                        if ($role) {
                            $role->update([
                                'name' => $roleData['name'],
                                'description' => $roleData['description'] ?? null,
                            ]);
                            $submittedRoleIds[] = $role->id;
                        }
                    } else {
                        // New role - create it
                        $role = Role::create([
                            'user_id' => $user->id,
                            'name' => $roleData['name'],
                            'slug' => Str::slug($roleData['name'] . '-' . $user->id . '-' . time()),
                            'description' => $roleData['description'] ?? null,
                            'is_system_role' => false,
                            'is_active' => true,
                        ]);
                        $submittedRoleIds[] = $role->id;
                    }

                    // Assign permissions
                    if (isset($roleData['permissions']) && is_array($roleData['permissions']) && count($roleData['permissions']) > 0) {
                        // Sync the selected permissions
                        \Log::info('Syncing permissions for role', [
                            'role_id' => $role->id,
                            'role_name' => $role->name,
                            'permissions_count' => count($roleData['permissions']),
                            'permissions' => $roleData['permissions']
                        ]);
                        $role->permissions()->sync($roleData['permissions']);
                        
                        // Verify sync worked
                        $syncedCount = $role->permissions()->count();
                        \Log::info('Permissions synced', [
                            'role_id' => $role->id,
                            'synced_count' => $syncedCount
                        ]);
                    } else {
                        // If no permissions selected, give all permissions (for Owner)
                        if (strtolower($roleData['name']) === 'owner') {
                            $role->permissions()->sync(Permission::pluck('id'));
                        } else {
                            // For other roles, clear permissions if none selected
                            $role->permissions()->sync([]);
                            \Log::warning('No permissions selected for role', [
                                'role_id' => $role->id,
                                'role_name' => $role->name
                            ]);
                        }
                    }
                }

                // Delete roles that were removed
                $rolesToDelete = array_diff($existingRoleIds, $submittedRoleIds);
                if (!empty($rolesToDelete)) {
                    Role::whereIn('id', $rolesToDelete)
                        ->where('user_id', $user->id)
                        ->delete();
                }

                // Assign first role (usually Owner) to current user if not already assigned
                $firstRole = Role::whereIn('id', $submittedRoleIds)
                    ->where('user_id', $user->id)
                    ->first();
                if ($firstRole && !$user->userRoles()->where('role_id', $firstRole->id)->exists()) {
                    $user->userRoles()->attach($firstRole->id);
                }
            }

            // Mark step 3 as completed
            session(['config_step_3' => true]);

            return redirect()->route('business-configuration.step4')
                ->with('success', 'Roles and permissions saved successfully!');
        }

        $step = 3;
        $maxStep = 4;
        return view('business-configuration.wizard', compact('step', 'maxStep', 'permissions', 'existingRoles', 'isBasicPlan', 'suggestedRoles'))->with('include_step', 'step3');
    }

    /**
     * Step 4: Review & Complete (removed System Settings step)
     */
    public function step4(Request $request)
    {
        // Staff members should not access business configuration setup
        if (session('is_staff')) {
            return redirect()->route('dashboard')
                ->with('error', 'Staff members cannot access business configuration. Please contact the business owner.');
        }
        
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check if step 3 is completed
        if (!session('config_step_3') && $user->userRoles()->count() == 0) {
            return redirect()->route('business-configuration.step3')
                ->with('error', 'Please complete Step 3 first.');
        }

        if ($request->isMethod('post')) {
            // Mark user as configured
            $user->update(['is_configured' => true]);

            // Generate menu items based on selected business types
            $this->generateMenuItems($user);

            // Clear session data
            session()->forget(['config_step_1', 'config_step_2', 'config_step_3', 'business_settings']);

            return redirect()->route('dashboard')
                ->with('success', 'Business configuration completed successfully!');
        }

        // Get summary data
        $businessTypes = $user->businessTypes;
        $roles = $user->userRoles()->with('permissions')->get();

        $step = 4;
        $maxStep = 4;
        return view('business-configuration.wizard', compact('step', 'maxStep', 'businessTypes', 'roles'))->with('include_step', 'step5');
    }

    /**
     * Edit business configuration
     */
    public function edit()
    {
        \Log::info('BusinessConfigurationController@edit called', [
            'is_staff' => session('is_staff'),
            'staff_id' => session('staff_id'),
            'staff_role_id' => session('staff_role_id'),
            'auth_check' => auth()->check(),
            'user_id' => auth()->id()
        ]);
        
        $user = $this->getCurrentUser();
        
        if (!$user) {
            \Log::warning('No user found in BusinessConfigurationController@edit');
            return redirect()->route('login');
        }
        
        \Log::info('User found in BusinessConfigurationController@edit', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'is_staff' => session('is_staff')
        ]);
        
        // Check permission for staff members
        if (session('is_staff')) {
            // Get staff details for logging
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            \Log::info('Staff member accessing edit', [
                'staff_id' => $staff->id ?? null,
                'staff_name' => $staff->full_name ?? null,
                'role_id' => $staff->role_id ?? null,
                'role_name' => $staff->role->name ?? null,
                'role_slug' => $staff->role->slug ?? null
            ]);
            
            // Staff members need settings.edit permission to access business configuration
            // Manager role has all permissions, so this check will pass for managers
            $hasPermission = $this->hasPermission('settings', 'edit');
            \Log::info('Permission check result', [
                'module' => 'settings',
                'action' => 'edit',
                'has_permission' => $hasPermission
            ]);
            
            if (!$hasPermission) {
                \Log::warning('Staff member denied access to business configuration', [
                    'staff_id' => session('staff_id'),
                    'role_id' => session('staff_role_id'),
                    'role_name' => $staff->role->name ?? null,
                    'has_permission' => $hasPermission
                ]);
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have permission to edit business configuration.');
            }
            
            \Log::info('Staff member granted access to business configuration');
            
            // For staff members, skip the isConfigured check - they can edit even if owner hasn't completed initial setup
            // The owner's configuration status doesn't prevent staff from managing roles/permissions
        } else {
            // Only check isConfigured for regular users (owners), not staff
            if (!$user->isConfigured()) {
                \Log::info('User not configured, redirecting to configuration index', [
                    'user_id' => $user->id
                ]);
                return redirect()->route('business-configuration.index')
                    ->with('info', 'Please complete your business configuration first.');
            }
        }

        // Get current configuration data
        $businessTypes = BusinessType::where('is_active', true)->orderBy('sort_order')->get();
        $selectedTypes = $user->businessTypes()->pluck('business_types.id')->toArray();
        
        // Load roles with permissions - ensure fresh data (no cache)
        $existingRoles = $user->ownedRoles()
            ->where('is_active', true)
            ->get()
            ->map(function($role) {
                // Force fresh load of permissions
                $role->unsetRelation('permissions');
                $role->load(['permissions' => function($query) {
                    $query->where('is_active', true);
                }]);
                return $role;
            });
        
        // Debug: Log permissions for each role
        foreach ($existingRoles as $role) {
            \Log::info('Role permissions loaded in edit', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_count' => $role->permissions->count(),
                'permission_ids' => $role->permissions->pluck('id')->toArray()
            ]);
        }
        
        $permissions = Permission::where('is_active', true)
            ->orderBy('module')
            ->orderBy('action')
            ->get()
            ->groupBy('module');

        // Get business types to suggest appropriate roles
        $businessTypesSlugs = $user->businessTypes()->pluck('slug')->toArray();
        $suggestedRoles = \App\Services\RoleSuggestionService::getSuggestedRolesForBusinessTypes($businessTypesSlugs);

        return view('business-configuration.edit', compact(
            'user', 
            'businessTypes', 
            'selectedTypes', 
            'existingRoles', 
            'permissions',
            'suggestedRoles'
        ));
    }

    /**
     * Update business configuration
     */
    public function update(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission for staff members
        if (session('is_staff')) {
            // Staff members need settings.edit permission to update business configuration
            if (!$this->hasPermission('settings', 'edit')) {
                return redirect()->route('dashboard')
                    ->with('error', 'You do not have permission to update business configuration.');
            }
        }

        if (!$user->isConfigured()) {
            return redirect()->route('business-configuration.index');
        }

        // Update business information
        if ($request->has('business_name')) {
            $user->update($request->only([
                'business_name', 'phone', 'address', 'city', 'country'
            ]));
        }

        // Update business types (allow 0 business types)
        // Always process business types - if not in request, it means all are unchecked
        $validated = $request->validate([
            'business_types' => 'nullable|array',
            'business_types.*' => 'exists:business_types,id',
        ]);

        // Remove all existing business types
        $user->businessTypes()->detach();

        // If business types are provided and not empty, attach them
        $businessTypes = $validated['business_types'] ?? [];
        if (!empty($businessTypes) && is_array($businessTypes)) {
            $primaryBusinessTypeId = $businessTypes[0];

            foreach ($businessTypes as $businessTypeId) {
                $isPrimary = $businessTypeId == $primaryBusinessTypeId;
                $user->businessTypes()->attach($businessTypeId, [
                    'is_primary' => $isPrimary,
                    'is_enabled' => true,
                ]);
            }

            // Regenerate menu items only if business types exist
            $this->generateMenuItems($user);
            
            // Auto-create default roles for selected business types
            $this->autoCreateDefaultRoles($user, $businessTypes);
        }

        // Update roles (same logic as step 3)
        if ($request->has('roles')) {
            $plan = $user->currentPlan();
            $isBasicPlan = $plan && $plan->slug === 'basic';

            if (!$isBasicPlan) {
                // Log the raw request data for debugging
                \Log::info('Update roles request data', [
                    'roles_data' => $request->input('roles'),
                    'all_request' => $request->all()
                ]);

                $validated = $request->validate([
                    'roles' => 'required|array|min:1',
                    'roles.*.name' => 'required|string|max:255',
                    'roles.*.permissions' => 'nullable|array',
                    'roles.*.permissions.*' => 'nullable|integer|exists:permissions,id',
                ]);

                // Wrap in transaction to ensure data consistency
                DB::beginTransaction();
                try {
                    $existingRoles = $user->ownedRoles()->where('is_active', true)->get();
                    $existingRoleIds = $existingRoles->pluck('id')->toArray();
                    $submittedRoleIds = [];

                    foreach ($validated['roles'] as $roleKey => $roleData) {
                    // Skip roles marked for deletion
                    if (isset($roleData['_delete']) && $roleData['_delete'] == '1') {
                        \Log::info('Skipping role marked for deletion', ['role_key' => $roleKey]);
                        continue;
                    }
                    
                    // Debug: Log everything about this role
                    \Log::info('=== Processing role ===', [
                        'role_key' => $roleKey,
                        'role_data_keys' => array_keys($roleData),
                        'has_permissions_key' => isset($roleData['permissions']),
                        'permissions_type' => gettype($roleData['permissions'] ?? 'not set'),
                        'permissions_value' => $roleData['permissions'] ?? 'not set',
                        'permissions_count' => isset($roleData['permissions']) && is_array($roleData['permissions']) ? count($roleData['permissions']) : 0,
                        'full_role_data' => $roleData
                    ]);

                    if (is_numeric($roleKey)) {
                        $role = Role::where('id', $roleKey)
                            ->where('user_id', $user->id)
                            ->first();
                        
                        if ($role) {
                            $role->update([
                                'name' => $roleData['name'],
                                'description' => $roleData['description'] ?? null,
                            ]);
                            $submittedRoleIds[] = $role->id;
                        } else {
                            \Log::warning('Role not found', ['role_id' => $roleKey, 'user_id' => $user->id]);
                            continue;
                        }
                    } else {
                        $role = Role::create([
                            'user_id' => $user->id,
                            'name' => $roleData['name'],
                            'slug' => Str::slug($roleData['name'] . '-' . $user->id . '-' . time()),
                            'description' => $roleData['description'] ?? null,
                            'is_system_role' => false,
                            'is_active' => true,
                        ]);
                        $submittedRoleIds[] = $role->id;
                    }

                    // Always sync permissions - if not provided, clear them
                    $permissionIds = [];
                    
                    if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
                        // Filter out any empty values, null values, and ensure all are valid integers
                        $permissionIds = array_filter($roleData['permissions'], function($id) {
                            // Remove empty strings, null, false, and non-numeric values
                            return $id !== '' && $id !== null && $id !== false && is_numeric($id) && (int)$id > 0;
                        });
                        // Convert to integers
                        $permissionIds = array_map('intval', $permissionIds);
                        // Remove duplicates
                        $permissionIds = array_unique($permissionIds);
                        // Re-index array
                        $permissionIds = array_values($permissionIds);
                    }
                    
                    \Log::info('Permission IDs to sync', [
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'permission_ids' => $permissionIds,
                        'count' => count($permissionIds)
                    ]);
                    
                    // Validate that all permission IDs exist in the database
                    $validPermissionIds = [];
                    if (!empty($permissionIds)) {
                        $validPermissionIds = Permission::whereIn('id', $permissionIds)
                            ->where('is_active', true)
                            ->pluck('id')
                            ->toArray();
                        
                        if (count($validPermissionIds) !== count($permissionIds)) {
                            \Log::warning('Some permission IDs are invalid', [
                                'role_id' => $role->id,
                                'submitted_ids' => $permissionIds,
                                'valid_ids' => $validPermissionIds
                            ]);
                        }
                    }
                    
                    \Log::info('Syncing permissions for role (update)', [
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'permissions_count' => count($validPermissionIds),
                        'permission_ids' => $validPermissionIds,
                        'has_permissions_in_request' => isset($roleData['permissions']),
                        'raw_permissions' => $roleData['permissions'] ?? 'not set',
                        'raw_role_data' => $roleData
                    ]);

                    // Always sync permissions (even if empty array to clear them)
                    try {
                        \Log::info('About to sync permissions', [
                            'role_id' => $role->id,
                            'valid_permission_ids' => $validPermissionIds,
                            'count' => count($validPermissionIds)
                        ]);
                        
                        // Clear cache first
                        $role->unsetRelation('permissions');
                        
                        // Get current permissions before sync
                        $beforeSync = DB::table('role_permissions')
                            ->where('role_id', $role->id)
                            ->pluck('permission_id')
                            ->toArray();
                        
                        \Log::info('Before sync', ['permissions' => $beforeSync]);
                        
                        // Use sync which handles attach/detach automatically
                        $syncResult = $role->permissions()->sync($validPermissionIds);
                        
                        \Log::info('Sync result', ['result' => $syncResult]);
                        
                        // Immediately verify in database
                        $afterSync = DB::table('role_permissions')
                            ->where('role_id', $role->id)
                            ->pluck('permission_id')
                            ->toArray();
                        
                        \Log::info('After sync (database)', [
                            'permissions' => $afterSync,
                            'count' => count($afterSync),
                            'expected' => $validPermissionIds,
                            'expected_count' => count($validPermissionIds)
                        ]);
                        
                        // Force reload from database
                        $role->refresh();
                        $role->load('permissions');
                        
                        $relationshipIds = $role->permissions->pluck('id')->toArray();
                        \Log::info('After sync (relationship)', [
                            'permissions' => $relationshipIds,
                            'count' => count($relationshipIds)
                        ]);
                        
                        // Sort arrays for comparison
                        sort($afterSync);
                        sort($validPermissionIds);
                        
                        // Verify they match
                        if ($afterSync !== $validPermissionIds) {
                            \Log::error('PERMISSION SYNC MISMATCH', [
                                'role_id' => $role->id,
                                'role_name' => $role->name,
                                'expected' => $validPermissionIds,
                                'expected_count' => count($validPermissionIds),
                                'actual' => $afterSync,
                                'actual_count' => count($afterSync)
                            ]);
                            // Try to re-sync one more time
                            $role->permissions()->sync($validPermissionIds);
                            \Log::info('Re-synced permissions after mismatch');
                        }
                        
                        \Log::info('Permissions synced successfully', [
                            'role_id' => $role->id,
                            'role_name' => $role->name,
                            'permissions' => $afterSync
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to sync permissions', [
                            'role_id' => $role->id,
                            'role_name' => $role->name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                }

                    $rolesToDelete = array_diff($existingRoleIds, $submittedRoleIds);
                    if (!empty($rolesToDelete)) {
                        Role::whereIn('id', $rolesToDelete)
                            ->where('user_id', $user->id)
                            ->delete();
                    }

                    // Commit transaction if everything succeeded
                    DB::commit();
                    
                    // Final verification: Check all roles have correct permissions
                    foreach ($submittedRoleIds as $roleId) {
                        $role = Role::find($roleId);
                        if ($role) {
                            $role->unsetRelation('permissions');
                            $role->load('permissions');
                            $finalPermissions = DB::table('role_permissions')
                                ->where('role_id', $roleId)
                                ->pluck('permission_id')
                                ->toArray();
                            
                            \Log::info('Final verification for role', [
                                'role_id' => $roleId,
                                'role_name' => $role->name,
                                'database_permissions' => $finalPermissions,
                                'relationship_permissions' => $role->permissions->pluck('id')->toArray()
                            ]);
                        }
                    }
                    
                    \Log::info('Roles and permissions updated successfully', [
                        'user_id' => $user->id,
                        'roles_updated' => count($submittedRoleIds),
                        'role_ids' => $submittedRoleIds
                    ]);
                } catch (\Exception $e) {
                    // Rollback transaction on error
                    DB::rollBack();
                    \Log::error('Failed to update roles and permissions', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return redirect()->route('business-configuration.edit')
                        ->with('error', 'Failed to save roles and permissions: ' . $e->getMessage())
                        ->withInput();
                }
            }
        }

        // Build success message with details
        $successMessage = 'Business configuration updated successfully!';
        if ($request->has('roles')) {
            $roleCount = count($request->input('roles', []));
            $successMessage .= " {$roleCount} role(s) updated with permissions.";
        }
        
        return redirect()->route('business-configuration.edit')
            ->with('success', $successMessage);
    }

    /**
     * Check if user can access configuration
     */
    private function canAccessConfiguration($user)
    {
        // Admins can always access
        if ($user->isAdmin()) {
            return true;
        }

        $plan = $user->currentPlan();

        // Free plan - can access immediately
        if ($plan && $plan->slug === 'free') {
            return true;
        }

        // Basic/Pro plans - need verified payment
        if ($plan && in_array($plan->slug, ['basic', 'pro'])) {
            $subscription = $user->activeSubscription;
            return $subscription && $subscription->status === 'active';
        }

        return false;
    }

    /**
     * Create Owner role for Basic Plan
     */
    private function createOwnerRole($user, $permissions)
    {
        $ownerRole = Role::firstOrCreate(
            [
                'user_id' => $user->id,
                'slug' => 'owner',
            ],
            [
                'name' => 'Owner',
                'description' => 'Full access to all features (Sole Proprietor)',
                'is_system_role' => true,
                'is_active' => true,
            ]
        );

        // Give all permissions
        $ownerRole->permissions()->sync($permissions->pluck('id'));

        // Assign role to user
        if (!$user->userRoles()->where('role_id', $ownerRole->id)->exists()) {
            $user->userRoles()->attach($ownerRole->id);
        }
    }

    /**
     * Generate menu items based on selected business types
     */
    private function generateMenuItems($user)
    {
        $businessTypes = $user->businessTypes;

        // Ensure common menus are linked to all business types
        $commonMenuSlugs = ['dashboard', 'sales', 'products', 'customers', 'staff', 'reports', 'settings'];
        $commonMenus = MenuItem::whereIn('slug', $commonMenuSlugs)->get();

        foreach ($businessTypes as $businessType) {
            // Link common menus to this business type if not already linked
            foreach ($commonMenus as $menu) {
                \App\Models\BusinessTypeMenuItem::firstOrCreate(
                    [
                        'business_type_id' => $businessType->id,
                        'menu_item_id' => $menu->id,
                    ],
                    [
                        'is_enabled' => true,
                        'sort_order' => $menu->sort_order ?? 0,
                    ]
                );
            }

            // Link business-specific menus if they exist
            $businessSpecificMenus = $this->getBusinessSpecificMenus($businessType->slug);
            foreach ($businessSpecificMenus as $menuSlug => $menuData) {
                $menuItem = MenuItem::where('slug', $menuSlug)->first();
                if ($menuItem) {
                    \App\Models\BusinessTypeMenuItem::firstOrCreate(
                        [
                            'business_type_id' => $businessType->id,
                            'menu_item_id' => $menuItem->id,
                        ],
                        [
                            'is_enabled' => true,
                            'sort_order' => $menuData['sort_order'] ?? 0,
                        ]
                    );
                }
            }
            
            // Also ensure business-specific menus are created if they don't exist
            $this->createBusinessSpecificMenuItems($businessType->slug);
        }
    }

    /**
     * Create business-specific menu items if they don't exist
     */
    private function createBusinessSpecificMenuItems($businessTypeSlug)
    {
        $menuDefinitions = [
            'bar' => [
                ['name' => 'Sales & Orders', 'slug' => 'bar-sales-orders', 'icon' => 'fa-shopping-cart', 'route' => null, 'sort_order' => 2.5, 'children' => [
                    ['name' => 'Waiter Orders', 'slug' => 'bar-counter-waiter-orders', 'icon' => 'fa-bell', 'route' => 'bar.counter.waiter-orders', 'sort_order' => 1],
                    ['name' => 'All Orders', 'slug' => 'bar-orders-all', 'icon' => 'fa-list', 'route' => 'bar.orders.index', 'sort_order' => 2],
                    ['name' => 'Payments', 'slug' => 'bar-payments', 'icon' => 'fa-money', 'route' => 'bar.payments.index', 'sort_order' => 3],
                    ['name' => 'Tables', 'slug' => 'bar-tables', 'icon' => 'fa-table', 'route' => 'bar.tables.index', 'sort_order' => 4],
                ]],
                ['name' => 'Stock Management', 'slug' => 'bar-stock-mgmt', 'icon' => 'fa-archive', 'route' => null, 'sort_order' => 3.5, 'children' => [
                    ['name' => 'Register Products', 'slug' => 'bar-products-create', 'icon' => 'fa-plus-circle', 'route' => 'bar.products.create', 'sort_order' => 1],
                    ['name' => 'Products List', 'slug' => 'bar-products', 'icon' => 'fa-list', 'route' => 'bar.products.index', 'sort_order' => 2],
                    ['name' => 'Receiving Stock', 'slug' => 'bar-stock-receipts', 'icon' => 'fa-download', 'route' => 'bar.stock-receipts.index', 'sort_order' => 3],
                    ['name' => 'Stock Transfers', 'slug' => 'bar-stock-transfers', 'icon' => 'fa-exchange', 'route' => 'bar.stock-transfers.index', 'sort_order' => 4],
                    ['name' => 'Stock Levels', 'slug' => 'bar-stock-levels', 'icon' => 'fa-bar-chart', 'route' => 'bar.beverage-inventory.stock-levels', 'sort_order' => 5],
                    ['name' => 'Warehouse Stock', 'slug' => 'bar-warehouse-stock', 'icon' => 'fa-archive', 'route' => 'bar.beverage-inventory.warehouse-stock', 'sort_order' => 6],
                ]],
                ['name' => 'Operations & Settings', 'slug' => 'bar-ops-settings', 'icon' => 'fa-gears', 'route' => null, 'sort_order' => 4.5, 'children' => [
                    ['name' => 'Suppliers', 'slug' => 'bar-suppliers', 'icon' => 'fa-truck', 'route' => 'bar.suppliers.index', 'sort_order' => 1],
                    ['name' => 'Reconciliation', 'slug' => 'bar-reconciliation', 'icon' => 'fa-balance-scale', 'route' => 'bar.counter.reconciliation', 'sort_order' => 2],
                    ['name' => 'Counter Settings', 'slug' => 'bar-counter-settings', 'icon' => 'fa-cog', 'route' => 'bar.counter-settings.index', 'sort_order' => 3],
                ]],
            ],
            'restaurant' => [
                ['name' => 'Restaurant Management', 'slug' => 'restaurant-management', 'icon' => 'fa-cutlery', 'route' => null, 'sort_order' => 2.5, 'children' => [
                    ['name' => 'Food Orders', 'slug' => 'restaurant-orders-food', 'icon' => 'fa-cutlery', 'route' => 'bar.orders.food', 'sort_order' => 1],
                    ['name' => 'Staff Management', 'slug' => 'restaurant-staff', 'icon' => 'fa-users', 'route' => 'staff.index', 'sort_order' => 2],
                    ['name' => 'Restaurant Reports', 'slug' => 'restaurant-reports', 'icon' => 'fa-chart-bar', 'route' => 'bar.chef.reports', 'sort_order' => 3],
                    ['name' => 'Reconciliation', 'slug' => 'restaurant-reconciliation', 'icon' => 'fa-balance-scale', 'route' => 'bar.chef.reconciliation', 'sort_order' => 4],
                ]],
                ['name' => 'Table Management', 'slug' => 'table-management', 'icon' => 'fa-table', 'route' => null, 'sort_order' => 2.6, 'children' => [
                    ['name' => 'Table Layout', 'slug' => 'table-layout', 'icon' => 'fa-th', 'route' => 'bar.tables.index', 'sort_order' => 1],
                    ['name' => 'Table Status', 'slug' => 'table-status', 'icon' => 'fa-info-circle', 'route' => 'bar.tables.index', 'sort_order' => 2],
                    ['name' => 'Reservations', 'slug' => 'table-reservations', 'icon' => 'fa-calendar', 'route' => 'bar.tables.index', 'sort_order' => 3],
                ]],
                ['name' => 'Kitchen Display', 'slug' => 'kitchen-display', 'icon' => 'fa-tv', 'route' => null, 'sort_order' => 2.7, 'children' => [
                    ['name' => 'Active Orders', 'slug' => 'kitchen-active-orders', 'icon' => 'fa-fire', 'route' => 'bar.chef.dashboard', 'sort_order' => 1],
                    ['name' => 'Kitchen Settings', 'slug' => 'kitchen-settings', 'icon' => 'fa-cog', 'route' => 'bar.chef.dashboard', 'sort_order' => 2],
                ]],
                ['name' => 'Menu Management', 'slug' => 'menu-management', 'icon' => 'fa-book', 'route' => null, 'sort_order' => 3.5, 'children' => [
                    ['name' => 'Menu Items', 'slug' => 'menu-items', 'icon' => 'fa-list', 'route' => 'bar.chef.food-items', 'sort_order' => 1],
                    ['name' => 'Menu Categories', 'slug' => 'menu-categories', 'icon' => 'fa-tags', 'route' => 'bar.chef.food-items', 'sort_order' => 2],
                    ['name' => 'Menu Pricing', 'slug' => 'menu-pricing', 'icon' => 'fa-dollar-sign', 'route' => 'bar.chef.food-items', 'sort_order' => 3],
                ]],
                ['name' => 'Ingredient Management', 'slug' => 'ingredient-management', 'icon' => 'fa-flask', 'route' => null, 'sort_order' => 4, 'children' => [
                    ['name' => 'Ingredients', 'slug' => 'ingredients', 'icon' => 'fa-list', 'route' => 'bar.chef.ingredients', 'sort_order' => 1],
                    ['name' => 'Food Suppliers', 'slug' => 'food-suppliers', 'icon' => 'fa-truck', 'route' => null, 'url' => '/bar/suppliers?type=food', 'sort_order' => 2],
                    ['name' => 'Ingredient Receipts', 'slug' => 'ingredient-receipts', 'icon' => 'fa-shopping-cart', 'route' => 'bar.chef.ingredient-receipts', 'sort_order' => 3],
                    ['name' => 'Ingredient Batches', 'slug' => 'ingredient-batches', 'icon' => 'fa-boxes', 'route' => 'bar.chef.ingredient-batches', 'sort_order' => 4],
                    ['name' => 'Stock Movements', 'slug' => 'ingredient-stock-movements', 'icon' => 'fa-exchange-alt', 'route' => 'bar.chef.ingredient-stock-movements', 'sort_order' => 5],
                ]],
            ],
            'pharmacy' => [
                ['name' => 'Pharmacy Management', 'slug' => 'pharmacy-management', 'icon' => 'fa-medkit', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Prescriptions', 'slug' => 'prescriptions', 'icon' => 'fa-file-text', 'route' => null, 'sort_order' => 2.6],
                ['name' => 'Medicine Inventory', 'slug' => 'medicine-inventory', 'icon' => 'fa-pills', 'route' => null, 'sort_order' => 3.5],
                ['name' => 'Expiry Tracking', 'slug' => 'expiry-tracking', 'icon' => 'fa-calendar', 'route' => null, 'sort_order' => 3.6],
            ],
            'supermarket' => [
                ['name' => 'Supermarket Management', 'slug' => 'supermarket-management', 'icon' => 'fa-shopping-basket', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Grocery Inventory', 'slug' => 'grocery-inventory', 'icon' => 'fa-cart-plus', 'route' => null, 'sort_order' => 3.5],
                ['name' => 'Department Management', 'slug' => 'department-management', 'icon' => 'fa-sitemap', 'route' => null, 'sort_order' => 3.6],
            ],
            'cafe' => [
                ['name' => 'Cafe Management', 'slug' => 'cafe-management', 'icon' => 'fa-coffee', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Menu Items', 'slug' => 'cafe-menu-items', 'icon' => 'fa-list', 'route' => null, 'sort_order' => 2.6],
                ['name' => 'Beverage Inventory', 'slug' => 'cafe-beverage-inventory', 'icon' => 'fa-mug-hot', 'route' => null, 'sort_order' => 3.5],
            ],
            'juice' => [
                ['name' => 'Juice Point Management', 'slug' => 'juice-management', 'icon' => 'fa-tint', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Juice Inventory', 'slug' => 'juice-inventory', 'icon' => 'fa-flask', 'route' => null, 'sort_order' => 3.5],
            ],
            'electronics' => [
                ['name' => 'Electronics Management', 'slug' => 'electronics-management', 'icon' => 'fa-laptop', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Product Categories', 'slug' => 'electronics-categories', 'icon' => 'fa-tags', 'route' => null, 'sort_order' => 2.6],
                ['name' => 'Electronics Inventory', 'slug' => 'electronics-inventory', 'icon' => 'fa-microchip', 'route' => null, 'sort_order' => 3.5],
            ],
        ];

        $menus = $menuDefinitions[$businessTypeSlug] ?? [];
        
        foreach ($menus as $menuData) {
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);
            
            $menuItem = MenuItem::firstOrCreate(
                ['slug' => $menuData['slug']],
                array_merge($menuData, ['is_active' => true, 'parent_id' => null])
            );
            
            // Link to business type
            $businessType = BusinessType::where('slug', $businessTypeSlug)->first();
            if ($businessType) {
                \App\Models\BusinessTypeMenuItem::firstOrCreate(
                    [
                        'business_type_id' => $businessType->id,
                        'menu_item_id' => $menuItem->id,
                    ],
                    [
                        'is_enabled' => true,
                        'sort_order' => $menuData['sort_order'] ?? 0,
                    ]
                );
            }
            
            // Create child menu items
            foreach ($children as $childData) {
                $childMenuItem = MenuItem::firstOrCreate(
                    ['slug' => $childData['slug']],
                    array_merge($childData, [
                        'is_active' => true,
                        'parent_id' => $menuItem->id,
                    ])
                );
                
                // Link child to business type
                if ($businessType) {
                    \App\Models\BusinessTypeMenuItem::firstOrCreate(
                        [
                            'business_type_id' => $businessType->id,
                            'menu_item_id' => $childMenuItem->id,
                        ],
                        [
                            'is_enabled' => true,
                            'sort_order' => $childData['sort_order'] ?? 0,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Get business-specific menu items
     */
    private function getBusinessSpecificMenus($businessTypeSlug)
    {
        $menus = [
            'bar' => [
                'bar-sales-orders' => ['sort_order' => 2.5],
                'bar-stock-mgmt' => ['sort_order' => 3.5],
                'bar-ops-settings' => ['sort_order' => 4.5],
            ],
            'restaurant' => [
                'restaurant-management' => ['sort_order' => 2.5],
                'table-management' => ['sort_order' => 2.6],
                'kitchen-display' => ['sort_order' => 2.7],
                'menu-management' => ['sort_order' => 3.5],
            ],
            'pharmacy' => [
                'pharmacy-management' => ['sort_order' => 2.5],
                'prescriptions' => ['sort_order' => 2.6],
                'medicine-inventory' => ['sort_order' => 3.5],
                'expiry-tracking' => ['sort_order' => 3.6],
            ],
            'supermarket' => [
                'supermarket-management' => ['sort_order' => 2.5],
                'grocery-inventory' => ['sort_order' => 3.5],
                'department-management' => ['sort_order' => 3.6],
            ],
            'cafe' => [
                'cafe-management' => ['sort_order' => 2.5],
                'cafe-menu-items' => ['sort_order' => 2.6],
                'cafe-beverage-inventory' => ['sort_order' => 3.5],
            ],
            'juice' => [
                'juice-management' => ['sort_order' => 2.5],
                'juice-inventory' => ['sort_order' => 3.5],
            ],
            'electronics' => [
                'electronics-management' => ['sort_order' => 2.5],
                'electronics-categories' => ['sort_order' => 2.6],
                'electronics-inventory' => ['sort_order' => 3.5],
            ],
        ];

        return $menus[$businessTypeSlug] ?? [];
    }

    /**
     * Auto-create default roles based on selected business types
     */
    private function autoCreateDefaultRoles($user, $businessTypeIds)
    {
        $plan = $user->currentPlan();
        $isBasicPlan = $plan && $plan->slug === 'basic';
        
        // Don't auto-create roles for Basic plan
        if ($isBasicPlan) {
            return;
        }

        // Get business type slugs
        $businessTypes = BusinessType::whereIn('id', $businessTypeIds)->get();
        $businessTypeSlugs = $businessTypes->pluck('slug')->toArray();

        // Get all suggested roles for these business types
        $suggestedRoles = \App\Services\RoleSuggestionService::getSuggestedRolesForBusinessTypes($businessTypeSlugs);

        // Get existing roles for this user (by name, case-insensitive)
        $existingRoles = $user->ownedRoles()
            ->where('is_active', true)
            ->get()
            ->keyBy(function($role) {
                return strtolower($role->name);
            });

        // Create default roles that don't exist yet
        foreach ($suggestedRoles as $roleSuggestion) {
            $roleNameLower = strtolower($roleSuggestion['name']);
            
            // Check if role already exists
            if ($existingRoles->has($roleNameLower)) {
                continue; // Skip if role already exists
            }

            // Create the role
            $role = Role::create([
                'user_id' => $user->id,
                'name' => $roleSuggestion['name'],
                'slug' => Str::slug($roleSuggestion['name'] . '-' . $user->id . '-' . time()),
                'description' => $roleSuggestion['description'] ?? null,
                'is_system_role' => false,
                'is_active' => true,
            ]);

            // Get permission IDs for this role
            $permissionIds = \App\Services\RoleSuggestionService::getPermissionIdsForRole($roleSuggestion);
            
            // Assign permissions to the role
            if (!empty($permissionIds)) {
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
