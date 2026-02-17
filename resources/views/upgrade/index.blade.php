@extends('layouts.dashboard')

@section('title', 'Upgrade Plan')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-arrow-up"></i> Upgrade Your Plan</h1>
    <p>Choose the perfect plan for your business</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Upgrade</li>
  </ul>
</div>

@if($currentPlan && $currentPlan->slug === 'free')
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-info">
      <h5><i class="fa fa-info-circle"></i> Your Free Trial</h5>
      @if($trialDaysRemaining > 0)
        <p class="mb-0">You have <strong>{{ $trialDaysRemaining }} day(s)</strong> remaining in your free trial. Upgrade now to continue enjoying all features after your trial ends.</p>
      @else
        <p class="mb-0">Your free trial has ended. Please upgrade to continue using MauzoLink.</p>
      @endif
    </div>
  </div>
</div>
@endif

<div class="row">
  @foreach($upgradePlans as $plan)
  <div class="col-md-6">
    <div class="tile" style="min-height: 600px;">
      <div class="tile-title-w-btn">
        <h3 class="title">{{ $plan->name }}</h3>
        @if($plan->slug === 'basic')
          <span class="badge badge-info">For Sole Proprietors</span>
        @elseif($plan->slug === 'pro')
          <span class="badge badge-success">For Businesses</span>
        @endif
      </div>
      <div class="tile-body text-center">
        <h2 class="text-primary mb-3">
          TSh {{ number_format($plan->price, 0) }}
          <small class="text-muted">/mwezi</small>
        </h2>
        <p class="text-muted mb-4">{{ $plan->description }}</p>
        
        <hr>
        
        <h6 class="mb-3">Features:</h6>
        <ul class="list-unstyled text-left" style="padding-left: 20px;">
          @if($plan->features)
            @foreach($plan->features as $feature)
              <li class="mb-2"><i class="fa fa-check text-success me-2"></i>{{ $feature }}</li>
            @endforeach
          @endif
        </ul>
        
        <div class="mt-4">
          <p class="mb-2"><strong>Max Locations:</strong> {{ $plan->max_locations == 999 ? 'Unlimited' : $plan->max_locations }}</p>
          <p class="mb-0"><strong>Max Users:</strong> {{ $plan->max_users == 999 ? 'Unlimited' : $plan->max_users }}</p>
        </div>
      </div>
      <div class="tile-footer">
        <form action="{{ route('upgrade.process') }}" method="POST">
          @csrf
          <input type="hidden" name="plan_id" value="{{ $plan->id }}">
          <button type="submit" class="btn btn-primary btn-block">
            <i class="fa fa-arrow-up"></i> Upgrade to {{ $plan->name }}
          </button>
        </form>
      </div>
    </div>
  </div>
  @endforeach
</div>

<div class="row mt-4">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Plan Comparison</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>Feature</th>
                <th class="text-center">Basic Plan</th>
                <th class="text-center">Pro Plan</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Best For</strong></td>
                <td class="text-center">Sole Proprietors (Single User)</td>
                <td class="text-center">Businesses with Staff (Multi-User)</td>
              </tr>
              <tr>
                <td><strong>Users</strong></td>
                <td class="text-center">1 User</td>
                <td class="text-center">Unlimited Users</td>
              </tr>
              <tr>
                <td><strong>Locations</strong></td>
                <td class="text-center">1 Location</td>
                <td class="text-center">Unlimited Locations</td>
              </tr>
              <tr>
                <td><strong>Role-Based Access</strong></td>
                <td class="text-center"><i class="fa fa-times text-danger"></i></td>
                <td class="text-center"><i class="fa fa-check text-success"></i></td>
              </tr>
              <tr>
                <td><strong>API Access</strong></td>
                <td class="text-center"><i class="fa fa-times text-danger"></i></td>
                <td class="text-center"><i class="fa fa-check text-success"></i></td>
              </tr>
              <tr>
                <td><strong>Support</strong></td>
                <td class="text-center">Email Support</td>
                <td class="text-center">Priority 24/7 Support</td>
              </tr>
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












