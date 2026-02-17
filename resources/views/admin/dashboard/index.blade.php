@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-dashboard"></i> Admin Dashboard</h1>
    <p>System Overview & Statistics</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin Dashboard</a></li>
  </ul>
</div>

<!-- Statistics Cards -->
<div class="row" style="margin-bottom: 20px;">
  <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3" style="margin-bottom: 15px;">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-users fa-3x"></i>
      <div class="info">
        <h4>Total Users</h4>
        <p><b>{{ number_format($totalUsers) }}</b></p>
        <small>
          <span class="text-success">+{{ $newUsersThisMonth }}</span> this month
          @if($userGrowth != 0)
            <span class="{{ $userGrowth > 0 ? 'text-success' : 'text-danger' }}">
              ({{ $userGrowth > 0 ? '+' : '' }}{{ $userGrowth }}%)
            </span>
          @endif
        </small>
      </div>
    </div>
  </div>
  
  <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3" style="margin-bottom: 15px;">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-credit-card fa-3x"></i>
      <div class="info">
        <h4>Total Revenue</h4>
        <p><b>TSh {{ number_format($totalRevenue, 0) }}</b></p>
        <small>
          This month: <span class="text-success">TSh {{ number_format($monthlyRevenue, 0) }}</span>
          @if($revenueGrowth != 0)
            <span class="{{ $revenueGrowth > 0 ? 'text-success' : 'text-danger' }}">
              ({{ $revenueGrowth > 0 ? '+' : '' }}{{ $revenueGrowth }}%)
            </span>
          @endif
        </small>
      </div>
    </div>
  </div>
  
  <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3" style="margin-bottom: 15px;">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-list fa-3x"></i>
      <div class="info">
        <h4>Subscriptions</h4>
        <p><b>{{ $totalSubscriptions }}</b></p>
        <small>
          Active: <span class="text-success">{{ $activeSubscriptions }}</span> | 
          Trial: <span class="text-info">{{ $trialSubscriptions }}</span> | 
          Pending: <span class="text-warning">{{ $pendingSubscriptions }}</span>
        </small>
      </div>
    </div>
  </div>
  
  <div class="col-xs-12 col-sm-6 col-md-6 col-lg-3" style="margin-bottom: 15px;">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-clock-o fa-3x"></i>
      <div class="info">
        <h4>Pending Payments</h4>
        <p><b>{{ $pendingPayments }}</b></p>
        <small>
          Amount: <span class="text-warning">TSh {{ number_format($pendingPaymentsAmount, 0) }}</span>
        </small>
      </div>
    </div>
  </div>
</div>

<!-- Charts Row -->
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Monthly Revenue</h3>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas id="revenueChart" height="100"></canvas>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">User Growth</h3>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas id="userGrowthChart" height="100"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Plan Distribution & Recent Activities -->
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Plan Distribution</h3>
      <div class="tile-body">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Plan</th>
              <th class="text-center">Subscriptions</th>
              <th class="text-right">Revenue</th>
            </tr>
          </thead>
          <tbody>
            @foreach($planDistribution as $plan)
            <tr>
              <td><strong>{{ $plan['name'] }}</strong></td>
              <td class="text-center">{{ $plan['count'] }}</td>
              <td class="text-right">TSh {{ number_format($plan['revenue'], 0) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">
        <i class="fa fa-clock-o"></i> Pending Payment Verifications
        @if($pendingVerifications->count() > 0)
          <span class="badge badge-warning">{{ $pendingVerifications->count() }}</span>
        @endif
        <a href="{{ route('admin.payments.index') }}" class="btn btn-sm btn-primary float-right">
          <i class="fa fa-list"></i> View All
        </a>
      </h3>
      <div class="tile-body">
        @if($pendingVerifications->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Plan</th>
                <th class="text-right">Amount</th>
                <th>Submitted</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pendingVerifications as $payment)
              <tr>
                <td><small><strong>{{ $payment->invoice->invoice_number ?? 'N/A' }}</strong></small></td>
                <td>
                  <small><strong>{{ $payment->user->name }}</strong></small><br>
                  <small class="text-muted">{{ $payment->user->email }}</small>
                </td>
                <td>
                  @if($payment->invoice && $payment->invoice->plan)
                    <small><span class="badge badge-info">{{ $payment->invoice->plan->name }}</span></small>
                  @else
                    <small class="text-muted">N/A</small>
                  @endif
                </td>
                <td class="text-right"><small><strong>TSh {{ number_format($payment->amount, 0) }}</strong></small></td>
                <td>
                  <small>{{ $payment->created_at->format('M d') }}</small><br>
                  <small class="text-muted">{{ $payment->created_at->diffForHumans() }}</small>
                </td>
                <td>
                  <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> Review
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @else
        <div class="text-center py-3">
          <i class="fa fa-check-circle fa-3x text-success mb-2"></i>
          <p class="text-muted mb-0">No pending payment verifications</p>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Recent Subscriptions -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">
        <i class="fa fa-list"></i> Recent Subscriptions
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm btn-primary float-right">
          <i class="fa fa-list"></i> View All
        </a>
      </h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Started</th>
                <th>Ends</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentSubscriptions as $subscription)
              <tr>
                <td>
                  <strong>{{ $subscription->user->name }}</strong><br>
                  <small class="text-muted">{{ $subscription->user->business_name }}</small>
                </td>
                <td>
                  @if($subscription->plan)
                    <span class="badge badge-info">{{ $subscription->plan->name }}</span>
                  @else
                    <span class="text-muted">Plan Deleted</span>
                  @endif
                </td>
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
                <td>
                  <small>{{ $subscription->starts_at ? $subscription->starts_at->format('M d, Y') : 'N/A' }}</small>
                </td>
                <td>
                  <small>
                    @if($subscription->ends_at)
                      {{ $subscription->ends_at->format('M d, Y') }}
                    @elseif($subscription->trial_ends_at)
                      {{ $subscription->trial_ends_at->format('M d, Y') }} <span class="text-info">(Trial)</span>
                    @else
                      -
                    @endif
                  </small>
                </td>
                <td>
                  <small>{{ $subscription->created_at->diffForHumans() }}</small>
                </td>
                <td>
                  <a href="{{ route('admin.subscriptions.show', $subscription) }}" class="btn btn-sm btn-primary">
                    <i class="fa fa-eye"></i> View
                  </a>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="7" class="text-center text-muted">No subscriptions found</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('js/admin/plugins/chart.js') }}"></script>
