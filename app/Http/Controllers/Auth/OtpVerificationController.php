<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use App\Models\Subscription;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OtpVerificationController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Show OTP verification form
     */
    public function showVerificationForm(Request $request)
    {
        $userId = $request->session()->get('pending_user_id');
        
        if (!$userId) {
            return redirect()->route('register')->with('error', 'Please register first.');
        }

        $user = User::find($userId);
        
        if (!$user) {
            return redirect()->route('register')->with('error', 'User not found. Please register again.');
        }

        // Mask phone number for display
        $phone = $user->phone;
        $maskedPhone = substr($phone, 0, 4) . '****' . substr($phone, -3);

        return view('auth.verify-otp', compact('user', 'maskedPhone'));
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $userId = $request->session()->get('pending_user_id');
        
        if (!$userId) {
            return redirect()->route('register')->with('error', 'Session expired. Please register again.');
        }

        $user = User::findOrFail($userId);

        // Find the latest unverified OTP for this user
        $otp = Otp::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->where('verified', false)
            ->latest()
            ->first();

        if (!$otp) {
            return back()->withErrors(['otp' => 'Invalid OTP code.'])->withInput();
        }

        if ($otp->isExpired()) {
            return back()->withErrors(['otp' => 'OTP code has expired. Please request a new one.'])->withInput();
        }

        // Verify OTP
        $otp->update(['verified' => true]);

        // Activate user account
        $user->update([
            'email_verified_at' => Carbon::now(),
        ]);

        // Activate subscription
        $subscription = Subscription::where('user_id', $user->id)->latest()->first();
        $plan = null;
        if ($subscription) {
            // Update subscription status based on plan type
            if ($subscription->plan->slug === 'free') {
                // Free Plan - activate trial
                $subscription->update(['status' => 'trial']);
            } else {
                // Paid Plans (Basic/Pro) - keep as pending, generate invoice
                // Status remains 'pending' until payment is verified
            }
            $plan = $subscription->plan;
            
            // Generate invoice for paid plans (Basic/Pro)
            if ($plan && $plan->price > 0) {
                $invoiceController = new \App\Http\Controllers\InvoiceController();
                $invoiceController->generateInvoice($user->id, $plan->id);
            }
        }

        // Send welcome SMS with credentials and plan information
        $this->sendWelcomeSms($user, $plan);

        // Clear pending user session
        $request->session()->forget('pending_user_id');

        // Redirect to login page with success message
        return redirect()->route('login')->with('success', 'Phone number verified successfully! Welcome SMS with your login credentials has been sent to your phone number.');
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request)
    {
        $userId = $request->session()->get('pending_user_id');
        
        if (!$userId) {
            return back()->with('error', 'Session expired. Please register again.');
        }

        $user = User::findOrFail($userId);

        // Invalidate previous OTPs
        Otp::where('user_id', $user->id)
            ->where('verified', false)
            ->update(['verified' => true]);

        // Generate new OTP
        $otpCode = Otp::generateCode();
        $expiresAt = Carbon::now()->addMinutes(10);

        Otp::create([
            'user_id' => $user->id,
            'code' => $otpCode,
            'phone' => $user->phone,
            'expires_at' => $expiresAt,
        ]);

        // Send OTP via SMS
        $message = "HABARI! Your MauzoLink verification code is: " . $otpCode . ". This code expires in 10 minutes.";
        $smsResult = $this->smsService->sendSms($user->phone, $message);

        if ($smsResult['success']) {
            return back()->with('success', 'OTP code has been resent to ' . substr($user->phone, 0, 4) . '****' . substr($user->phone, -3));
        } else {
            return back()->with('error', 'Failed to send OTP. Please try again.');
        }
    }

    /**
     * Send welcome SMS with credentials and plan information
     */
    private function sendWelcomeSms($user, $plan)
    {
        $planName = $plan ? $plan->name : 'Free Plan';
        $planPrice = $plan && $plan->price > 0 ? 'TSh ' . number_format($plan->price, 0) . '/month' : 'BURE';
        $trialDays = $plan ? $plan->trial_days : 30;
        
        // Get business information
        $businessName = $user->business_name ?? 'N/A';
        $businessType = $user->business_type ?? 'N/A';
        $city = $user->city ?? 'N/A';
        
        $message = "HABARI! Karibu MauzoLink!\n\n";
        $message .= "Akaunti yako imeundwa kwa mafanikio.\n\n";
        $message .= "TAARIFA ZA AKAUNTI:\n";
        $message .= "Jina: " . $user->name . "\n";
        $message .= "Biashara: " . $businessName . "\n";
        $message .= "Aina: " . ucfirst($businessType) . "\n";
        $message .= "Mji: " . $city . "\n\n";
        $message .= "PLAN YAKO:\n";
        $message .= "Jina: " . $planName . "\n";
        $message .= "Bei: " . $planPrice . "\n";
        $message .= "Majaribio: " . $trialDays . " siku bure\n\n";
        $message .= "HATUA ZA KULOGIN:\n";
        $message .= "Email: " . $user->email . "\n";
        $message .= "Password: (uliyoichagua wakati wa kujiandikisha)\n\n";
        $message .= "Tafadhali login kwa kutumia credentials hapo juu.\n\n";
        $message .= "Asante kwa kuchagua MauzoLink!";
        
        $this->smsService->sendSms($user->phone, $message);
    }
}
