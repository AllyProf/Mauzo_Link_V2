@extends('layouts.dashboard')

@section('title', 'Subscription Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-list"></i> Subscription Management</h1>
    <p>Manage all user subscriptions</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item">Subscriptions</li>
  </ul>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('admin.subscriptions.index') }}">
        <div class="row">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search by user name, email, business..." value="{{ request('search') }}">
          </div>
          <div class="col-md-3">
            <select name="status" class="form-control">
              <option value="">All Status</option>
              <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
              <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Trial</option>
              <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
              <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
          </div>
          <div class="col-md-3">
            <select name="plan" class="form-control">
              <option value="">All Plans</option>
              @foreach($plans as $plan)
                <option value="{{ $plan->id }}" {{ request('plan') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa fa-search"></i> Filter
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Subscriptions Table -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead>
              <tr>
                <th>User</th>
                <th>Business</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Started</th>
                <th>Ends</th>
                <th>Trial Ends</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($subscriptions as $subscription)
              <tr>
                <td>
                  <strong>{{ $subscription->user->name }}</strong><br>
                  <small class="text-muted">{{ $subscription->user->email }}</small>
                </td>
                <td>{{ $subscription->user->business_name }}</td>
                <td><span class="badge badge-info">{{ $subscription->plan->name }}</span></td>
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
                <td>{{ $subscription->starts_at->format('M d, Y') }}</td>
                <td>{{ $subscription->ends_at ? $subscription->ends_at->format('M d, Y') : '-' }}</td>
                <td>
                  @if($subscription->is_trial && $subscription->trial_ends_at)
                    {{ $subscription->trial_ends_at->format('M d, Y') }}
                  @else
                    -
                  @endif
                </td>
                <td>
                  <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="8" class="text-center text-muted">No subscriptions found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        
        <div class="mt-3">
          {{ $subscriptions->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection












