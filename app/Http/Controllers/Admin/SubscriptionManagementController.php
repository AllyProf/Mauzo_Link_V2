<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionManagementController extends Controller
{
    /**
     * Display all subscriptions with filters
     */
    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'plan']);

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'trial') {
                $query->where('status', 'trial')->where('is_trial', true);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Plan filter
        if ($request->filled('plan')) {
            $query->where('plan_id', $request->plan);
        }

        // Search by user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(20);
        $plans = \App\Models\Plan::where('is_active', true)->get();

        return view('admin.subscriptions.index', compact('subscriptions', 'plans'));
    }

    /**
     * Show subscription details
     */
    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan', 'user.invoices', 'user.payments']);
        
        return view('admin.subscriptions.show', compact('subscription'));
    }

    /**
     * Activate subscription
     */
    public function activate(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'active',
            'is_trial' => false,
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addMonth(),
        ]);

        return redirect()->back()->with('success', 'Subscription activated successfully.');
    }

    /**
     * Suspend subscription
     */
    public function suspend(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'suspended',
        ]);

        return redirect()->back()->with('success', 'Subscription suspended successfully.');
    }

    /**
     * Cancel subscription
     */
    public function cancel(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
            'ends_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Subscription cancelled successfully.');
    }
}
