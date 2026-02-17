@extends('layouts.dashboard')

@section('title', 'Payment Instructions')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-credit-card"></i> Payment Instructions</h1>
    <p>Complete your payment to activate your subscription</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Payment Instructions</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="tile">
      <h3 class="tile-title">Invoice Details</h3>
      <div class="tile-body">
        <table class="table table-bordered">
          <tr>
            <th width="40%">Invoice Number:</th>
            <td><strong>{{ $invoice->invoice_number }}</strong></td>
          </tr>
          <tr>
            <th>Plan:</th>
            <td>{{ $invoice->plan->name }}</td>
          </tr>
          <tr>
            <th>Amount Due:</th>
            <td><strong class="text-primary" style="font-size: 1.2em;">{{ $invoice->formatted_amount }}</strong></td>
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
                <span class="badge badge-info">Payment Submitted - Awaiting Verification</span>
              @elseif($invoice->status === 'verified')
                <span class="badge badge-success">Payment Verified - Subscription Active</span>
              @else
                <span class="badge badge-secondary">{{ ucfirst($invoice->status) }}</span>
              @endif
            </td>
          </tr>
        </table>
      </div>
    </div>

    @if($invoice->status === 'pending' || $invoice->status === 'pending_verification' || ($invoice->status === 'paid' && !$invoice->verified_at))
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-university"></i> Bank Transfer Details</h3>
      <div class="tile-body">
        <div class="alert alert-info">
          <h5><i class="fa fa-info-circle"></i> Payment Instructions</h5>
          <p class="mb-2">Please transfer the exact amount to the bank account below:</p>
        </div>

        <div class="card border-primary mb-3">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa fa-bank"></i> CRDB Bank Account</h5>
          </div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr>
                <th width="40%">Bank Name:</th>
                <td><strong>CRDB Bank</strong></td>
              </tr>
              <tr>
                <th>Account Name:</th>
                <td><strong>EmCa Technologies</strong></td>
              </tr>
              <tr>
                <th>Account Number:</th>
                <td><strong class="text-primary" style="font-size: 1.3em;">329876567</strong></td>
              </tr>
              <tr>
                <th>Payment Reference:</th>
                <td><strong>{{ $invoice->invoice_number }}</strong></td>
              </tr>
              <tr>
                <th>Amount:</th>
                <td><strong class="text-success" style="font-size: 1.2em;">{{ $invoice->formatted_amount }}</strong></td>
              </tr>
            </table>
          </div>
        </div>

        <div class="alert alert-warning">
          <strong><i class="fa fa-exclamation-triangle"></i> Important:</strong>
          <ul class="mb-0 mt-2">
            <li>Use <strong>{{ $invoice->invoice_number }}</strong> as your payment reference</li>
            <li>Transfer the exact amount: <strong>{{ $invoice->formatted_amount }}</strong></li>
            <li>Take a screenshot or photo of your payment receipt</li>
            <li>Upload the proof below after making the transfer</li>
          </ul>
        </div>
      </div>
    </div>
    @endif

    @if($invoice->status === 'pending')
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-upload"></i> Upload Payment Proof</h3>
      <div class="tile-body">
        <form action="{{ route('payments.store-proof', $invoice) }}" method="POST" enctype="multipart/form-data">
          @csrf
          
          <div class="form-group">
            <label for="payment_reference">Payment Reference <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control @error('payment_reference') is-invalid @enderror" 
                   id="payment_reference" 
                   name="payment_reference" 
                   value="{{ old('payment_reference', $invoice->invoice_number) }}" 
                   required>
            <small class="form-text text-muted">Enter the reference number from your bank transfer (usually the invoice number)</small>
            @error('payment_reference')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="amount">Amount Paid (TSh) <span class="text-danger">*</span></label>
            <input type="number" 
                   class="form-control @error('amount') is-invalid @enderror" 
                   id="amount" 
                   name="amount" 
                   value="{{ old('amount', $invoice->amount) }}" 
                   step="0.01" 
                   min="0" 
                   required>
            <small class="form-text text-muted">Enter the exact amount you transferred: <strong>{{ $invoice->formatted_amount }}</strong></small>
            @error('amount')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
            <input type="date" 
                   class="form-control @error('payment_date') is-invalid @enderror" 
                   id="payment_date" 
                   name="payment_date" 
                   value="{{ old('payment_date', date('Y-m-d')) }}" 
                   max="{{ date('Y-m-d') }}" 
                   required>
            @error('payment_date')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="proof_file">Payment Proof (Screenshot/Receipt) <span class="text-danger">*</span></label>
            <input type="file" 
                   class="form-control-file @error('proof_file') is-invalid @enderror" 
                   id="proof_file" 
                   name="proof_file" 
                   accept="image/*,.pdf" 
                   required>
            <small class="form-text text-muted">Upload a clear screenshot or photo of your bank transfer receipt (JPG, PNG, or PDF - Max 5MB)</small>
            @error('proof_file')
              <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa fa-upload"></i> Submit Payment Proof
            </button>
          </div>
        </form>
      </div>
    </div>
    @elseif($invoice->status === 'pending_verification' || ($invoice->status === 'paid' && !$invoice->verified_at))
    <div class="tile">
      <div class="tile-body">
        <div class="alert alert-info">
          <h5><i class="fa fa-check-circle"></i> Payment Proof Submitted</h5>
          <p class="mb-0">Your payment proof has been received and is pending verification. We will verify your payment within 24-48 hours and activate your subscription.</p>
          <p class="mb-0 mt-2">You will receive an email and SMS notification once your payment is verified.</p>
        </div>
        
        @if($invoice->payments->count() > 0)
        <div class="mt-3">
          <h6>Submitted Payment Details:</h6>
          <table class="table table-sm">
            <tr>
              <th>Reference:</th>
              <td>{{ $invoice->payments->first()->payment_reference }}</td>
            </tr>
            <tr>
              <th>Amount:</th>
              <td>{{ $invoice->payments->first()->formatted_amount }}</td>
            </tr>
            <tr>
              <th>Date:</th>
              <td>{{ $invoice->payments->first()->payment_date->format('F d, Y') }}</td>
            </tr>
            <tr>
              <th>Status:</th>
              <td><span class="badge badge-warning">Pending Verification</span></td>
            </tr>
          </table>
        </div>
        @endif
      </div>
    </div>
    @elseif($invoice->status === 'verified')
    <div class="tile">
      <div class="tile-body">
        <div class="alert alert-success">
          <h5><i class="fa fa-check-circle"></i> Payment Verified!</h5>
          <p class="mb-0">Your payment has been verified and your subscription is now active. You can now access all features of your plan.</p>
        </div>
        
        @if($invoice->payments->count() > 0)
        <div class="mt-3">
          <h6>Payment Details:</h6>
          <table class="table table-sm">
            <tr>
              <th>Reference:</th>
              <td>{{ $invoice->payments->first()->payment_reference }}</td>
            </tr>
            <tr>
              <th>Amount:</th>
              <td>{{ $invoice->payments->first()->formatted_amount }}</td>
            </tr>
            <tr>
              <th>Date:</th>
              <td>{{ $invoice->payments->first()->payment_date->format('F d, Y') }}</td>
            </tr>
            <tr>
              <th>Verified:</th>
              <td>{{ $invoice->verified_at->format('F d, Y h:i A') }}</td>
            </tr>
          </table>
        </div>
        @endif
      </div>
    </div>
    @endif
  </div>

  <div class="col-md-4">
    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-outline-primary btn-block mb-2">
          <i class="fa fa-download"></i> Download Invoice
        </a>
        <a href="{{ route('payments.history') }}" class="btn btn-outline-secondary btn-block mb-2">
          <i class="fa fa-history"></i> Payment History
        </a>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-info btn-block">
          <i class="fa fa-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <div class="tile">
      <h3 class="tile-title">Need Help?</h3>
      <div class="tile-body">
        <p>If you have any questions about payment, please contact us:</p>
        <p class="mb-1"><i class="fa fa-phone"></i> <strong>+255 749 719 998</strong></p>
        <p class="mb-1"><i class="fa fa-envelope"></i> <strong>emca@emca.tech</strong></p>
        <p class="mb-0"><i class="fa fa-globe"></i> <strong>www.emca.tech</strong></p>
      </div>
    </div>
  </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('success'))
<script>
  Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: '{{ session('success') }}',
    confirmButtonColor: '#940000'
  });
</script>
@endif

@if(session('error'))
<script>
  Swal.fire({
    icon: 'error',
    title: 'Error!',
    text: '{{ session('error') }}',
    confirmButtonColor: '#940000'
  });
</script>
@endif
@endsection

