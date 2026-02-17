@extends('layouts.dashboard')

@section('title', 'Marketing Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-bullhorn"></i> Marketing Dashboard</h1>
    <p>Manage SMS campaigns and customer communications</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Marketing</li>
  </ul>
</div>

<!-- Statistics Cards -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-users fa-3x"></i>
      <div class="info">
        <h4>Total Customers</h4>
        <p><b>{{ number_format($totalCustomers) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-user-circle fa-3x"></i>
      <div class="info">
        <h4>Active Customers</h4>
        <p><b>{{ number_format($activeCustomers) }}</b></p>
        <small class="text-muted">Last 30 days</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-star fa-3x"></i>
      <div class="info">
        <h4>VIP Customers</h4>
        <p><b>{{ number_format($vipCustomers) }}</b></p>
        <small class="text-muted">Top 20%</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-envelope fa-3x"></i>
      <div class="info">
        <h4>SMS Sent</h4>
        <p><b>{{ number_format($totalSmsSent) }}</b></p>
        <small class="text-muted">All time</small>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-paper-plane fa-3x"></i>
      <div class="info">
        <h4>Campaigns</h4>
        <p><b>{{ $totalCampaigns }}</b></p>
        <small class="text-muted">{{ $todayCampaigns }} today</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Cost</h4>
        <p><b>TSh {{ number_format($totalCost, 2) }}</b></p>
        <small class="text-muted">TSh {{ number_format($thisMonthCost, 2) }} this month</small>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-3 mb-3">
            <a href="{{ route('marketing.campaigns.create') }}" class="btn btn-primary btn-block btn-lg">
              <i class="fa fa-plus-circle"></i><br>
              Create Campaign
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('marketing.customers') }}" class="btn btn-info btn-block btn-lg">
              <i class="fa fa-users"></i><br>
              Customer Database
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('marketing.campaigns') }}" class="btn btn-success btn-block btn-lg">
              <i class="fa fa-list"></i><br>
              Campaign History
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('marketing.templates') }}" class="btn btn-warning btn-block btn-lg">
              <i class="fa fa-file-text"></i><br>
              Templates
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Charts Row -->
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Customer Growth</h3>
      <div class="tile-body">
        <canvas id="customerGrowthChart" height="200"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Campaign Performance</h3>
      <div class="tile-body">
        <canvas id="campaignPerformanceChart" height="200"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Recent Campaigns -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Recent Campaigns</h3>
      <div class="tile-body">
        @if($recentCampaigns->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Campaign Name</th>
                  <th>Status</th>
                  <th>Recipients</th>
                  <th>Sent</th>
                  <th>Failed</th>
                  <th>Success Rate</th>
                  <th>Cost</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recentCampaigns as $campaign)
                  <tr>
                    <td><strong>{{ $campaign->name }}</strong></td>
                    <td>
                      <span class="badge badge-{{ $campaign->status === 'completed' ? 'success' : ($campaign->status === 'sending' ? 'warning' : ($campaign->status === 'scheduled' ? 'info' : 'secondary')) }}">
                        {{ ucfirst($campaign->status) }}
                      </span>
                    </td>
                    <td>{{ number_format($campaign->total_recipients) }}</td>
                    <td>{{ number_format($campaign->sent_count) }}</td>
                    <td>{{ number_format($campaign->failed_count) }}</td>
                    <td>
                      @if($campaign->total_recipients > 0)
                        {{ number_format(($campaign->success_count / $campaign->total_recipients) * 100, 1) }}%
                      @else
                        0%
                      @endif
                    </td>
                    <td>TSh {{ number_format($campaign->actual_cost, 2) }}</td>
                    <td>{{ $campaign->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      <a href="{{ route('marketing.campaigns.show', $campaign->id) }}" class="btn btn-sm btn-info">
                        <i class="fa fa-eye"></i> View
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">No campaigns yet. <a href="{{ route('marketing.campaigns.create') }}">Create your first campaign</a></p>
        @endif
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Customer Growth Chart
  const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
  new Chart(customerGrowthCtx, {
    type: 'line',
    data: {
      labels: {!! json_encode(array_column($customerGrowth, 'month')) !!},
      datasets: [{
        label: 'New Customers',
        data: {!! json_encode(array_column($customerGrowth, 'count')) !!},
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  // Campaign Performance Chart
  const campaignPerformanceCtx = document.getElementById('campaignPerformanceChart').getContext('2d');
  new Chart(campaignPerformanceCtx, {
    type: 'bar',
    data: {
      labels: {!! json_encode(array_column($campaignPerformance, 'month')) !!},
      datasets: [
        {
          label: 'Sent',
          data: {!! json_encode(array_column($campaignPerformance, 'sent')) !!},
          backgroundColor: 'rgba(54, 162, 235, 0.6)'
        },
        {
          label: 'Failed',
          data: {!! json_encode(array_column($campaignPerformance, 'failed')) !!},
          backgroundColor: 'rgba(255, 99, 132, 0.6)'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
</script>
@endpush
@endsection







