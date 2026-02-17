@extends('layouts.dashboard')

@section('title', 'Payment History')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-history"></i> Payment History</h1>
    <p>View all your invoices and payment records</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Payment History</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        @if($invoices->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($invoices as $invoice)
              <tr>
                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                <td>{{ $invoice->plan->name }}</td>
                <td>{{ $invoice->formatted_amount }}</td>
                <td>{{ $invoice->due_date->format('M d, Y') }}</td>
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
                <td>
                  <a href="{{ route('payments.instructions', $invoice) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> View
                  </a>
                  <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-download"></i> Download
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          {{ $invoices->links() }}
        </div>
        @else
        <div class="text-center py-5">
          <i class="fa fa-file-o fa-4x text-muted mb-3"></i>
          <p class="text-muted">No invoices found.</p>
          <a href="{{ route('dashboard') }}" class="btn btn-primary">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
          </a>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

