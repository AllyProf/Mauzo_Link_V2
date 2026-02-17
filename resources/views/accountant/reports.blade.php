@extends('layouts.dashboard')

@section('title', 'Financial Reports')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-line-chart"></i> Financial Reports</h1>
    <p>Detailed financial analysis and reports</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accountant.dashboard') }}">Accountant</a></li>
    <li class="breadcrumb-item">Reports</li>
  </ul>
</div>

<!-- Date Range Selector -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('accountant.reports') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="start_date" class="mr-2">Start Date:</label>
          <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="end_date" class="mr-2">End Date:</label>
          <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> Generate Report
        </button>
        <a href="{{ route('accountant.reports.pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-success ml-2" target="_blank">
          <i class="fa fa-file-pdf-o"></i> Download PDF
        </a>
      </form>
    </div>
  </div>
</div>

<!-- Revenue by Day -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Daily Revenue Report ({{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }})</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Revenue</th>
                <th>Cash</th>
                <th>Mobile Money</th>
                <th>Orders Count</th>
              </tr>
            </thead>
            <tbody>
              @foreach($revenueByDay as $day)
              <tr>
                <td>{{ $day['date_formatted'] }}</td>
                <td><strong>TSh {{ number_format($day['revenue'], 0) }}</strong></td>
                <td>TSh {{ number_format($day['cash'], 0) }}</td>
                <td>TSh {{ number_format($day['mobile_money'], 0) }}</td>
                <td>{{ $day['orders_count'] }}</td>
              </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr>
                <th>Total</th>
                <th>TSh {{ number_format(collect($revenueByDay)->sum('revenue'), 0) }}</th>
                <th>TSh {{ number_format(collect($revenueByDay)->sum('cash'), 0) }}</th>
                <th>TSh {{ number_format(collect($revenueByDay)->sum('mobile_money'), 0) }}</th>
                <th>{{ collect($revenueByDay)->sum('orders_count') }}</th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Revenue by Waiter -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Revenue by Waiter</h3>
      <div class="tile-body">
        @if($revenueByWaiter->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Waiter</th>
                  <th>Total Revenue</th>
                  <th>Bar Sales</th>
                  <th>Food Sales</th>
                  <th>Orders Count</th>
                </tr>
              </thead>
              <tbody>
                @foreach($revenueByWaiter as $waiterData)
                <tr>
                  <td>
                    <strong>{{ $waiterData['waiter']->full_name }}</strong><br>
                    <small class="text-muted">{{ $waiterData['waiter']->email }}</small>
                  </td>
                  <td><strong>TSh {{ number_format($waiterData['total_revenue'], 0) }}</strong></td>
                  <td>TSh {{ number_format($waiterData['bar_sales'], 0) }}</td>
                  <td>TSh {{ number_format($waiterData['food_sales'], 0) }}</td>
                  <td>{{ $waiterData['orders_count'] }}</td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th>Total</th>
                  <th>TSh {{ number_format($revenueByWaiter->sum('total_revenue'), 0) }}</th>
                  <th>TSh {{ number_format($revenueByWaiter->sum('bar_sales'), 0) }}</th>
                  <th>TSh {{ number_format($revenueByWaiter->sum('food_sales'), 0) }}</th>
                  <th>{{ $revenueByWaiter->sum('orders_count') }}</th>
                </tr>
              </tfoot>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No revenue data found for the selected period.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

