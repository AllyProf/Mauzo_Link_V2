@extends('layouts.dashboard')

@section('title', 'Analytics & Reports')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-bar-chart"></i> Analytics & Reports</h1>
    <p>System analytics and performance metrics</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Admin</a></li>
    <li class="breadcrumb-item">Analytics</li>
  </ul>
</div>

<!-- Date Range Filter -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('admin.analytics.index') }}">
        <div class="row">
          <div class="col-md-4">
            <label>Date From:</label>
            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
          </div>
          <div class="col-md-4">
            <label>Date To:</label>
            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
          </div>
          <div class="col-md-4">
            <label>&nbsp;</label>
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa fa-filter"></i> Apply Filter
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Key Metrics -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Revenue</h4>
        <p><b>TSh {{ number_format($totalRevenue, 0) }}</b></p>
        <small>This month: TSh {{ number_format($monthlyRevenue, 0) }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-users fa-3x"></i>
      <div class="info">
        <h4>Total Users</h4>
        <p><b>{{ number_format($totalUsers) }}</b></p>
        <small>Active: {{ $activeUsers }} | New: {{ $newUsers }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-list fa-3x"></i>
      <div class="info">
        <h4>Subscriptions</h4>
        <p><b>{{ $totalSubscriptions }}</b></p>
        <small>Active: {{ $activeSubscriptions }} | Trial: {{ $trialSubscriptions }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-check fa-3x"></i>
      <div class="info">
        <h4>Active Users</h4>
        <p><b>{{ $activeUsers }}</b></p>
        <small>{{ $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0 }}% of total</small>
      </div>
    </div>
  </div>
</div>

<!-- Charts -->
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Daily Revenue (Last 30 Days)</h3>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas id="dailyRevenueChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Monthly Revenue (Last 12 Months)</h3>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas id="monthlyRevenueChart"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">User Growth (Last 12 Months)</h3>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas id="userGrowthChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Plan Performance</h3>
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
            @foreach($planPerformance as $plan)
            <tr>
              <td><strong>{{ $plan['name'] }}</strong></td>
              <td class="text-center">{{ $plan['subscriptions'] }}</td>
              <td class="text-right">TSh {{ number_format($plan['revenue'], 0) }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Top Customers -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Top Customers by Revenue</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Customer</th>
                <th>Business</th>
                <th>Email</th>
                <th class="text-right">Total Paid</th>
              </tr>
            </thead>
            <tbody>
              @foreach($topCustomers as $index => $customer)
              <tr>
                <td><strong>#{{ $index + 1 }}</strong></td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->business_name }}</td>
                <td>{{ $customer->email }}</td>
                <td class="text-right"><strong>TSh {{ number_format($customer->total_paid ?? 0, 0) }}</strong></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/admin/plugins/chart.js') }}"></script>
<script>
  // Daily Revenue Chart
  var dailyCtx = document.getElementById('dailyRevenueChart').getContext('2d');
  var dailyChart = new Chart(dailyCtx, {
    type: 'line',
    data: {
      labels: {!! json_encode(array_column($dailyRevenueData, 'date')) !!},
      datasets: [{
        label: 'Revenue (TSh)',
        data: {!! json_encode(array_column($dailyRevenueData, 'revenue')) !!},
        borderColor: '#940000',
        backgroundColor: 'rgba(148, 0, 0, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return 'TSh ' + value.toLocaleString();
            }
          }
        }
      }
    }
  });

  // Monthly Revenue Chart
  var monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
  var monthlyChart = new Chart(monthlyCtx, {
    type: 'bar',
    data: {
      labels: {!! json_encode(array_column($monthlyRevenueChart, 'month')) !!},
      datasets: [{
        label: 'Revenue (TSh)',
        data: {!! json_encode(array_column($monthlyRevenueChart, 'revenue')) !!},
        backgroundColor: '#06a3da',
        borderColor: '#06a3da',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return 'TSh ' + value.toLocaleString();
            }
          }
        }
      }
    }
  });

  // User Growth Chart
  var userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
  var userGrowthChart = new Chart(userGrowthCtx, {
    type: 'line',
    data: {
      labels: {!! json_encode(array_column($userGrowthChart, 'month')) !!},
      datasets: [{
        label: 'New Users',
        data: {!! json_encode(array_column($userGrowthChart, 'users')) !!},
        borderColor: '#940000',
        backgroundColor: 'rgba(148, 0, 0, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  });
</script>
@endsection












