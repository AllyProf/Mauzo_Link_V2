@extends('layouts.dashboard')

@section('title', 'Plan Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-credit-card"></i> Plan Management</h1>
    <p>Manage subscription plans</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item">Plans</li>
  </ul>
</div>

<div class="row mb-3">
  <div class="col-md-12">
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
      <i class="fa fa-plus"></i> Create New Plan
    </a>
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
                <th>Name</th>
                <th>Slug</th>
                <th>Price</th>
                <th>Trial Days</th>
                <th>Max Locations</th>
                <th>Max Users</th>
                <th>Subscriptions</th>
                <th>Status</th>
                <th>Sort Order</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($plans as $plan)
              <tr>
                <td><strong>{{ $plan->name }}</strong></td>
                <td><code>{{ $plan->slug }}</code></td>
                <td>TSh {{ number_format($plan->price, 0) }}</td>
                <td>{{ $plan->trial_days }} days</td>
                <td>{{ $plan->max_locations == 999 ? 'Unlimited' : $plan->max_locations }}</td>
                <td>{{ $plan->max_users == 999 ? 'Unlimited' : $plan->max_users }}</td>
                <td>{{ $plan->subscriptions()->count() }}</td>
                <td>
                  @if($plan->is_active)
                    <span class="badge badge-success">Active</span>
                  @else
                    <span class="badge badge-secondary">Inactive</span>
                  @endif
                </td>
                <td>{{ $plan->sort_order }}</td>
                <td>
                  <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-edit"></i> Edit
                  </a>
                  <form method="POST" action="{{ route('admin.plans.toggle-status', $plan) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-{{ $plan->is_active ? 'warning' : 'success' }}">
                      <i class="fa fa-{{ $plan->is_active ? 'ban' : 'check' }}"></i> {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                  </form>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="10" class="text-center text-muted">No plans found</td>
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