<script type="text/javascript">
  // Revenue Chart Data
  var revenueData = {
    labels: {!! json_encode(array_column($monthlyRevenueData, 'month')) !!},
    datasets: [{
      label: 'Revenue (TSh)',
      fillColor: 'rgba(148, 0, 0, 0.2)',
      strokeColor: '#940000',
      pointColor: '#940000',
      pointStrokeColor: '#fff',
      pointHighlightFill: '#fff',
      pointHighlightStroke: '#940000',
      data: {!! json_encode(array_column($monthlyRevenueData, 'revenue')) !!}
    }]
  };

  // User Growth Chart Data
  var userGrowthData = {
    labels: {!! json_encode(array_column($monthlyUserGrowthData, 'month')) !!},
    datasets: [{
      label: 'New Users',
      fillColor: 'rgba(6, 163, 218, 0.2)',
      strokeColor: '#06a3da',
      pointColor: '#06a3da',
      pointStrokeColor: '#fff',
      pointHighlightFill: '#fff',
      pointHighlightStroke: '#06a3da',
      data: {!! json_encode(array_column($monthlyUserGrowthData, 'users')) !!}
    }]
  };

  // Calculate max values for proper scaling
  var revenueMax = Math.max.apply(Math, revenueData.datasets[0].data);
  var revenueMaxRounded = revenueMax > 0 ? Math.ceil(revenueMax * 1.1) : 10; // Add 10% padding at top, min 10
  var revenueSteps = 5;
  var revenueStepWidth = Math.ceil(revenueMaxRounded / revenueSteps);
  if (revenueStepWidth === 0) revenueStepWidth = 1;
  
  var userGrowthMax = Math.max.apply(Math, userGrowthData.datasets[0].data);
  var userGrowthMaxRounded = userGrowthMax > 0 ? Math.ceil(userGrowthMax * 1.1) : 10; // Add 10% padding at top, min 10
  var userGrowthSteps = 5;
  var userGrowthStepWidth = Math.ceil(userGrowthMaxRounded / userGrowthSteps);
  if (userGrowthStepWidth === 0) userGrowthStepWidth = 1;

  // Initialize Charts with options
  var revenueCtx = document.getElementById('revenueChart').getContext('2d');
  var revenueChart = new Chart(revenueCtx).Line(revenueData, {
    scaleOverride: true,
    scaleSteps: revenueSteps,
    scaleStepWidth: revenueStepWidth,
    scaleStartValue: 0,
    scaleBeginAtZero: true,
    scaleShowGridLines: true,
    scaleGridLineColor: "rgba(0,0,0,.05)",
    scaleShowLabels: true,
    bezierCurve: true,
    bezierCurveTension: 0.4,
    pointDot: true,
    pointDotRadius: 4,
    pointDotStrokeWidth: 1,
    pointHitDetectionRadius: 20,
    datasetStroke: true,
    datasetStrokeWidth: 2,
    datasetFill: true,
    responsive: true,
    maintainAspectRatio: true
  });

  var userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
  var userGrowthChart = new Chart(userGrowthCtx).Bar(userGrowthData, {
    scaleOverride: true,
    scaleSteps: userGrowthSteps,
    scaleStepWidth: userGrowthStepWidth,
    scaleStartValue: 0,
    scaleBeginAtZero: true,
    scaleShowGridLines: true,
    scaleGridLineColor: "rgba(0,0,0,.05)",
    scaleShowLabels: true,
    barShowStroke: true,
    barStrokeWidth: 2,
    barValueSpacing: 5,
    barDatasetSpacing: 1,
    responsive: true,
    maintainAspectRatio: true
  });
</script>
@endsection
