@extends('layouts.dashboard')

@section('title', 'Stock Receipts Report')

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
        overflow: hidden;
    }
    .report-card:hover { transform: translateY(-3px); }
    .report-card .icon {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .batch-row {
        background: #f8f9fa !important;
        border-left: 5px solid var(--mauzo-gold);
    }
    .table thead th {
        background: #f8f9fa;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #dee2e6;
    }
    .product-name { color: var(--mauzo-gold); font-weight: 700; font-size: 1.05rem; }
    .glass-pill {
        background: rgba(52, 152, 219, 0.1);
        color: #2980b9;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 10px;
        border: 1px solid rgba(52, 152, 219, 0.2);
    }
    .profit-text { font-weight: 800; }
    .btn-mauzo {
        background-color: var(--mauzo-gold);
        color: white;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 600;
        border: none;
        box-shadow: 0 4px 10px rgba(148,0,0,0.2);
    }
    .btn-mauzo:hover { background-color: #7a0000; color: white; }
</style>
@endpush

@section('content')
<div class="app-title">
    <div>
        <h1><i class="fa fa-th-list"></i> Stock Receipts Audit</h1>
        <p>Deep dive into inventory acquisition and profit potential</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Reports</li>
        <li class="breadcrumb-item active">Stock Receipts</li>
    </ul>
</div>

<!-- Summary Section -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="report-card tile bg-mauzo text-white mb-0" style="background: linear-gradient(135deg, #940000 0%, #600000 100%);">
            <div class="d-flex align-items-center">
                <div class="icon mr-3"><i class="fa fa-shopping-cart"></i></div>
                <div>
                    <h6 class="text-white-50 mb-0">Total Items In</h6>
                    <h3 class="font-weight-bold mb-0">{{ number_format($groupSummary->total_items ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="report-card tile bg-info text-white mb-0" style="background: linear-gradient(135deg, #17a2b8 0%, #0d6efd 100%);">
            <div class="d-flex align-items-center">
                <div class="icon mr-3"><i class="fa fa-truck"></i></div>
                <div>
                    <h6 class="text-white-50 mb-0">Total Batches</h6>
                    <h3 class="font-weight-bold mb-0">{{ number_format($groupSummary->unique_batches ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="report-card tile bg-success text-white mb-0" style="background: linear-gradient(135deg, #28a745 0%, #198754 100%);">
            <div class="d-flex align-items-center">
                <div class="icon mr-3"><i class="fa fa-money"></i></div>
                <div>
                    <h6 class="text-white-50 mb-0">Total Investment</h6>
                    <h3 class="font-weight-bold mb-0">TSh {{ number_format($groupSummary->total_buying_cost ?? 0) }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtering Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="tile p-3 report-card">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label class="font-weight-bold small text-uppercase">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" style="border-radius: 8px;">
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold small text-uppercase">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" style="border-radius: 8px;">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-mauzo btn-block"><i class="fa fa-filter mr-2"></i> Update Report</button>
                </div>
                <div class="col-md-3 text-right">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary" style="border-radius: 8px;"><i class="fa fa-print"></i> Export</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile report-card p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="pl-4">Product Identity</th>
                            <th class="text-center">Volume (Pkgs/Unt)</th>
                            <th class="text-right">Unit Buy Price</th>
                            <th class="text-right">Market Sell Price</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Net Investment</th>
                            <th class="text-right pr-4">Profit Potential</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastBatch = null; @endphp
                        @forelse($receipts as $receipt)
                            @if($lastBatch !== $receipt->receipt_number)
                                <tr class="batch-row">
                                    <td colspan="7" class="py-3 pl-4">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge badge-dark px-3 py-2" style="border-radius: 8px; letter-spacing: 1px;">
                                                    <i class="fa fa-barcode mr-2 text-warning"></i> BATCH: {{ $receipt->receipt_number }}
                                                </span>
                                            </div>
                                            <div class="small">
                                                <span class="mr-4"><i class="fa fa-calendar-check-o text-mauzo mr-1"></i> Received: <strong>{{ \Carbon\Carbon::parse($receipt->received_date)->format('M d, Y') }}</strong></span>
                                                <span><i class="fa fa-building-o text-mauzo mr-1"></i> Supplier: <strong>{{ $receipt->supplier->company_name ?? 'Local Vendor' }}</strong></span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @php $lastBatch = $receipt->receipt_number; @endphp
                            @endif
                            <tr>
                                <td class="pl-4 vert-align">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3 text-muted"><i class="fa fa-level-up fa-rotate-90"></i></div>
                                        <div>
                                            <div class="product-name">{{ $receipt->productVariant->name ?? 'Unknown Item' }}</div>
                                            <small class="text-muted text-uppercase" style="font-size: 0.7rem;">{{ $receipt->productVariant->product->category ?? '' }} Collection</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center vert-align">
                                    <span class="font-weight-bold text-dark">{{ $receipt->quantity_received }}</span> 
                                    <small class="text-muted">Pkgs</small><br>
                                    <span class="badge badge-light border">{{ number_format($receipt->total_units) }} Units</span>
                                </td>
                                <td class="text-right vert-align text-muted font-weight-bold">TSh {{ number_format($receipt->buying_price_per_unit) }}</td>
                                <td class="text-right vert-align">
                                    <div class="font-weight-bold text-dark">TSh {{ number_format($receipt->selling_price_per_unit) }}</div>
                                    @if($receipt->productVariant->can_sell_in_tots ?? false)
                                        <div class="glass-pill mt-1">
                                            <i class="fa fa-glass"></i> TSh {{ number_format($receipt->productVariant->selling_price_per_tot) }} / Glass
                                        </div>
                                    @endif
                                </td>
                                <td class="text-right vert-align">
                                    @if($receipt->discount_amount > 0)
                                        <span class="badge badge-pill badge-info px-2">
                                            {{ $receipt->discount_type == 'percent' ? $receipt->discount_amount.'%' : 'TSh '.number_format($receipt->discount_amount) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-right vert-align font-weight-bold text-danger" style="font-size: 1.1rem;">
                                    TSh {{ number_format($receipt->final_buying_cost) }}
                                </td>
                                <td class="text-right vert-align pr-4">
                                    <div class="profit-text text-success" style="font-size: 1.1rem;">TSh {{ number_format($receipt->total_profit) }}</div>
                                    @if($receipt->productVariant->can_sell_in_tots ?? false)
                                        @php 
                                           $glassRev = $receipt->total_units * ($receipt->productVariant->total_tots ?? 0) * ($receipt->productVariant->selling_price_per_tot ?? 0);
                                           $glassProfit = $glassRev - $receipt->final_buying_cost;
                                        @endphp
                                        <div class="mt-1" style="font-size: 0.7rem; font-style: italic;">
                                            <span class="text-primary font-weight-bold">
                                                [Gl. Strategy: TSh {{ number_format($glassProfit) }}]
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa fa-inbox fa-4x mb-3 opacity-25"></i>
                                        <h5>No stock acquisitions found</h5>
                                        <p>Try adjusting your date range filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($receipts->hasPages())
                <div class="p-4 border-top d-flex justify-content-center">
                    {!! $receipts->appends(request()->query())->links() !!}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .vert-align { vertical-align: middle !important; }
    .bg-mauzo { background-color: var(--mauzo-gold); }
</style>
@endsection

