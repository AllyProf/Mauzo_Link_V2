<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UpgradeController extends Controller
{
    /**
     * Show upgrade page with plan options
     */
    public function index()
    {
        $user = auth()->user();
        $subscription = $user->activeSubscription;
        
        // Get upgrade plans (Basic and Pro)
        $upgradePlans = Plan::where('slug', '!=', 'free')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        // Get current plan info
        $currentPlan = $subscription ? $subscription->plan : null;
        $trialDaysRemaining = $subscription && $subscription->is_trial ? $subscription->getTrialDaysRemaining() : 0;
        
        return view('upgrade.index', compact('upgradePlans', 'currentPlan', 'trialDaysRemaining'));
    }

    /**
     * Process plan upgrade
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $user = auth()->user();
        $selectedPlan = Plan::findOrFail($request->plan_id);
        
        // Don't allow upgrading to Free Plan
        if ($selectedPlan->slug === 'free') {
            return redirect()->back()->with('error', 'You cannot upgrade to Free Plan.');
        }

        // Check if user already has this plan
        $currentSubscription = $user->activeSubscription;
        if ($currentSubscription && $currentSubscription->plan_id == $selectedPlan->id) {
            return redirect()->back()->with('error', 'You are already on this plan.');
        }

        // Generate invoice for the selected plan
        $invoiceController = new InvoiceController();
        $invoice = $invoiceController->generateInvoice($user->id, $selectedPlan->id);

        // Redirect to payment instructions
        return redirect()->route('payments.instructions', $invoice)
            ->with('success', 'Invoice generated! Please complete payment to activate your ' . $selectedPlan->name . ' subscription.');
    }
}
