@extends('layouts.dashboard')

@section('title', 'User Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> User Management</h1>
    <p>Manage all system users</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item">Users</li>
  </ul>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('admin.users.index') }}">
        <div class="row">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name, email, business..." value="{{ request('search') }}">
          </div>
          <div class="col-md-3">
            <select name="status" class="form-control">
              <option value="">All Status</option>
              <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
              <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Trial</option>
              <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
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

<!-- Users Table -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead>
              <tr>
                <th>Name</th>
                <th>Business</th>
                <th>Email</th>
                <th>Phone</th>
                <th>City</th>
                <th>Current Plan</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $user)
              <tr>
                <td><strong>{{ $user->name }}</strong></td>
                <td>{{ $user->business_name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->phone }}</td>
                <td>{{ $user->city }}</td>
                <td>
                  @if($user->activeSubscription)
                    <span class="badge badge-info">{{ $user->activeSubscription->plan->name }}</span>
                  @else
                    <span class="badge badge-secondary">No Plan</span>
                  @endif
                </td>
                <td>
                  @if($user->activeSubscription)
                    @if($user->activeSubscription->status === 'active')
                      <span class="badge badge-success">Active</span>
                    @elseif($user->activeSubscription->status === 'trial')
                      <span class="badge badge-info">Trial</span>
                    @else
                      <span class="badge badge-warning">Pending</span>
                    @endif
                  @else
                    <span class="badge badge-secondary">Inactive</span>
                  @endif
                </td>
                <td>{{ $user->created_at->format('M d, Y') }}</td>
                <td>
                  <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center text-muted">No users found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        
        <div class="mt-3">
          {{ $users->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection












