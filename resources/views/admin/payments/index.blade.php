@extends('layouts.dashboard')

@section('title', 'Payment Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Payment Management</h1>
    <p>Manage and verify all customer payments</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item">Payments</li>
  </ul>
</div>

<!-- Statistics -->
<div class="row mb-3">
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-clock-o fa-3x"></i>
      <div class="info">
        <h4>Pending</h4>
        <p><b>{{ $totalPending }}</b></p>
        <small>TSh {{ number_format($pendingAmount, 0) }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-check fa-3x"></i>
      <div class="info">
        <h4>Verified</h4>
        <p><b>{{ $totalVerified }}</b></p>
        <small>TSh {{ number_format($verifiedAmount, 0) }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-times fa-3x"></i>
      <div class="info">
        <h4>Rejected</h4>
        <p><b>{{ $totalRejected }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Revenue</h4>
        <p><b>TSh {{ number_format($verifiedAmount, 0) }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('admin.payments.index') }}">
        <div class="row">
          <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search by reference, user, invoice..." value="{{ request('search') }}">
          </div>
          <div class="col-md-2">
            <select name="status" class="form-control">
              <option value="">All Status</option>
              <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
              <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="plan" class="form-control">
              <option value="">All Plans</option>
              @foreach($plans as $plan)
                <option value="{{ $plan->id }}" {{ request('plan') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <input type="date" name="date_from" class="form-control" placeholder="From Date" value="{{ request('date_from') }}">
          </div>
          <div class="col-md-2">
            <input type="date" name="date_to" class="form-control" placeholder="To Date" value="{{ request('date_to') }}">
          </div>
          <div class="col-md-1">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa fa-search"></i>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Reference</th>
                <th>Payment Date</th>
                <th>Status</th>
                <th>Verified By</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($payments as $payment)
              <tr>
                <td><strong>{{ $payment->invoice->invoice_number }}</strong></td>
                <td>
                  {{ $payment->user->name }}<br>
                  <small class="text-muted">{{ $payment->user->business_name }}</small>
                </td>
                <td>{{ $payment->invoice->plan->name }}</td>
                <td><strong>TSh {{ number_format($payment->amount, 0) }}</strong></td>
                <td>{{ $payment->payment_reference }}</td>
                <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                <td>
                  @if($payment->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @elseif($payment->status === 'verified')
                    <span class="badge badge-success">Verified</span>
                  @else
                    <span class="badge badge-danger">Rejected</span>
                  @endif
                </td>
                <td>
                  @if($payment->verifier)
                    {{ $payment->verifier->name }}<br>
                    <small>{{ $payment->verified_at->format('M d, Y') }}</small>
                  @else
                    -
                  @endif
                </td>
                <td>
                  <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center text-muted">No payments found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        
        <div class="mt-3">
          {{ $payments->links() }}
        </div>
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
@endsection

