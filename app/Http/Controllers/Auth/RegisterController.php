<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Otp;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Carbon\Carbon;

class RegisterController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Show the registration form with plan selection
     * Users can choose Free, Basic, or Pro plan
     */
    public function showRegistrationForm(Request $request)
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $selectedPlan = $request->get('plan', 'free'); // Default to free plan
        
        return view('auth.register', compact('plans', 'selectedPlan'));
    }

    /**
     * Handle a registration request
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email'),
                \Illuminate\Validation\Rule::unique('staff', 'email'),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{9}$/'],
            'city' => ['required', 'string', 'max:255'],
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        // Format phone number to include +255
        $phoneNumber = '+255' . $request->phone;

        // Create user (but don't activate yet - wait for OTP verification)
        // Business type will be selected in configuration wizard
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'business_name' => $request->business_name,
            'phone' => $phoneNumber,
            'address' => $request->address ?? '',
            'city' => $request->city,
            'country' => $request->country ?? 'Tanzania',
            'email_verified_at' => null, // Will verify after OTP
        ]);

        // Get selected plan
        $plan = Plan::findOrFail($request->plan_id);

        // Create subscription based on plan type
        if ($plan->slug === 'free') {
            // Free Plan - 30 days trial with all features
            $trialEndsAt = Carbon::now()->addDays($plan->trial_days);
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => Carbon::now(),
                'is_trial' => true,
            ]);
        } else {
            // Paid Plans (Basic/Pro) - pending until payment verified
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending', // Will be activated after payment verification
                'trial_ends_at' => null,
                'starts_at' => Carbon::now(),
                'is_trial' => false,
            ]);
        }

        // Generate OTP
        $otpCode = Otp::generateCode();
        $expiresAt = Carbon::now()->addMinutes(10); // OTP expires in 10 minutes

        // Create OTP record
        Otp::create([
            'user_id' => $user->id,
            'code' => $otpCode,
            'phone' => $phoneNumber,
            'expires_at' => $expiresAt,
        ]);

        // Send OTP via SMS
        $message = "HABARI! Your MauzoLink verification code is: " . $otpCode . ". This code expires in 10 minutes.";
        $smsResult = $this->smsService->sendSms($phoneNumber, $message);

        // Store user ID in session for OTP verification
        $request->session()->put('pending_user_id', $user->id);

        // Redirect to OTP verification page
        return redirect()->route('otp.verify')->with([
            'success' => 'Registration successful! Please verify your phone number with the OTP sent to ' . $phoneNumber,
            'sms_sent' => $smsResult['success']
        ]);
    }
}


