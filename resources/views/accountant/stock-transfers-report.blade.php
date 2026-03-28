@extends('layouts.dashboard')

@section('title', 'Counter Intakes Tracker')

@push('styles')
<style>
    :root {
        --mauzo-gold: #940000;
        --mauzo-dark: #2c3e50;
    }
    .report-card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.2s;
    }
    .report-card:hover { transform: translateY(-3px); }
    .report-card .icon {
        width: 50px;
        height: 50px;
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .batch-header {
        background: #f1f3f5 !important;
        border-left: 5px solid var(--mauzo-gold);
    }
    .progress-sm { height: 6px; border-radius: 10px; background: #e9ecef; }
    .strategy-badge {
        font-size: 0.65rem;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 700;
    }
    .bt-strategy { background: #e3f2fd; color: #1976d2; }
    .gl-strategy { background: #f3e5f5; color: #7b1fa2; }
    .btn-mauzo {
        background-color: var(--mauzo-gold);
        color: white;
        border-radius: 8px;
        border: none;
        box-shadow: 0 4px 10px rgba(148,0,0,0.2);
    }
    .btn-mauzo:hover { background-color: #7a0000; color: white; }
</style>
@endpush

@section('content')
<div class="app-title">
    <div>
        <h1><i class="fa fa-truck"></i> Counter Intakes Analytics</h1>
        <p>Real-time physical inventory velocity and revenue conversion</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Reports</li>
        <li class="breadcrumb-item active">Counter Intakes</li>
    </ul>
</div>

<!-- Financial Pulse Summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="report-card tile bg-primary text-white mb-0" style="background: linear-gradient(135deg, #2c3e50 0%, #000000 100%); min-height: 110px;">
            <div class="d-flex align-items-center">
                <div class="icon mr-3"><i class="fa fa-line-chart"></i></div>
                <div>
                    <h6 class="text-white-50 mb-0 small">Expected Total</h6>
                    <h4 class="font-weight-bold mb-0">TSh {{ number_format($totals['expected_revenue']) }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="report-card tile bg-warning text-white mb-0" style="background: linear-gradient(135deg, #f39c12 0%, #d35400 100%); min-height: 110px;">
            <div class="d-flex align-items-center">
                <div class="icon mr-3"><i class="fa fa-clock-o"></i></div>
                <div>
                    <h6 class="text-white-50 mb-0 small">Real-time Revenue</h6>
                    <h4 class="font-weight-bold mb-0">TSh {{ number_format($totals['real_time_revenue']) }}</h4>
                    @if($totals['expected_revenue'] > 0)
                        <small class="text-white-50">{{ number_format(($totals['real_time_revenue'] / $totals['expected_revenue']) * 100, 1) }}% achieved</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="report-card tile bg-success text-white mb-0" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); min-height: 110px;">
            <div class="d-flex align-items-center">
                <div class="icon mr-3"><i class="fa fa-check-circle"></i></div>
                <div>
                    <h6 class="text-white-50 mb-0 small">Real-time Profit</h6>
                    <h4 class="font-weight-bold mb-0">TSh {{ number_format($totals['real_time_profit']) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="tile report-card p-3">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4">
                    <label class="font-weight-bold small text-uppercase">Start Point</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" style="border-radius: 8px;">
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold small text-uppercase">End Point</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" style="border-radius: 8px;">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-mauzo btn-block py-2"><i class="fa fa-bolt mr-2"></i> Refresh Tracking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile report-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="pl-4">Inventory Profile</th>
                            <th class="text-center">Initial Stock</th>
                            <th class="text-center">Sold Units</th>
                            <th class="text-center">In Counter</th>
                            <th class="text-right">Strategy Revenue</th>
                            <th class="text-right">Actual Revenue</th>
                            <th class="text-center" style="width: 150px;">Depletion</th>
                            <th class="text-right pr-4">Net Profit (MTD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastBatchNum = null; @endphp
                        @forelse($transfers as $transfer)
                            @if($lastBatchNum !== $transfer->transfer_number)
                                <tr class="batch-header">
                                    <td colspan="8" class="py-3 pl-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge badge-dark p-2" style="border-radius: 6px;">
                                                    <i class="fa fa-code-fork mr-2 text-info"></i> BATCH: {{ $transfer->transfer_number }}
                                                </span>
                                                <span class="ml-3 small text-muted font-weight-bold">{{ $transfer->created_at->format('M d, Y') }}</span>
                                            </div>
                                            <div class="pr-3">
                                                @switch($transfer->status)
                                                    @case('completed') <span class="badge badge-pill badge-success px-3">COMPLETED</span> @break
                                                    @case('reconciled') <span class="badge badge-pill badge-dark px-3">RECONCILED</span> @break
                                                    @default <span class="badge badge-pill badge-warning px-3">{{ strtoupper($transfer->status) }}</span>
                                                @endswitch
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @php $lastBatchNum = $transfer->transfer_number; @endphp
                            @endif
                            <tr class="align-middle">
                                <td class="pl-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="text-muted mr-3"><i class="fa fa-level-up fa-rotate-90"></i></div>
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 1rem;">{{ $transfer->productVariant->name ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $transfer->productVariant->product->category ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center font-weight-bold text-dark">{{ $transfer->productVariant->formatUnits($transfer->total_units) }}</td>
                                <td class="text-center">
                                    @if($transfer->productVariant->can_sell_in_tots)
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            <span class="strategy-badge bt-strategy mr-1" title="Bottles Sold">{{ number_format($transfer->real_time_unit_sales) }} Bt</span>
                                            <span class="strategy-badge gl-strategy" title="Glasses Sold">{{ number_format($transfer->real_time_portion_sales) }} Gl</span>
                                        </div>
                                    @else
                                        <span class="badge badge-light border">{{ number_format($transfer->real_time_units_sold) }} btl(s)</span>
                                    @endif
                                </td>
                                <td class="text-center font-weight-bold text-danger">
                                    @php $remaining = $transfer->total_units - ($transfer->real_time_units_sold ?? 0); @endphp
                                    {{ $transfer->productVariant->formatUnits($remaining) }}
                                </td>
                                <td class="text-right">
                                    @if($transfer->productVariant->can_sell_in_tots)
                                        <div class="small">Bt: TSh {{ number_format($transfer->expected_revenue_bottle) }}</div>
                                        <div class="small text-info font-weight-bold">Gl: TSh {{ number_format($transfer->expected_revenue_glass) }}</div>
                                    @else
                                        <div class="font-weight-bold">TSh {{ number_format($transfer->expected_revenue_bottle) }}</div>
                                    @endif
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    TSh {{ number_format($transfer->real_time_revenue) }}
                                    @if(isset($expRev) && $expRev > 0 && $transfer->real_time_revenue > $expRev)
                                        <span class="badge badge-pill badge-warning border shadow-sm ml-1" style="font-size: 0.6rem; vertical-align: top;">
                                            <i class="fa fa-rocket text-dark"></i> <span class="text-dark">GAIN</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center vert-align">
                                    @php 
                                        $expRev = ($transfer->productVariant->can_sell_in_tots ?? false) ? $transfer->expected_revenue_glass : $transfer->expected_revenue_bottle;
                                        $percent = ($expRev > 0) ? ($transfer->real_time_revenue / $expRev) * 100 : (($transfer->total_units > 0) ? ($transfer->real_time_units_sold / $transfer->total_units) * 100 : 0);
                                    @endphp
                                    <div class="small font-weight-bold mb-1 {{ $percent >= 100 ? 'text-success' : 'text-primary' }}">{{ number_format($percent, 0) }}%</div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar {{ $percent >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ min(100, $percent) }}%"></div>
                                    </div>
                                </td>
                                <td class="text-right pr-4 font-weight-bold {{ $transfer->real_time_profit < 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1rem;">
                                    TSh {{ number_format($transfer->real_time_profit) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted italic">No inventory intakes recorded for this window.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

