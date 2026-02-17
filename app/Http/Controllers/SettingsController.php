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
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Get system settings if admin
        $systemSettings = null;
        if ($user->isAdmin()) {
            $systemSettings = SystemSetting::getGrouped();
        }

        return view('settings.index', compact('user', 'systemSettings'));
    }

    /**
     * Update profile information
     */
    public function updateProfile(Request $request)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login');
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        $user->update([
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
