@extends('layouts.dashboard')

@section('title', 'Invoice Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-file-text-o"></i> Invoice Details</h1>
    <p>Invoice #{{ $invoice->invoice_number }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Invoice</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="tile">
      <div class="tile-body">
        <div class="row mb-4">
          <div class="col-md-6">
            <h4>Invoice Details</h4>
            <table class="table table-borderless">
              <tr>
                <th>Invoice Number:</th>
                <td><strong>{{ $invoice->invoice_number }}</strong></td>
              </tr>
              <tr>
                <th>Issue Date:</th>
                <td>{{ $invoice->issued_at->format('F d, Y') }}</td>
              </tr>
              <tr>
                <th>Due Date:</th>
                <td>{{ $invoice->due_date->format('F d, Y') }}</td>
              </tr>
              <tr>
                <th>Status:</th>
                <td>
                  @if($invoice->status === 'pending')
                    <span class="badge badge-warning">Pending Payment</span>
                  @elseif($invoice->status === 'pending_verification' || ($invoice->status === 'paid' && !$invoice->verified_at))
                    <span class="badge badge-info">Awaiting Verification</span>
                  @elseif($invoice->status === 'verified')
                    <span class="badge badge-success">Verified</span>
                  @else
                    <span class="badge badge-secondary">{{ ucfirst($invoice->status) }}</span>
                  @endif
                </td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <h4>Bill To</h4>
            <p>
              <strong>{{ $invoice->user->name }}</strong><br>
              {{ $invoice->user->business_name }}<br>
              {{ $invoice->user->email }}<br>
              {{ $invoice->user->phone }}
            </p>
          </div>
        </div>

        <hr>

        <h5>Plan Details</h5>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Description</th>
              <th class="text-right">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <strong>{{ $invoice->plan->name }}</strong><br>
                <small class="text-muted">{{ $invoice->plan->description }}</small>
              </td>
              <td class="text-right"><strong>{{ $invoice->formatted_amount }}</strong></td>
            </tr>
            <tr>
              <td class="text-right"><strong>Total:</strong></td>
              <td class="text-right"><strong>{{ $invoice->formatted_amount }}</strong></td>
            </tr>
          </tbody>
        </table>

        @if($invoice->payments->count() > 0)
        <hr>
        <h5>Payment History</h5>
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Date</th>
              <th>Reference</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($invoice->payments as $payment)
            <tr>
              <td>{{ $payment->payment_date->format('M d, Y') }}</td>
              <td>{{ $payment->payment_reference }}</td>
              <td>{{ $payment->formatted_amount }}</td>
              <td>
                @if($payment->status === 'pending')
                  <span class="badge badge-warning">Pending</span>
                @elseif($payment->status === 'verified')
                  <span class="badge badge-success">Verified</span>
                @else
                  <span class="badge badge-danger">Rejected</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
        @endif
      </div>
      <div class="tile-footer">
        <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-primary">
          <i class="fa fa-download"></i> Download Invoice
        </a>
        @if($invoice->status === 'pending')
        <a href="{{ route('payments.instructions', $invoice) }}" class="btn btn-success">
          <i class="fa fa-credit-card"></i> Make Payment
        </a>
        @endif
        <a href="{{ route('payments.history') }}" class="btn btn-secondary">
          <i class="fa fa-arrow-left"></i> Back to History
        </a>
      </div>
    </div>
  </div>
</div>
@endsection

