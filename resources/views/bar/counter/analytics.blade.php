@extends('layouts.dashboard')

@section('title', 'Analytics & Trends')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-line-chart"></i> Analytics & Trends</h1>
    <p>Product trends and expected revenue</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Counter Dashboard</a></li>
    <li class="breadcrumb-item">Analytics</li>
  </ul>
</div>

<!-- Revenue Summary -->
<div class="row">
  <div class="col-md-4">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Expected Revenue</h4>
        <p><b>TSh {{ number_format($expectedRevenue, 2) }}</b></p>
        <small>From current counter stock</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-shopping-cart fa-3x"></i>
      <div class="info">
        <h4>Total Orders (30 days)</h4>
        <p><b>{{ $salesData->sum('orders') }}</b></p>
        <small>Last 30 days</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-trophy fa-3x"></i>
      <div class="info">
        <h4>Total Revenue (30 days)</h4>
        <p><b>TSh {{ number_format($salesData->sum('revenue'), 2) }}</b></p>
        <small>Last 30 days</small>
      </div>
    </div>
  </div>
</div>

<!-- Sales Chart -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Sales Trend (Last 30 Days)</h3>
      <div class="tile-body">
        <canvas id="salesChart" height="100"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Top Products -->
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Top Selling Products (Last 30 Days)</h3>
      <div class="tile-body">
        @if($topProducts->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity Sold</th>
                  <th>Revenue</th>
                </tr>
              </thead>
              <tbody>
                @foreach($topProducts as $item)
                <tr>
                  <td>
                    <strong>{{ $item->productVariant->product->name }}</strong><br>
                    <small class="text-muted">{{ $item->productVariant->measurement }}</small>
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
          <p class="text-muted">No sales data available</p>
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

<!-- Expected Revenue from Counter Stock -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Expected Revenue from Counter Stock</h3>
      <div class="tile-body">
        @if(count($counterStock) > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity in Stock</th>
                  <th>Selling Price</th>
                  <th>Potential Revenue</th>
                </tr>
              </thead>
              <tbody>
                @foreach($counterStock->sortByDesc('potential_revenue')->take(20) as $item)
                <tr>
                  <td><strong>{{ $item['product_name'] }}</strong></td>
                  <td>{{ number_format($item['quantity']) }} units</td>
                  <td>TSh {{ number_format($item['selling_price'], 2) }}</td>
                  <td><strong class="text-success">TSh {{ number_format($item['potential_revenue'], 2) }}</strong></td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr class="table-info">
                  <th colspan="3" class="text-right">Total Expected Revenue:</th>
                  <th>TSh {{ number_format($expectedRevenue, 2) }}</th>
                </tr>
              </tfoot>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No counter stock available.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<a href="{{ route('bar.counter.dashboard') }}" class="btn btn-secondary">
  <i class="fa fa-arrow-left"></i> Back to Dashboard
</a>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
  // Sales Chart
  const salesData = @json($salesData);
  const ctx = document.getElementById('salesChart').getContext('2d');
  
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: salesData.map(item => item.date),
      datasets: [{
        label: 'Revenue (TSh)',
        data: salesData.map(item => parseFloat(item.revenue)),
        borderColor: '#940000',
        backgroundColor: 'rgba(148, 0, 0, 0.1)',
        tension: 0.4
      }, {
        label: 'Orders',
        data: salesData.map(item => item.orders),
        borderColor: '#06a3da',
        backgroundColor: 'rgba(6, 163, 218, 0.1)',
        tension: 0.4,
        yAxisID: 'y1'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      scales: {
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Revenue (TSh)'
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: {
            display: true,
            text: 'Number of Orders'
          },
          grid: {
            drawOnChartArea: false
          }
        }
      }
    }
  });
</script>
@endpush








