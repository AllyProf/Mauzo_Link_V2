<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Show payment instructions page
     */
    public function showInstructions(Invoice $invoice)
    {
        // Ensure user can only view their own invoices
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        $invoice->load(['plan']);

        return view('payments.instructions', compact('invoice'));
    }

    /**
     * Store payment proof
     */
    public function storeProof(Request $request, Invoice $invoice)
    {
        // Ensure user can only submit proof for their own invoices
        if ($invoice->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if invoice already has a verified payment
        if ($invoice->status === 'verified') {
            return redirect()->back()
                ->with('error', 'This invoice has already been verified.');
        }

        // Check if payment proof has already been submitted
        if ($invoice->status === 'pending_verification' || ($invoice->status === 'paid' && !$invoice->verified_at)) {
            return redirect()->back()
                ->with('error', 'Payment proof has already been submitted for this invoice. Please wait for verification.');
        }

        $request->validate([
            'payment_reference' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date|before_or_equal:today',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        // Validate amount matches invoice
        if ($request->amount != $invoice->amount) {
            return redirect()->back()
                ->withErrors(['amount' => 'Payment amount must match invoice amount (TSh ' . number_format($invoice->amount, 0) . ')'])
                ->withInput();
        }

        // Store proof file
        $proofPath = $request->file('proof_file')->store('payment-proofs', 'public');

        // Create payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'payment_method' => 'bank_transfer',
            'payment_reference' => $request->payment_reference,
            'payment_date' => $request->payment_date,
            'status' => 'pending',
            'proof_file_path' => $proofPath,
        ]);

        // Update invoice status to pending_verification (payment proof submitted, waiting for admin verification)
        $invoice->update([
            'status' => 'pending_verification',
            'paid_at' => Carbon::now(),
        ]);

        return redirect()->route('payments.instructions', $invoice)
            ->with('success', 'Payment proof submitted successfully! We will verify your payment within 24-48 hours.');
    }

    /**
     * View payment history
     */
    public function history()
    {
        $invoices = Invoice::where('user_id', auth()->id())
            ->with(['plan', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('payments.history', compact('invoices'));
    }
}
