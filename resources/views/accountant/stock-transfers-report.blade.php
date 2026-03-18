@extends('layouts.dashboard')

@section('title', 'Stock Transfers Report')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> Stock Transfers & Real-time Tracking</h1>
    <p>Inventory distribution and revenue generation analysis</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Stock Transfers</li>
  </ul>
</div>

<!-- Financial Summary -->
<div class="row">
    <div class="col-md-3">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-line-chart fa-3x"></i>
            <div class="info">
                <h4>Expected Revenue</h4>
                <p><b>TSh {{ number_format($totals['expected_revenue']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-money fa-3x"></i>
            <div class="info">
                <h4>Expected Profit</h4>
                <p><b>TSh {{ number_format($totals['expected_profit']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small warning coloured-icon"><i class="icon fa fa-clock-o fa-3x"></i>
            <div class="info">
                <h4>Real-time Revenue</h4>
                <p><b>TSh {{ number_format($totals['real_time_revenue']) }}</b></p>
                @if($totals['expected_revenue'] > 0)
                  <small class="text-white-50">{{ number_format(($totals['real_time_revenue'] / $totals['expected_revenue']) * 100, 1) }}% of target</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small success coloured-icon"><i class="icon fa fa-check-circle fa-3x"></i>
            <div class="info">
                <h4>Real-time Profit</h4>
                <p><b>TSh {{ number_format($totals['real_time_profit']) }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" class="row">
        <div class="col-md-4">
          <label>Start Date</label>
          <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
        </div>
        <div class="col-md-4">
          <label>End Date</label>
          <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Filter Report</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead class="thead-light">
              <tr>
                <th>Date</th>
                <th>Transfer #</th>
                <th>Product Variant</th>
                <th>Units</th>
                <th>Status</th>
                <th>Expected Rev.</th>
                <th class="bg-light">Real-time Rev.</th>
                <th>% Done</th>
                <th>Expected Profit</th>
                <th class="bg-light">Real-time Profit</th>
              </tr>
            </thead>
            <tbody>
              @php $lastTransferNumber = null; @endphp
              @forelse($transfers as $transfer)
                @if($lastTransferNumber !== $transfer->transfer_number)
                  <tr class="bg-light font-weight-bold" style="border-top: 2px solid #dee2e6;">
                    <td colspan="5">
                        <i class="fa fa-tag text-info mr-2"></i> BATCH: {{ $transfer->transfer_number }}
                        <span class="badge badge-secondary ml-2">{{ $transfer->created_at->format('M d, Y H:i') }}</span>
                    </td>
                    <td colspan="4" class="text-right">
                        <span class="small text-muted">Status: </span>
                        @switch($transfer->status)
                            @case('pending') <span class="badge badge-warning">Pending</span> @break
                            @case('approved') <span class="badge badge-primary">Approved</span> @break
                            @case('completed') <span class="badge badge-success">Completed</span> @break
                            @case('reconciled') <span class="badge badge-dark">Reconciled</span> @break
                            @default <span class="badge badge-secondary">{{ ucfirst($transfer->status) }}</span>
                        @endswitch
                    </td>
                  </tr>
                  @php $lastTransferNumber = $transfer->transfer_number; @endphp
                @endif
                <tr>
                  <td class="text-center text-muted small"><i class="fa fa-level-up fa-rotate-90"></i></td>
                  <td class="text-center text-muted small">-</td>
                  <td>
                      <strong>{{ $transfer->productVariant->name ?? 'N/A' }}</strong><br>
                      <small class="text-muted">{{ $transfer->productVariant->product->name ?? '' }}</small>
                  </td>
                  <td class="text-center">{{ $transfer->total_units }}</td>
                  <td class="text-center">
                    <i class="fa fa-check-circle-o text-success"></i>
                  </td>
                  <td class="font-weight-bold">TSh {{ number_format($transfer->expected_revenue) }}</td>
                  <td class="bg-light font-weight-bold text-primary">TSh {{ number_format($transfer->real_time_revenue) }}</td>
                  <td>
                      @php 
                          $percent = $transfer->expected_revenue > 0 ? ($transfer->real_time_revenue / $transfer->expected_revenue) * 100 : 0;
                      @endphp
                      <div class="text-center font-weight-bold small">{{ number_format($percent, 1) }}%</div>
                      <div class="progress" style="height: 5px;">
                          <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, $percent) }}%" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                  </td>
                  <td class="font-weight-bold">TSh {{ number_format($transfer->expected_profit) }}</td>
                  <td class="bg-light font-weight-bold text-success">TSh {{ number_format($transfer->real_time_profit) }}</td>
                </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No stock transfers found for the selected period.</td>
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
