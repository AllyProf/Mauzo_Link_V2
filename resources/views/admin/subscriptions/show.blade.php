@extends('layouts.dashboard')

@section('title', 'Subscription Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-list"></i> Subscription Details</h1>
    <p>{{ $subscription->user->name }} - {{ $subscription->plan->name }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.subscriptions.index') }}">Subscriptions</a></li>
    <li class="breadcrumb-item">Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Subscription Information</h3>
      <div class="tile-body">
        <table class="table table-bordered">
          <tr>
            <th width="40%">User:</th>
            <td>
              <strong>{{ $subscription->user->name }}</strong><br>
              <small>{{ $subscription->user->email }}</small>
            </td>
          </tr>
          <tr>
            <th>Business:</th>
            <td>{{ $subscription->user->business_name }}</td>
          </tr>
          <tr>
            <th>Plan:</th>
            <td><strong>{{ $subscription->plan->name }}</strong></td>
          </tr>
          <tr>
            <th>Status:</th>
            <td>
              @if($subscription->status === 'active')
                <span class="badge badge-success">Active</span>
              @elseif($subscription->status === 'trial')
                <span class="badge badge-info">Trial</span>
              @elseif($subscription->status === 'pending')
                <span class="badge badge-warning">Pending</span>
              @elseif($subscription->status === 'suspended')
                <span class="badge badge-danger">Suspended</span>
              @else
                <span class="badge badge-secondary">{{ ucfirst($subscription->status) }}</span>
              @endif
            </td>
          </tr>
          <tr>
            <th>Is Trial:</th>
            <td>{{ $subscription->is_trial ? 'Yes' : 'No' }}</td>
          </tr>
          <tr>
            <th>Started:</th>
            <td>{{ $subscription->starts_at->format('F d, Y') }}</td>
          </tr>
          @if($subscription->ends_at)
          <tr>
            <th>Ends:</th>
            <td>{{ $subscription->ends_at->format('F d, Y') }}</td>
          </tr>
          @endif
          @if($subscription->is_trial && $subscription->trial_ends_at)
          <tr>
            <th>Trial Ends:</th>
            <td>{{ $subscription->trial_ends_at->format('F d, Y') }}</td>
          </tr>
          @endif
          <tr>
            <th>Created:</th>
            <td>{{ $subscription->created_at->format('F d, Y h:i A') }}</td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Actions -->
    <div class="tile">
      <h3 class="tile-title">Actions</h3>
      <div class="tile-body">
        @if($subscription->status !== 'active')
        <form method="POST" action="{{ route('admin.subscriptions.activate', $subscription) }}" class="mb-2">
          @csrf
          <button type="submit" class="btn btn-success btn-block">
            <i class="fa fa-check"></i> Activate Subscription
          </button>
        </form>
        @endif

        @if($subscription->status === 'active')
        <form method="POST" action="{{ route('admin.subscriptions.suspend', $subscription) }}" class="mb-2">
          @csrf
          <button type="submit" class="btn btn-warning btn-block" onclick="return confirm('Are you sure you want to suspend this subscription?');">
            <i class="fa fa-pause"></i> Suspend Subscription
          </button>
        </form>
        @endif

        @if($subscription->status !== 'cancelled')
        <form method="POST" action="{{ route('admin.subscriptions.cancel', $subscription) }}">
          @csrf
          <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to cancel this subscription?');">
            <i class="fa fa-times"></i> Cancel Subscription
          </button>
        </form>
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">User Information</h3>
      <div class="tile-body">
        <table class="table table-borderless">
          <tr>
            <th width="40%">Name:</th>
            <td>{{ $subscription->user->name }}</td>
          </tr>
          <tr>
            <th>Email:</th>
            <td>{{ $subscription->user->email }}</td>
          </tr>
          <tr>
            <th>Phone:</th>
            <td>{{ $subscription->user->phone }}</td>
          </tr>
          <tr>
            <th>Business:</th>
            <td>{{ $subscription->user->business_name }}</td>
          </tr>
          <tr>
            <th>City:</th>
            <td>{{ $subscription->user->city }}</td>
          </tr>
        </table>
        <a href="{{ route('admin.users.show', $subscription->user) }}" class="btn btn-primary btn-block">
          <i class="fa fa-user"></i> View Full User Profile
        </a>
      </div>
    </div>

    <div class="tile">
      <h3 class="tile-title">Plan Details</h3>
      <div class="tile-body">
        <table class="table table-borderless">
          <tr>
            <th width="40%">Plan Name:</th>
            <td><strong>{{ $subscription->plan->name }}</strong></td>
          </tr>
          <tr>
            <th>Price:</th>
            <td>TSh {{ number_format($subscription->plan->price, 0) }}/month</td>
          </tr>
          <tr>
            <th>Max Locations:</th>
            <td>{{ $subscription->plan->max_locations == 999 ? 'Unlimited' : $subscription->plan->max_locations }}</td>
          </tr>
          <tr>
            <th>Max Users:</th>
            <td>{{ $subscription->plan->max_users == 999 ? 'Unlimited' : $subscription->plan->max_users }}</td>
          </tr>
        </table>
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












