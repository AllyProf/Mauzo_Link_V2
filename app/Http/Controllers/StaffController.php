<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Role;
use App\Models\BusinessType;
use App\Services\SmsService;
use App\Services\RoleSuggestionService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffController extends Controller
{
    use HandlesStaffPermissions;

    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display staff registration form
     */
    public function create()
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission (Manager role has all permissions)
        if (!$this->hasPermission('staff', 'create')) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to register staff members.');
        }
        
        // For staff members, skip plan check - they should have access regardless
        // Only check plan for regular users (owners)
        if (!session('is_staff')) {
            // Check if user's plan allows staff registration (Free or Pro only)
            $plan = $user->currentPlan();
            if (!$plan || !in_array($plan->slug, ['free', 'pro'])) {
                return redirect()->route('dashboard')
                    ->with('error', 'Staff registration is only available for Free and Pro plans.');
            }
        }

        // Get user's enabled business types
        $businessTypes = $user->enabledBusinessTypes()->get();

        // Get user's roles for dropdown (only Manager, Counter and Waiter roles)
        $roles = $user->ownedRoles()->where('is_active', true)
            ->where(function($q) {
                $q->where(function($sq) {
                    $sq->where('name', 'LIKE', '%manager%')
                      ->orWhere('name', 'LIKE', '%counter%')
                      ->orWhere('name', 'LIKE', '%waiter%')
                      ->orWhere('name', 'LIKE', '%super admin%');
                })->where('name', 'NOT LIKE', '%HR%')->where('name', 'NOT LIKE', '%Human Resources%');
            })->get();

        if ($roles->count() == 0) {
            return redirect()->route('business-configuration.edit')
                ->with('warning', 'Please create at least one role in Business Configuration before registering staff members.');
        }

        return view('staff.create', compact('roles', 'businessTypes'));
    }

    /**
     * Store new staff member
     */
    public function store(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission (Manager role has all permissions)
        if (!$this->hasPermission('staff', 'create')) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to register staff members.');
        }
        
        // For staff members, skip plan check - they should have access regardless
        // Only check plan for regular users (owners)
        if (!session('is_staff')) {
            // Check if user's plan allows staff registration
            $plan = $user->currentPlan();
            if (!$plan || !in_array($plan->slug, ['free', 'pro'])) {
                return back()->with('error', 'Staff registration is only available for Free and Pro plans.');
            }
        }

        // Validate request
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('staff', 'email'),
                \Illuminate\Validation\Rule::unique('users', 'email'),
            ],
            'gender' => 'required|in:male,female,other',
            'nida' => 'nullable|string|max:50',
            'phone_number' => 'required|string|max:20',
            'next_of_kin' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'location_branch' => 'nullable|string|max:255',
            'business_type_id' => 'nullable|exists:business_types,id',
            'role_id' => 'required|exists:roles,id',
            'salary_paid' => 'nullable|numeric|min:0',
            'religion' => 'nullable|string|max:100',
            'nida_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'voter_id_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'professional_certificate_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Verify role belongs to the owner
        $role = Role::findOrFail($validated['role_id']);
        $ownerId = $this->getOwnerId();
        if ($role->user_id !== $ownerId) {
            return back()->with('error', 'Invalid role selected.');
        }

        // Generate staff ID
        $staffId = Staff::generateStaffId($ownerId);

        // Generate password from last name
        $password = Staff::generatePasswordFromLastName($validated['full_name']);
        $hashedPassword = Hash::make($password);

        // Handle file uploads
        $nidaAttachment = null;
        $voterIdAttachment = null;
        $professionalCertificateAttachment = null;

        if ($request->hasFile('nida_attachment')) {
            $nidaAttachment = $request->file('nida_attachment')->store('staff/documents/nida', 'public');
        }

        if ($request->hasFile('voter_id_attachment')) {
            $voterIdAttachment = $request->file('voter_id_attachment')->store('staff/documents/voter-id', 'public');
        }

        if ($request->hasFile('professional_certificate_attachment')) {
            $professionalCertificateAttachment = $request->file('professional_certificate_attachment')->store('staff/documents/certificates', 'public');
        }

        // Create staff record
        $staff = Staff::create([
            'user_id' => $ownerId,
            'staff_id' => $staffId,
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'gender' => $validated['gender'],
            'nida' => $validated['nida'] ?? null,
            'phone_number' => $validated['phone_number'],
            'password' => $hashedPassword,
            'next_of_kin' => $validated['next_of_kin'] ?? null,
            'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
            'location_branch' => $validated['location_branch'] ?? null,
            'business_type_id' => $validated['business_type_id'] ?? null,
            'role_id' => $validated['role_id'],
            'salary_paid' => $validated['salary_paid'] ?? 0,
            'religion' => $validated['religion'] ?? null,
            'nida_attachment' => $nidaAttachment,
            'voter_id_attachment' => $voterIdAttachment,
            'professional_certificate_attachment' => $professionalCertificateAttachment,
            'is_active' => true,
        ]);

        // Send SMS with credentials
        $this->sendStaffCredentialsSms($staff, $password);

        return redirect()->route('staff.index')
            ->with('success', 'Staff member registered successfully! SMS with credentials has been sent to ' . $staff->phone_number);
    }

    /**
     * Display list of staff members
     */
    public function index()
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission (Manager / Super Admin role has all permissions)
        if (!$this->hasPermission('staff', 'view')) {
            \Illuminate\Support\Facades\Log::warning('User denied access to staff list', [
                'is_staff' => session('is_staff'),
                'staff_id' => session('staff_id'),
                'has_permission' => $this->hasPermission('staff', 'view')
            ]);
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to view staff members.');
        }
        
        // Get owner ID (for staff, get their owner's ID)
        $ownerId = $this->getOwnerId();
        
        // For staff members, skip plan check - they should have access regardless
        // Only check plan for regular users (owners)
        if (!session('is_staff')) {
            // Check if user's plan allows staff registration
            $plan = $user->currentPlan();
            if (!$plan || !in_array($plan->slug, ['free', 'pro'])) {
                return redirect()->route('dashboard')
                    ->with('error', 'Staff management is only available for Free and Pro plans.');
            }
        }

        $staffQuery = Staff::where('user_id', $ownerId)
            ->with(['role', 'businessType']);
            
        $staff = $staffQuery->orderBy('created_at', 'desc')->get();

        $staff = $staffQuery->orderBy('created_at', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total' => $staff->count(),
            'active' => $staff->where('is_active', true)->count(),
            'total_salary' => $staff->sum('salary_paid')
        ];

        return view('staff.index', compact('staff', 'stats'));
    }

    /**
     * Get roles for a specific business type (AJAX endpoint)
     */
    public function getRolesByBusinessType(Request $request)
    {
        // Only allow authenticated users (not staff) to access this
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['roles' => [], 'error' => 'Unauthorized'], 401);
        }
        
        $businessTypeId = $request->input('business_type_id');
        
        if (!$businessTypeId) {
            return response()->json(['roles' => [], 'error' => 'Business type ID is required']);
        }
        
        // Get the business type
        $businessType = BusinessType::find($businessTypeId);
        if (!$businessType) {
            return response()->json(['roles' => [], 'error' => 'Business type not found']);
        }
        
        // Ensure default roles exist for this business type
        $this->ensureDefaultRolesExist($user, [$businessType->id]);
        
        // Get suggested role names for this business type
        $suggestedRoles = RoleSuggestionService::getSuggestedRolesForBusinessType($businessType->slug);
        $suggestedRoleNames = array_map(function($role) {
            return strtolower(trim($role['name']));
        }, $suggestedRoles);
        
        // Get all user's active roles
        $allRoles = Role::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
        
        // Filter roles that match suggested role names for this business type
        // Use case-insensitive matching and trim whitespace
        $filteredRoles = $allRoles->filter(function($role) use ($suggestedRoleNames) {
            $roleNameLower = strtolower(trim($role->name));
            return in_array($roleNameLower, $suggestedRoleNames);
        });
        
        // If no matching roles found, return all roles as fallback (but still filtered by manager/counter/waiter/super admin)
        if ($filteredRoles->isEmpty()) {
            $filteredRoles = $allRoles;
        }
        
        // Final strict filter to ensure only Manager, Counter, Waiter, and Super Admin roles are shown, and NO HR
        $filteredRoles = $filteredRoles->filter(function($role) {
            $name = strtolower($role->name);
            $isAllowedRole = str_contains($name, 'manager') || 
                             str_contains($name, 'counter') || 
                             str_contains($name, 'waiter') || 
                             str_contains($name, 'super admin') ||
                             str_contains($name, 'super-admin');
            
            $isHr = str_contains($name, 'hr') || str_contains($name, 'human resource');
            
            return $isAllowedRole && !$isHr;
        });
        
        // Return filtered roles
        return response()->json([
            'roles' => $filteredRoles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                ];
            })->values()
        ]);
    }

    /**
     * Check if the current user or staff has a specific permission.
     * This method is assumed to be part of the controller or a trait used by it.
     * It's being added/updated based on the user's instruction.
     */
    protected function hasPermission(string $module, string $action): bool
    {
        // If the current user is a staff member
        if (session('is_staff')) {
            $staff = Staff::find(session('staff_id'));
            if ($staff && $staff->role) {
                // Manager / Super Admin roles always have all permissions
                $roleName = strtolower($staff->role->name);
                if ($roleName === 'manager' || $roleName === 'super admin') {
                    return true;
                }
                return $staff->role->hasPermission($module, $action);
            }
            return false; // Staff not found or no role assigned
        }

        // If it's a regular user (owner)
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        // Owners (users) are assumed to have all permissions for their own account
        // unless a more granular permission system is implemented for owners themselves.
        // For the context of staff management, the owner implicitly has all rights.
        return true;
    }

    /**
     * Ensure default roles exist for business types
     */
    private function ensureDefaultRolesExist($user, $businessTypeIds)
    {
        $ownerId = $this->getOwnerId();
        
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
        $suggestedRoles = RoleSuggestionService::getSuggestedRolesForBusinessTypes($businessTypeSlugs);

        // Get existing roles for this user (by name, case-insensitive)
        $existingRoles = $user->ownedRoles()
            ->where('is_active', true)
            ->get()
            ->keyBy(function($role) {
                return strtolower($role->name);
            });

        // Create default roles that don't exist yet
        foreach ($suggestedRoles as $roleSuggestion) {
            $roleName = is_array($roleSuggestion) ? ($roleSuggestion['name'] ?? '') : (string)$roleSuggestion;
            $roleNameLower = strtolower($roleName);
            
            // Check if role already exists
            if ($existingRoles->has($roleNameLower)) {
                continue; // Skip if role already exists
            }

            // Create the role
            $role = Role::create([
                'user_id' => $ownerId,
                'name' => $roleSuggestion['name'],
                'slug' => \Illuminate\Support\Str::slug($roleSuggestion['name'] . '-' . $ownerId . '-' . time()),
                'description' => $roleSuggestion['description'] ?? null,
                'is_system_role' => false,
                'is_active' => true,
            ]);

            // Get permission IDs for this role
            $permissionIds = RoleSuggestionService::getPermissionIdsForRole($roleSuggestion);
            
            // Assign permissions to the role
            if (!empty($permissionIds)) {
                $role->permissions()->sync($permissionIds);
            }
        }
    }

    /**
     * Display staff member details
     */
    public function show($id)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission
        if (!$this->hasPermission('staff', 'view')) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to view staff details.');
        }
        
        $ownerId = $this->getOwnerId();
        $staff = Staff::where('user_id', $ownerId)
            ->with(['role', 'businessType'])
            ->findOrFail($id);

        return view('staff.show', compact('staff'));
    }

    /**
     * Show the form for editing a staff member
     */
    public function edit($id)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission
        if (!$this->hasPermission('staff', 'edit')) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to edit staff members.');
        }
        
        $ownerId = $this->getOwnerId();
        $staff = Staff::where('user_id', $ownerId)->findOrFail($id);
        
        // Get user's enabled business types
        $businessTypes = $user->enabledBusinessTypes()->get();
        
        // Get user's roles for dropdown (filtered)
        $roles = $user->ownedRoles()->where('is_active', true)
            ->where(function($q) {
                $q->where(function($sq) {
                    $sq->where('name', 'LIKE', '%manager%')
                      ->orWhere('name', 'LIKE', '%counter%')
                      ->orWhere('name', 'LIKE', '%waiter%')
                      ->orWhere('name', 'LIKE', '%super admin%');
                })->where('name', 'NOT LIKE', '%HR%')->where('name', 'NOT LIKE', '%Human Resources%');
            })->get();

        return view('staff.edit', compact('staff', 'roles', 'businessTypes'));
    }

    /**
     * Update a staff member
     */
    public function update(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission
        if (!$this->hasPermission('staff', 'edit')) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to edit staff members.');
        }
        
        $ownerId = $this->getOwnerId();
        $staff = Staff::where('user_id', $ownerId)->findOrFail($id);

        // Validate request
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('staff', 'email')->ignore($staff->id),
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($staff->id),
            ],
            'gender' => 'required|in:male,female,other',
            'nida' => 'nullable|string|max:50',
            'phone_number' => 'required|string|max:20',
            'next_of_kin' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'location_branch' => 'nullable|string|max:255',
            'business_type_id' => 'nullable|exists:business_types,id',
            'role_id' => 'required|exists:roles,id',
            'salary_paid' => 'nullable|numeric|min:0',
            'religion' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'nida_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'voter_id_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'professional_certificate_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Verify role belongs to the owner
        $role = Role::findOrFail($validated['role_id']);
        $ownerId = $this->getOwnerId();
        if ($role->user_id !== $ownerId) {
            return back()->with('error', 'Invalid role selected.');
        }

        // Handle file uploads (only if new files are provided)
        if ($request->hasFile('nida_attachment')) {
            // Delete old file if exists
            if ($staff->nida_attachment) {
                Storage::disk('public')->delete($staff->nida_attachment);
            }
            $validated['nida_attachment'] = $request->file('nida_attachment')->store('staff/documents/nida', 'public');
        }

        if ($request->hasFile('voter_id_attachment')) {
            if ($staff->voter_id_attachment) {
                Storage::disk('public')->delete($staff->voter_id_attachment);
            }
            $validated['voter_id_attachment'] = $request->file('voter_id_attachment')->store('staff/documents/voter-id', 'public');
        }

        if ($request->hasFile('professional_certificate_attachment')) {
            if ($staff->professional_certificate_attachment) {
                Storage::disk('public')->delete($staff->professional_certificate_attachment);
            }
            $validated['professional_certificate_attachment'] = $request->file('professional_certificate_attachment')->store('staff/documents/certificates', 'public');
        }

        // Remove file fields from validated if not updated
        if (!$request->hasFile('nida_attachment')) {
            unset($validated['nida_attachment']);
        }
        if (!$request->hasFile('voter_id_attachment')) {
            unset($validated['voter_id_attachment']);
        }
        if (!$request->hasFile('professional_certificate_attachment')) {
            unset($validated['professional_certificate_attachment']);
        }

        // Update staff record
        $validated['salary_paid'] = $validated['salary_paid'] ?? 0;
        $staff->update($validated);

        return redirect()->route('staff.index')
            ->with('success', 'Staff member updated successfully!');
    }

    /**
     * Delete a staff member
     */
    public function destroy($id)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission
        if (!$this->hasPermission('staff', 'delete')) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to delete staff members.');
        }
        
        $ownerId = $this->getOwnerId();
        $staff = Staff::where('user_id', $ownerId)->findOrFail($id);

        // Delete associated files
        if ($staff->nida_attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($staff->nida_attachment);
        }
        if ($staff->voter_id_attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($staff->voter_id_attachment);
        }
        if ($staff->professional_certificate_attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($staff->professional_certificate_attachment);
        }

        $staff->delete();

        return redirect()->route('staff.index')
            ->with('success', 'Staff member deleted successfully!');
    }

    /**
     * Reset staff password
     */
    public function resetPassword(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Check permission
        if (!$this->hasPermission('staff', 'reset_password')) {
            return redirect()->route('staff.index')
                ->with('error', 'You do not have permission to reset staff passwords.');
        }
        
        $ownerId = $this->getOwnerId();
        $staff = Staff::where('user_id', $ownerId)->findOrFail($id);
        
        // Generate new random password
        $newPassword = \Illuminate\Support\Str::random(8);
        $staff->password = \Illuminate\Support\Facades\Hash::make($newPassword);
        $staff->save();
        
        return redirect()->route('staff.index')
            ->with('success', "Password for {$staff->full_name} has been reset to: <strong>{$newPassword}</strong>. Please share this with the staff member.");
    }

    /**
     * Send SMS with staff credentials
     */
    private function sendStaffCredentialsSms($staff, $password)
    {
        $owner = $staff->owner;
        $businessName = $owner->business_name ?? 'N/A';
        $roleName = $staff->role ? $staff->role->name : 'N/A';
        
        $message = "HABARI! Karibu MauzoLink!\n\n";
        $message .= "Umeandikishwa kama mfanyakazi wa " . $businessName . ".\n\n";
        $message .= "TAARIFA ZA AKAUNTI YAKO:\n";
        $message .= "Jina: " . $staff->full_name . "\n";
        $message .= "Staff ID: " . $staff->staff_id . "\n";
        $message .= "Jukumu: " . $roleName . "\n";
        $message .= "Email: " . $staff->email . "\n";
        $message .= "Password: " . $password . "\n\n";
        $message .= "Tafadhali login kwa kutumia credentials hapo juu.\n\n";
        $message .= "Asante!";
        
        $this->smsService->sendSms($staff->phone_number, $message);
    }
}
