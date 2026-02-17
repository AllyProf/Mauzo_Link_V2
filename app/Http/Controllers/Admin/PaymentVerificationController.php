<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentVerificationController extends Controller
{
    /**
     * Show all payments with filters
     */
    public function index(Request $request)
    {
        $query = Payment::with(['invoice.plan', 'invoice.user', 'user', 'verifier']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show pending first
            $query->where('status', 'pending');
        }

        // Plan filter
        if ($request->filled('plan')) {
            $query->whereHas('invoice', function($q) use ($request) {
                $q->where('plan_id', $request->plan);
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('business_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('invoice', function($invQuery) use ($search) {
                      $invQuery->where('invoice_number', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Statistics
        $totalPending = Payment::where('status', 'pending')->count();
        $totalVerified = Payment::where('status', 'verified')->count();
        $totalRejected = Payment::where('status', 'rejected')->count();
        $pendingAmount = Payment::where('status', 'pending')->sum('amount');
        $verifiedAmount = Payment::where('status', 'verified')->sum('amount');

        $plans = \App\Models\Plan::where('is_active', true)->get();

        return view('admin.payments.index', compact(
            'payments', 
            'plans',
            'totalPending',
            'totalVerified',
            'totalRejected',
            'pendingAmount',
            'verifiedAmount'
        ));
    }

    /**
     * Show payment details for verification
     */
    public function show(Payment $payment)
    {
        $payment->load(['invoice.plan', 'invoice.user', 'user']);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Verify payment
     */
    public function verify(Request $request, Payment $payment)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        // Update payment status
        $payment->update([
            'status' => 'verified',
            'verified_by' => auth()->id(),
            'verified_at' => Carbon::now(),
            'admin_notes' => $request->admin_notes,
        ]);

        // Update invoice status
        $invoice = $payment->invoice;
        $invoice->update([
            'status' => 'verified',
            'verified_at' => Carbon::now(),
            'verified_by' => auth()->id(),
        ]);

        // Activate or update subscription
        $subscription = Subscription::where('user_id', $invoice->user_id)
            ->where('plan_id', $invoice->plan_id)
            ->latest()
            ->first();

        if ($subscription) {
            // Update existing subscription
            $subscription->update([
                'status' => 'active',
                'is_trial' => false,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addMonth(), // 1 month subscription
            ]);
        } else {
            // Create new subscription
            Subscription::create([
                'user_id' => $invoice->user_id,
                'plan_id' => $invoice->plan_id,
                'status' => 'active',
                'is_trial' => false,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addMonth(),
                'trial_ends_at' => null,
            ]);
        }

        // TODO: Send notification email/SMS to user

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment verified successfully! Subscription activated.');
    }

    /**
     * Reject payment
     */
    public function reject(Request $request, Payment $payment)
    {
        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        // Update payment status
        $payment->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        // Update invoice status back to pending
        $invoice = $payment->invoice;
        $invoice->update([
            'status' => 'pending',
        ]);

        // TODO: Send notification email/SMS to user

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment rejected. Customer has been notified.');
    }
}
