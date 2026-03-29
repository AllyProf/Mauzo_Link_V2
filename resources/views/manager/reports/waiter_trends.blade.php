@extends('layouts.dashboard')

@section('title', 'Staff Analytics - Waiter Performance')

@section('content')
<div class="app-title">
    <div>
        <h1><i class="fa fa-line-chart"></i> Waiter Performance Audit</h1>
        <p>Strategic analysis of floor service productivity and revenue generation</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item">Reports</li>
        <li class="breadcrumb-item">Waiter Trends</li>
    </ul>
</div>

<!-- Performance Summary -->
@php
    $topWaiter = $waiterSales->first();
    $totalSales = $waiterSales->sum('total_sales');
    $totalOrders = $waiterSales->sum('order_count');
    $avgTicket = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
@endphp
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn border-bottom pb-3 mb-4">
                <h3 class="title"><i class="fa fa-chart-line mr-2"></i> Comparative Sales Velocity</h3>
                <div class="btn-group border rounded p-1 p-0">
                    <a href="?range=7" class="btn btn-sm btn-light {{ $range == '7' ? 'active font-weight-bold text-primary' : '' }}">Last 7 Days</a>
                    <a href="?range=30" class="btn btn-sm btn-light {{ $range == '30' ? 'active font-weight-bold text-primary' : '' }}">Last 30 Days</a>
                    <a href="?range=month" class="btn btn-sm btn-light {{ $range == 'month' ? 'active font-weight-bold text-primary' : '' }}">This Month</a>
                </div>
            </div>

            <div class="row">
                <!-- CHART SECTION -->
                <div class="col-md-8">
                    <div class="p-2">
                        <div class="d-flex justify-content-between align-items-end mb-4">
                            <div>
                                <h5 class="text-uppercase small font-weight-bold text-muted mb-0">Daily Revenue Contribution</h5>
                                <p class="mb-0 text-dark">Top 5 Performing Staff (Comparative Flow)</p>
                            </div>
                        </div>
                        <div style="position: relative; height: 420px;">
                            <canvas id="waiterTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- LEADERBOARD SECTION -->
                <div class="col-md-4 border-left">
                    <div class="p-2">
                        <h5 class="text-uppercase small font-weight-bold text-muted mb-4 text-center">Efficiency Leaderboard</h5>
                        
                        <div class="list-group list-group-flush">
                            @forelse($waiterSales as $index => $waiter)
                                @php 
                                    $perfPercent = ($topWaiter && $topWaiter->total_sales > 0) ? ($waiter->total_sales / $topWaiter->total_sales) * 100 : 0;
                                @endphp
                                <div class="list-group-item px-0 py-3 border-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3 text-center" style="width: 25px;">
                                                @if($index == 0) <i class="fa fa-trophy text-warning fa-lg"></i>
                                                @elseif($index == 1) <span class="bg-secondary text-white rounded-circle px-2 py-1 small">2</span>
                                                @elseif($index == 2) <span class="bg-danger text-white rounded-circle px-2 py-1 small">3</span>
                                                @else <span class="text-muted small">#{{ $index + 1 }}</span>
                                                @endif
                                            </div>
                                            <div>
                                                <h6 class="mb-0 font-weight-bold">{{ $waiter->full_name }}</h6>
                                                <small class="text-muted">{{ $waiter->order_count }} distinct orders</small>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-weight-bold text-primary">TSh {{ number_format($waiter->total_sales) }}</div>
                                            <small class="text-success">{{ number_format(($waiter->total_sales / max(1, $totalSales)) * 100, 1) }}% Share</small>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar {{ $index == 0 ? 'bg-success' : ($index < 3 ? 'bg-info' : 'bg-primary') }}" 
                                             role="progressbar" 
                                             style="width: {{ $perfPercent }}%;" 
                                             aria-valuenow="{{ $perfPercent }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <i class="fa fa-folder-open-o fa-3x text-light"></i>
                                    <p class="text-muted mt-2 small">No sales recorded for this period</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
    (function() {
        const ctx = document.getElementById('waiterTrendChart');
        if (!ctx) return;

        const colors = [
            { stroke: '#009688', fill: 'rgba(0, 150, 136, 0.05)' }, // Teal
            { stroke: '#2196f3', fill: 'rgba(33, 150, 243, 0.05)' }, // Blue
            { stroke: '#ff9800', fill: 'rgba(255, 152, 0, 0.05)' }, // Orange
            { stroke: '#9c27b0', fill: 'rgba(156, 39, 176, 0.05)' }, // Purple
            { stroke: '#e91e63', fill: 'rgba(233, 30, 99, 0.05)' }  // Pink
        ];

        const datasets = [];
        @php $idx = 0; @endphp
        @foreach($chartData['datasets'] as $name => $data)
            datasets.push({
                label: "{{ $name }}",
                data: {!! json_encode($data) !!},
                borderColor: colors[{{ $idx }} % colors.length].stroke,
                backgroundColor: colors[{{ $idx }} % colors.length].fill,
                borderWidth: 3,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: colors[{{ $idx }} % colors.length].stroke,
                fill: true
            });
            @php $idx++; @endphp
        @endforeach

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartData['labels']) !!},
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { usePointStyle: true, boxWidth: 8, font: { weight: 'bold', size: 12 } }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': TSh ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.03)' },
                        ticks: {
                            callback: function(value) {
                                return 'TSh ' + (value >= 1000 ? (value/1000).toFixed(0) + 'k' : value);
                            }
                        }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
</script>
<style>
    .tile-title-w-btn .btn-group .active {
        background-color: #f8f9fa !important;
        border-bottom: 2px solid #009688 !important;
        border-radius: 0;
    }
    .widget-small .info h4 { text-transform: uppercase; font-size: 11px; letter-spacing: 1px; color: #888; margin-bottom: 5px; }
    .widget-small .info p { font-size: 20px; color: #333; }
    .progress-bar { transition: width 1s ease-in-out; }
    #waiterTrendChart { filter: drop-shadow(0px 5px 15px rgba(0,0,0,0.02)); }
</style>
@endpush
@endsection
