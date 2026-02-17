<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * Display all users with filters
     */
    public function index(Request $request)
    {
        $query = User::customers()->with(['subscriptions.plan']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereHas('subscriptions', function($q) {
                    $q->where('status', 'active');
                });
            } elseif ($request->status === 'trial') {
                $query->whereHas('subscriptions', function($q) {
                    $q->where('status', 'trial')->where('is_trial', true);
                });
            } elseif ($request->status === 'pending') {
                $query->whereHas('subscriptions', function($q) {
                    $q->where('status', 'pending');
                });
            }
        }

        // Plan filter
        if ($request->filled('plan')) {
            $query->whereHas('subscriptions', function($q) use ($request) {
                $q->where('plan_id', $request->plan);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        $plans = \App\Models\Plan::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'plans'));
    }

    /**
     * Show user details
     */
    public function show(User $user)
    {
        $user->load(['subscriptions.plan', 'invoices.plan', 'payments.invoice.plan']);
        
        $activeSubscription = $user->activeSubscription;
        $subscriptionHistory = $user->subscriptions()->with('plan')->orderBy('created_at', 'desc')->get();
        $invoices = $user->invoices()->with('plan')->orderBy('created_at', 'desc')->get();
        $payments = $user->payments()->with(['invoice.plan'])->orderBy('created_at', 'desc')->get();

        return view('admin.users.show', compact(
            'user',
            'activeSubscription',
            'subscriptionHistory',
            'invoices',
            'payments'
        ));
    }

    /**
     * Activate user account
     */
    public function activate(User $user)
    {
        $user->update(['email_verified_at' => now()]);

        return redirect()->back()->with('success', 'User account activated successfully.');
    }

    /**
     * Deactivate user account
     */
    public function deactivate(User $user)
    {
        $user->update(['email_verified_at' => null]);

        return redirect()->back()->with('success', 'User account deactivated successfully.');
    }
}
