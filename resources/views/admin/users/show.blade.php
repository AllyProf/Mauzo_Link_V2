@extends('layouts.dashboard')

@section('title', 'User Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-user"></i> User Details</h1>
    <p>{{ $user->name }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item">Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-4">
    <div class="tile">
      <h3 class="tile-title">User Information</h3>
      <div class="tile-body">
        <table class="table table-borderless">
          <tr>
            <th width="40%">Name:</th>
            <td><strong>{{ $user->name }}</strong></td>
          </tr>
          <tr>
            <th>Email:</th>
            <td>{{ $user->email }}</td>
          </tr>
          <tr>
            <th>Phone:</th>
            <td>{{ $user->phone }}</td>
          </tr>
          <tr>
            <th>Business:</th>
            <td>{{ $user->business_name }}</td>
          </tr>
          <tr>
            <th>Type:</th>
            <td>{{ ucfirst($user->business_type) }}</td>
          </tr>
          <tr>
            <th>Address:</th>
            <td>{{ $user->address }}</td>
          </tr>
          <tr>
            <th>City:</th>
            <td>{{ $user->city }}</td>
          </tr>
          <tr>
            <th>Country:</th>
            <td>{{ $user->country }}</td>
          </tr>
          <tr>
            <th>Status:</th>
            <td>
              @if($user->email_verified_at)
                <span class="badge badge-success">Active</span>
              @else
                <span class="badge badge-warning">Inactive</span>
              @endif
            </td>
          </tr>
          <tr>
            <th>Registered:</th>
            <td>{{ $user->created_at->format('F d, Y h:i A') }}</td>
          </tr>
        </table>
        
        <hr>
        
        <form method="POST" action="{{ $user->email_verified_at ? route('admin.users.deactivate', $user) : route('admin.users.activate', $user) }}">
          @csrf
          <button type="submit" class="btn btn-{{ $user->email_verified_at ? 'warning' : 'success' }} btn-block">
            <i class="fa fa-{{ $user->email_verified_at ? 'ban' : 'check' }}"></i> 
            {{ $user->email_verified_at ? 'Deactivate' : 'Activate' }} Account
          </button>
        </form>
      </div>
    </div>
  </div>
  
  <div class="col-md-8">
    <!-- Current Subscription -->
    @if($activeSubscription)
    <div class="tile">
      <h3 class="tile-title">Current Subscription</h3>
      <div class="tile-body">
        <table class="table table-bordered">
          <tr>
            <th width="40%">Plan:</th>
            <td><strong>{{ $activeSubscription->plan->name }}</strong></td>
          </tr>
          <tr>
            <th>Status:</th>
            <td>
              @if($activeSubscription->status === 'active')
                <span class="badge badge-success">Active</span>
              @elseif($activeSubscription->status === 'trial')
                <span class="badge badge-info">Trial</span>
              @else
                <span class="badge badge-warning">Pending</span>
              @endif
            </td>
          </tr>
          <tr>
            <th>Started:</th>
            <td>{{ $activeSubscription->starts_at->format('F d, Y') }}</td>
          </tr>
          @if($activeSubscription->is_trial)
          <tr>
            <th>Trial Ends:</th>
            <td>{{ $activeSubscription->trial_ends_at->format('F d, Y') }}</td>
          </tr>
          @endif
          @if($activeSubscription->ends_at)
          <tr>
            <th>Ends:</th>
            <td>{{ $activeSubscription->ends_at->format('F d, Y') }}</td>
          </tr>
          @endif
        </table>
      </div>
    </div>
    @endif
    
    <!-- Subscription History -->
    <div class="tile">
      <h3 class="tile-title">Subscription History</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Plan</th>
                <th>Status</th>
                <th>Started</th>
                <th>Ended</th>
              </tr>
            </thead>
            <tbody>
              @forelse($subscriptionHistory as $sub)
              <tr>
                <td>{{ $sub->plan->name }}</td>
                <td>
                  @if($sub->status === 'active')
                    <span class="badge badge-success">Active</span>
                  @elseif($sub->status === 'trial')
                    <span class="badge badge-info">Trial</span>
                  @else
                    <span class="badge badge-secondary">{{ ucfirst($sub->status) }}</span>
                  @endif
                </td>
                <td>{{ $sub->starts_at->format('M d, Y') }}</td>
                <td>{{ $sub->ends_at ? $sub->ends_at->format('M d, Y') : '-' }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="4" class="text-center text-muted">No subscription history</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Payment History -->
    <div class="tile">
      <h3 class="tile-title">Payment History</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Plan</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @forelse($payments as $payment)
              <tr>
                <td>{{ $payment->invoice->invoice_number }}</td>
                <td>{{ $payment->invoice->plan->name }}</td>
                <td>TSh {{ number_format($payment->amount, 0) }}</td>
                <td>
                  @if($payment->status === 'verified')
                    <span class="badge badge-success">Verified</span>
                  @elseif($payment->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @else
                    <span class="badge badge-danger">Rejected</span>
                  @endif
                </td>
                <td>{{ $payment->created_at->format('M d, Y') }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No payments found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
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












