<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Generate invoice for subscription
     */
    public function generateInvoice($userId, $planId)
    {
        $plan = Plan::findOrFail($planId);
        $user = \App\Models\User::findOrFail($userId);

        // Check if user already has a pending invoice for this plan
        $existingInvoice = Invoice::where('user_id', $user->id)
            ->where('plan_id', $planId)
            ->where('status', 'pending')
            ->first();

        if ($existingInvoice) {
            return $existingInvoice;
        }

        // Create new invoice
        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'user_id' => $user->id,
            'plan_id' => $planId,
            'amount' => $plan->price,
            'status' => 'pending',
            'due_date' => Carbon::now()->addDays(7), // 7 days to pay
            'issued_at' => Carbon::now(),
        ]);

        return $invoice;
    }

    /**
     * Show invoice details
     */
    public function show(Invoice $invoice)
    {
        // Ensure user can only view their own invoices
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        $invoice->load(['user', 'plan', 'payments']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Download invoice as PDF
     */
    public function download(Invoice $invoice)
    {
        // Ensure user can only download their own invoices
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        $invoice->load(['user', 'plan']);

        // For now, return view. Can be converted to PDF later
        return view('invoices.pdf', compact('invoice'));
    }
}
