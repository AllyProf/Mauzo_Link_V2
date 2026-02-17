@extends('layouts.dashboard')

@section('title', 'Restaurant Reports & Analytics')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-line-chart"></i> Restaurant Reports & Analytics</h1>
    <p>Food order trends and kitchen performance</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.chef.dashboard') }}">Chef Dashboard</a></li>
    <li class="breadcrumb-item">Restaurant Reports</li>
  </ul>
</div>

<!-- Revenue Summary -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Food Revenue (30 days)</h4>
        <p><b>TSh {{ number_format($totalFoodRevenue, 2) }}</b></p>
        <small>From food orders</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-shopping-cart fa-3x"></i>
      <div class="info">
        <h4>Total Food Orders</h4>
        <p><b>{{ $totalFoodOrders }}</b></p>
        <small>Last 30 days</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-clock-o fa-3x"></i>
      <div class="info">
        <h4>Avg Prep Time</h4>
        <p><b>{{ $avgPrepTimeMinutes }} min</b></p>
        <small>Average preparation time</small>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-check-circle fa-3x"></i>
      <div class="info">
        <h4>Completed Orders</h4>
        <p><b>{{ $kitchenStats['total_completed'] }}</b></p>
        <small>Last 30 days</small>
      </div>
    </div>
  </div>
</div>

<!-- Kitchen Status -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Kitchen Status</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-3">
            <div class="alert alert-danger">
              <h4><i class="fa fa-clock-o"></i> Pending</h4>
              <p class="mb-0"><strong>{{ $kitchenStats['total_pending'] }}</strong> items</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="alert alert-warning">
              <h4><i class="fa fa-fire"></i> Preparing</h4>
              <p class="mb-0"><strong>{{ $kitchenStats['total_preparing'] }}</strong> items</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="alert alert-info">
              <h4><i class="fa fa-check-circle"></i> Ready</h4>
              <p class="mb-0"><strong>{{ $kitchenStats['total_ready'] }}</strong> items</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="alert alert-success">
              <h4><i class="fa fa-trophy"></i> Completed</h4>
              <p class="mb-0"><strong>{{ $kitchenStats['total_completed'] }}</strong> items</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Sales Chart -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Food Sales Trend (Last 30 Days)</h3>
      <div class="tile-body">
        <canvas id="salesChart" height="100"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Top Food Items -->
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Top Selling Food Items (Last 30 Days)</h3>
      <div class="tile-body">
        @if($topFoodItems->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Food Item</th>
                  <th>Quantity Sold</th>
                  <th>Revenue</th>
                </tr>
              </thead>
              <tbody>
                @foreach($topFoodItems as $item)
                <tr>
                  <td>
                    <strong>{{ $item->food_item_name }}</strong>
                    @if($item->variant_name)
                      <br><small class="text-muted">{{ $item->variant_name }}</small>
                    @endif
                  </td>
                  <td>
                    <span class="badge badge-primary">{{ number_format($item->total_quantity) }}</span>
                  </td>
                  <td><strong>TSh {{ number_format($item->total_revenue, 2) }}</strong></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">No food sales data available</p>
        @endif
      </div>
    </div>
  </div>

  <!-- Revenue by Day of Week -->
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Revenue by Day of Week</h3>
      <div class="tile-body">
        @if($revenueByDay->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Day</th>
                  <th>Revenue</th>
                </tr>
              </thead>
              <tbody>
                @foreach($revenueByDay as $day)
                <tr>
                  <td><strong>{{ $day->day_name }}</strong></td>
                  <td><strong>TSh {{ number_format($day->revenue, 2) }}</strong></td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">No revenue data available</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
  // Sales Chart
  var salesData = @json($foodOrdersData);
  var ctx = document.getElementById('salesChart').getContext('2d');
  var salesChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: salesData.map(item => item.date),
      datasets: [{
        label: 'Revenue (TSh)',
        data: salesData.map(item => parseFloat(item.revenue)),
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1
      }, {
        label: 'Quantity Sold',
        data: salesData.map(item => parseInt(item.total_quantity)),
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.2)',
        yAxisID: 'y1',
        tension: 0.1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          position: 'left',
          ticks: {
            callback: function(value) {
              return 'TSh ' + value.toLocaleString();
            }
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          grid: {
            drawOnChartArea: false,
          },
          ticks: {
            beginAtZero: true
          }
        }
      },
      tooltips: {
        callbacks: {
          label: function(tooltipItem, data) {
            if (tooltipItem.datasetIndex === 0) {
              return 'Revenue: TSh ' + tooltipItem.yLabel.toLocaleString();
            } else {
              return 'Quantity: ' + tooltipItem.yLabel;
            }
          }
        }
      }
    }
  });
</script>
@endpush





