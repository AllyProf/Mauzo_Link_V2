<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Staff;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            \Log::info('Login attempt', ['email' => $request->email]);

            // IMPORTANT: Clear any existing staff session before attempting user login
            if ($request->session()->has('is_staff')) {
                $request->session()->forget([
                    'staff_id',
                    'staff_name',
                    'staff_email',
                    'staff_role_id',
                    'staff_user_id',
                    'is_staff'
                ]);
            }

            // First, try to authenticate as a regular user
            if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
                \Log::info('User authentication successful', ['email' => $request->email]);
                
                // Regenerate session AFTER successful authentication
                // This prevents session fixation attacks
                $request->session()->regenerate();
                
                // Ensure session is saved
                $request->session()->save();

            // IMPORTANT: Double-check no staff session exists
            $request->session()->forget([
                'staff_id',
                'staff_name',
                'staff_email',
                'staff_role_id',
                'staff_user_id',
                'is_staff'
            ]);

            // Redirect admins to admin dashboard, customers to regular dashboard
            $user = Auth::user();
            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard.index'));
            }

            // Check if user needs to complete business configuration
            if (!$user->isConfigured()) {
                $plan = $user->currentPlan();
                $canConfigure = false;

                // If user has a plan
                if ($plan) {
                    // Free plan - can configure immediately
                    if ($plan->slug === 'free') {
                        $canConfigure = true;
                    } 
                    // Basic/Pro plans - need verified payment first
                    elseif (in_array($plan->slug, ['basic', 'pro'])) {
                        $subscription = $user->activeSubscription;
                        $canConfigure = $subscription && $subscription->status === 'active';
                    }
                } else {
                    // No plan yet - check if they have a subscription (even if pending)
                    $subscription = $user->activeSubscription;
                    if ($subscription) {
                        // If they have a free plan subscription (trial), allow configuration
                        if ($subscription->plan && $subscription->plan->slug === 'free') {
                            $canConfigure = true;
                        }
                    }
                }

                if ($canConfigure) {
                    return redirect()->route('business-configuration.index')
                        ->with('info', 'Please complete your business configuration to get started.');
                }
            }

            return redirect()->intended('/dashboard');
            }

            // If user authentication failed, check if it's a staff member
            \Log::info('User authentication failed, checking staff', ['email' => $request->email]);
            
            // IMPORTANT: Also check that this email is NOT in users table to prevent conflicts
            $userExists = \App\Models\User::where('email', $request->email)->exists();
            
            if ($userExists) {
                \Log::warning('Email exists in users table but password incorrect', ['email' => $request->email]);
                // Email exists in users table but password didn't match - don't check staff
                return back()->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ])->withInput($request->only('email'));
            }

            // Check for staff member
            $staff = Staff::where('email', $request->email)->first();

            if (!$staff) {
                \Log::warning('Staff not found', ['email' => $request->email]);
                return back()->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ])->withInput($request->only('email'));
            }

            \Log::info('Staff found', ['staff_id' => $staff->id, 'email' => $request->email, 'is_active' => $staff->is_active]);

            // Check if staff is active
            if (!$staff->is_active) {
                \Log::warning('Staff account inactive', ['staff_id' => $staff->id, 'email' => $request->email]);
                return back()->withErrors([
                    'email' => 'Your staff account has been deactivated. Please contact your administrator.',
                ])->withInput($request->only('email'));
            }

            // Verify password
            $passwordCheck = Hash::check($request->password, $staff->password);
            \Log::info('Password check result', ['email' => $request->email, 'password_match' => $passwordCheck]);
            
            if (!$passwordCheck) {
                \Log::warning('Staff password incorrect', ['staff_id' => $staff->id, 'email' => $request->email]);
                return back()->withErrors([
                    'password' => 'The provided password is incorrect.',
                ])->withInput($request->only('email'));
            }

            // Password is correct - proceed with staff login
            \Log::info('Staff password correct, proceeding with login', ['staff_id' => $staff->id]);
            
            // IMPORTANT: Logout any existing user session first
            if (Auth::check()) {
                Auth::logout();
            }
            
            // IMPORTANT: Clear any existing staff session data
            $request->session()->forget([
                'staff_id',
                'staff_name',
                'staff_email',
                'staff_role_id',
                'staff_user_id',
                'is_staff'
            ]);

            // Regenerate session ID for security
            $request->session()->regenerate();

            // Store staff info in session AFTER regeneration
            $request->session()->put('staff_id', $staff->id);
            $request->session()->put('staff_name', $staff->full_name);
            $request->session()->put('staff_email', $staff->email);
            $request->session()->put('staff_role_id', $staff->role_id);
            $request->session()->put('staff_user_id', $staff->user_id);
            $request->session()->put('is_staff', true);

            // Ensure session is saved
            $request->session()->save();

            \Log::info('Staff session created successfully', ['staff_id' => $staff->id]);

            // Update last login
            $staff->update(['last_login_at' => now()]);

            // Get staff role slug for URL
            $roleSlug = $staff->role ? \Illuminate\Support\Str::slug($staff->role->name) : 'staff';
            
            // Redirect staff to role-specific dashboard URL
            return redirect()->route('dashboard.role', ['role' => $roleSlug])
                ->with('success', 'Welcome back, ' . $staff->full_name . '!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation exception during login', [
                'email' => $request->email ?? 'unknown',
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Unexpected error during login: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'email' => 'An unexpected error occurred during login. Please try again or contact support.',
            ])->withInput($request->only('email'));
        }
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        // Clear staff session if exists
        if ($request->session()->has('is_staff')) {
            $request->session()->forget([
                'staff_id',
                'staff_name',
                'staff_email',
                'staff_role_id',
                'staff_user_id',
                'is_staff'
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}


