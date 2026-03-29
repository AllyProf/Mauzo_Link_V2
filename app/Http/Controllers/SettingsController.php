<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    use HandlesStaffPermissions;
    /**
     * Show settings page
     */
    public function index()
    {
        $businessUser = $this->getCurrentUser();
        $person = $this->getLoggedInPerson();
        
        if (!$businessUser || !$person) {
            return redirect()->route('login');
        }
        
        // Get system settings if admin
        $systemSettings = null;
        if ($businessUser->isAdmin()) {
            $systemSettings = SystemSetting::getGrouped();
        }

        // Standardize fields for view
        $user = (object)[
            'id' => $person->id,
            'name' => $person->name ?? $person->full_name,
            'email' => $person->email,
            'phone' => $person->phone ?? $person->phone_number,
            'avatar' => $person->avatar,
            'isAdmin' => method_exists($businessUser, 'isAdmin') ? $businessUser->isAdmin() : false,
        ];

        return view('settings.index', compact('user', 'systemSettings'));
    }

    protected function getLoggedInPerson()
    {
        if (session('is_staff')) {
            return \App\Models\Staff::find(session('staff_id'));
        }
        return \Illuminate\Support\Facades\Auth::user();
    }

    /**
     * Update profile information
     */
    public function updateProfile(Request $request)
    {
        $person = $this->getLoggedInPerson();
        
        if (!$person) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [];

        // Handle Phone prefixing
        if ($request->has('phone') && !empty($request->phone)) {
            $phoneSuffix = preg_replace('/^0|^\+255|^255/', '', $request->phone);
            $fullPhone = '255' . $phoneSuffix;
            
            // Map to correct attribute name
            if (isset($person->phone_number)) {
                $data['phone_number'] = $fullPhone;
            } else {
                $data['phone'] = $fullPhone;
            }
        }

        // Handle Avatar Upload
        if ($request->hasFile('avatar')) {
            $imageName = time() . '.' . $request->avatar->extension();
            $request->avatar->move(public_path('uploads/avatars'), $imageName);
            $data['avatar'] = asset('uploads/avatars/' . $imageName);
        }

        $person->update($data);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|confirmed|min:8',
        ]);

        $person = $this->getLoggedInPerson();
        
        if (!$person) {
            return redirect()->route('login');
        }

        $person->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    /**
     * Update system settings (Admin only)
     */
    public function updateSystemSettings(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $settings = $request->except(['_token', '_method']);

        foreach ($settings as $key => $value) {
            // Get existing setting to preserve type and group
            $existing = SystemSetting::where('key', $key)->first();
            
            if ($existing) {
                $type = $existing->type;
                $group = $existing->group;
            } else {
                // Determine type based on key
                if (in_array($key, ['registration_enabled', 'maintenance_mode'])) {
                    $type = 'boolean';
                } else {
                    $type = 'text';
                }
                $group = 'general';
            }

            // Handle boolean checkboxes
            if ($type === 'boolean') {
                $value = $request->has($key) ? true : false;
            }

            SystemSetting::set($key, $value, $type, $group);
        }

        return redirect()->back()->with('success', 'System settings updated successfully.');
    }
}
