@extends('layouts.dashboard')

@section('title', 'Counter Intakes Tracker')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-truck"></i> Counter Intakes Tracking</h1>
    <p>Analyze how effectively received stock generates revenue</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Counter Intakes Tracking</li>
  </ul>
</div>

<!-- Financial Summary -->
<div class="row">
    <div class="col-md-4">
        <div class="widget-small primary coloured-icon" style="min-height: 110px;"><i class="icon fa fa-line-chart fa-3x"></i>
            <div class="info">
                <h4>Expected Revenue</h4>
                <p><b>TSh {{ number_format($totals['expected_revenue']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small warning coloured-icon" style="min-height: 110px;"><i class="icon fa fa-clock-o fa-3x"></i>
            <div class="info">
                <h4>Real-time Revenue</h4>
                <p><b>TSh {{ number_format($totals['real_time_revenue']) }}</b></p>
                @if($totals['expected_revenue'] > 0)
                  <small class="text-white-50">{{ number_format(($totals['real_time_revenue'] / $totals['expected_revenue']) * 100, 1) }}% of target</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small success coloured-icon" style="min-height: 110px;"><i class="icon fa fa-check-circle fa-3x"></i>
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
                <th>Date / Batch</th>
                <th>Product Variant</th>
                <th class="text-center">Stock In</th>
                <th class="text-center">Sold</th>
                <th class="text-center">Remaining</th>
                <th class="bg-light">Exp. Revenue</th>
                <th class="bg-info text-white">Actual Revenue</th>
                <th class="bg-light">Exp. Profit</th>
                <th class="bg-success text-white">Actual Profit</th>
                <th class="text-center">% Progress</th>
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
                  <td>
                      <small class="text-muted">{{ $transfer->created_at->format('d M') }}</small><br>
                      <strong>#{{ $transfer->transfer_number }}</strong>
                  </td>
                  <td>
                      <strong>{{ $transfer->productVariant->name ?? 'N/A' }}</strong><br>
                      <small class="text-muted">{{ $transfer->productVariant->product->name ?? '' }}</small>
                  </td>
                  <td class="text-center">{{ $transfer->productVariant->formatUnits($transfer->total_units) }}</td>
                  <td class="text-center font-weight-bold">
                    @if($transfer->productVariant->can_sell_in_tots)
                      <span class="badge badge-light border">{{ number_format($transfer->real_time_unit_sales) }} Bt</span><br>
                      <span class="badge badge-info">{{ number_format($transfer->real_time_portion_sales) }} Gl</span>
                    @else
                      <div class="badge badge-light border">{{ number_format($transfer->real_time_units_sold) }} btls</div>
                    @endif
                  </td>
                  <td class="text-center text-danger font-weight-bold">
                      @php 
                        $remaining = $transfer->total_units - ($transfer->real_time_units_sold ?? 0); 
                      @endphp
                      {{ $transfer->productVariant->formatUnits($remaining) }}
                  </td>
                  <td class="bg-light">
                    @php 
                        $expRev = ($transfer->productVariant->can_sell_in_tots ?? false) ? $transfer->expected_revenue_glass : $transfer->expected_revenue_bottle;
                    @endphp
                    <strong>TSh {{ number_format($expRev) }}</strong><br>
                    <small class="text-muted">Target</small>
                  </td>
                  <td class="bg-info text-white font-weight-bold">
                    TSh {{ number_format($transfer->real_time_revenue) }}
                    @if(isset($expRev) && $expRev > 0 && $transfer->real_time_revenue > $expRev)
                        <span class="badge badge-pill badge-warning border shadow-sm ml-1" style="font-size: 0.6rem; vertical-align: middle;">
                          <i class="fa fa-rocket text-dark"></i> <span class="text-dark">GAIN</span>
                        </span>
                    @endif
                    <br>
                    <small class="text-white-50">Real-time</small>
                  </td>
                  <td class="bg-light">
                    <strong>TSh {{ number_format($transfer->expected_profit) }}</strong><br>
                    <small class="text-muted">Target</small>
                  </td>
                  <td class="bg-success text-white font-weight-bold">
                    TSh {{ number_format($transfer->real_time_profit) }}<br>
                    <small class="text-white-50">Real-time</small>
                  </td>
                  <td class="text-center">
                       @php 
                           $expRev = ($transfer->productVariant->can_sell_in_tots ?? false) ? $transfer->expected_revenue_glass : $transfer->expected_revenue_bottle;
                           $percent = ($expRev > 0) ? ($transfer->real_time_revenue / $expRev) * 100 : (($transfer->total_units > 0) ? ($transfer->real_time_units_sold / $transfer->total_units) * 100 : 0);
                       @endphp
                      <div class="font-weight-bold small">{{ number_format($percent, 1) }}%</div>
                      <div class="progress" style="height: 5px;">
                          <div class="progress-bar bg-{{ $percent >= 100 ? 'success' : ($percent > 80 ? 'info' : 'primary') }}" role="progressbar" style="width: {{ min(100, $percent) }}%"></div>
                      </div>
                  </td>
                </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No stock intakes found for the selected period.</td>
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
