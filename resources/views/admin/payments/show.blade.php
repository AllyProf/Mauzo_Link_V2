@extends('layouts.dashboard')

@section('title', 'Payment Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-file-text-o"></i> Payment Details</h1>
    <p>Review and verify payment</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li>
    <li class="breadcrumb-item">Payment Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="tile">
      <h3 class="tile-title">Payment Information</h3>
      <div class="tile-body">
        <table class="table table-bordered">
          <tr>
            <th width="40%">Invoice Number:</th>
            <td><strong>{{ $payment->invoice->invoice_number }}</strong></td>
          </tr>
          <tr>
            <th>Customer:</th>
            <td>
              <strong>{{ $payment->user->name }}</strong><br>
              <small class="text-muted">{{ $payment->user->business_name }}</small><br>
              <small>{{ $payment->user->email }} | {{ $payment->user->phone }}</small>
            </td>
          </tr>
          <tr>
            <th>Plan:</th>
            <td>{{ $payment->invoice->plan->name }}</td>
          </tr>
          <tr>
            <th>Amount:</th>
            <td><strong class="text-primary" style="font-size: 1.2em;">{{ $payment->formatted_amount }}</strong></td>
          </tr>
          <tr>
            <th>Payment Method:</th>
            <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
          </tr>
          <tr>
            <th>Payment Reference:</th>
            <td><strong>{{ $payment->payment_reference }}</strong></td>
          </tr>
          <tr>
            <th>Payment Date:</th>
            <td>{{ $payment->payment_date->format('F d, Y') }}</td>
          </tr>
          <tr>
            <th>Status:</th>
            <td>
              @if($payment->status === 'pending')
                <span class="badge badge-warning">Pending Verification</span>
              @elseif($payment->status === 'verified')
                <span class="badge badge-success">Verified</span>
              @else
                <span class="badge badge-danger">Rejected</span>
              @endif
            </td>
          </tr>
          <tr>
            <th>Submitted:</th>
            <td>{{ $payment->created_at->format('F d, Y h:i A') }}</td>
          </tr>
        </table>

        @if($payment->proof_file_path)
        <hr>
        <h5>Payment Proof</h5>
        <div class="text-center mb-3">
          <a href="{{ $payment->proof_url }}" target="_blank" class="btn btn-primary">
            <i class="fa fa-eye"></i> View Payment Proof
          </a>
        </div>
        <div class="text-center">
          <img src="{{ $payment->proof_url }}" alt="Payment Proof" class="img-fluid" style="max-height: 500px; border: 1px solid #ddd; border-radius: 5px;">
        </div>
        @endif

        @if($payment->admin_notes)
        <hr>
        <h5>Admin Notes</h5>
        <div class="alert alert-info">
          {{ $payment->admin_notes }}
        </div>
        @endif
      </div>
    </div>

    @if($payment->status === 'pending')
    <div class="tile">
      <h3 class="tile-title">Verification Actions</h3>
      <div class="tile-body">
        <form action="{{ route('admin.payments.verify', $payment) }}" method="POST" class="mb-3" id="verifyPaymentForm">
          @csrf
          <div class="form-group">
            <label for="admin_notes_verify">Admin Notes (Optional)</label>
            <textarea class="form-control" id="admin_notes_verify" name="admin_notes" rows="3" placeholder="Add any notes about this verification..."></textarea>
          </div>
          <button type="submit" class="btn btn-success btn-block" id="verifyPaymentBtn">
            <i class="fa fa-check-circle"></i> Verify Payment & Activate Subscription
          </button>
        </form>

        <form action="{{ route('admin.payments.reject', $payment) }}" method="POST">
          @csrf
          <div class="form-group">
            <label for="admin_notes_reject">Rejection Reason <span class="text-danger">*</span></label>
            <textarea class="form-control @error('admin_notes') is-invalid @enderror" id="admin_notes_reject" name="admin_notes" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
            @error('admin_notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to reject this payment? The customer will be notified.');">
            <i class="fa fa-times-circle"></i> Reject Payment
          </button>
        </form>
      </div>
    </div>
    @endif
  </div>

  <div class="col-md-4">
    <div class="tile">
      <h3 class="tile-title">Bank Account Details</h3>
      <div class="tile-body">
        <div class="card border-primary">
          <div class="card-body">
            <p class="mb-2"><strong>Bank:</strong> CRDB Bank</p>
            <p class="mb-2"><strong>Account Name:</strong> EmCa Technologies</p>
            <p class="mb-2"><strong>Account Number:</strong> <strong class="text-primary">329876567</strong></p>
            <p class="mb-0"><strong>Reference:</strong> {{ $payment->payment_reference }}</p>
          </div>
        </div>
        <div class="alert alert-info mt-3">
          <small>
            <strong>Instructions:</strong><br>
            Check your CRDB bank account for a payment of <strong>{{ $payment->formatted_amount }}</strong> with reference <strong>{{ $payment->payment_reference }}</strong>.
          </small>
        </div>
      </div>
    </div>

    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary btn-block">
          <i class="fa fa-arrow-left"></i> Back to List
        </a>
        <a href="{{ route('invoices.show', $payment->invoice) }}" class="btn btn-outline-primary btn-block">
          <i class="fa fa-file-text-o"></i> View Invoice
        </a>
      </div>
    </div>
  </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Handle Verify Payment form submission with SweetAlert
  document.getElementById('verifyPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const adminNotes = document.getElementById('admin_notes_verify').value;
    const customerName = '{{ $payment->user->name }}';
    const amount = '{{ $payment->formatted_amount }}';
    const planName = '{{ $payment->invoice->plan->name }}';
    
    Swal.fire({
      title: 'Verify Payment & Activate Subscription?',
      html: `
        <div class="text-left">
          <p><strong>Are you sure you want to verify this payment?</strong></p>
          <hr>
          <p><strong>Customer:</strong> ${customerName}</p>
          <p><strong>Plan:</strong> ${planName}</p>
          <p><strong>Amount:</strong> ${amount}</p>
          <p><strong>Invoice:</strong> {{ $payment->invoice->invoice_number }}</p>
          <hr>
          <p class="text-warning"><i class="fa fa-exclamation-triangle"></i> This will activate the customer's subscription immediately.</p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="fa fa-check-circle"></i> Yes, Verify Payment',
      cancelButtonText: '<i class="fa fa-times"></i> Cancel',
      reverseButtons: true,
      focusConfirm: false,
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Show loading state
        Swal.fire({
          title: 'Verifying Payment...',
          text: 'Please wait while we process your request.',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        // Submit the form
        form.submit();
      }
    });
  });
</script>

@if(session('success'))
<script>
  Swal.fire({
    icon: 'success',
    title: 'Payment Verified!',
    html: `
      <p>{{ session('success') }}</p>
      <p class="text-muted mt-2">The customer's subscription has been activated.</p>
    `,
    confirmButtonColor: '#940000',
    confirmButtonText: 'OK'
  }).then(() => {
    // Optionally reload the page to show updated status
    window.location.reload();
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

