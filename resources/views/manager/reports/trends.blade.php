@extends('layouts.dashboard')

@section('title', 'Business Trends')

@section('content')
<div class="app-title">
    <div>
        <h1><i class="fa fa-line-chart"></i> Business Trends & Profitability</h1>
        <p>Analyze high-level performance trends and profit margins</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Reports</li>
        <li class="breadcrumb-item">Trends</li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="tile-title mb-0">Strategic Performance Window</h3>
                <div class="btn-group">
                    <a href="?range=7" class="btn btn-sm btn-outline-info {{ $range == '7' ? 'active' : '' }}">Last 7 Days</a>
                    <a href="?range=30" class="btn btn-sm btn-outline-info {{ $range == '30' || empty($range) ? 'active' : '' }}">Last 30 Days</a>
                    <a href="?range=90" class="btn btn-sm btn-outline-info {{ $range == '90' ? 'active' : '' }}">Last 90 Days</a>
                    <a href="?range=year" class="btn btn-sm btn-outline-info {{ $range == 'year' ? 'active' : '' }}">Year to Date</a>
                </div>
            </div>

            <!-- Standard Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="widget-small info coloured-icon"><i class="icon fa fa-money fa-3x"></i>
                        <div class="info">
                            <h4>Total Revenue</h4>
                            <p><b>TSh {{ number_format($totals['revenue']) }}</b></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="widget-small success coloured-icon"><i class="icon fa fa-trophy fa-3x"></i>
                        <div class="info text-dark">
                            <h4 class="text-dark">Total profit</h4>
                            <p><b class="text-dark">TSh {{ number_format($totals['profit']) }}</b></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="widget-small danger coloured-icon"><i class="icon fa fa-shopping-cart fa-3x"></i>
                        <div class="info">
                            <h4>Buying Cost (COGS)</h4>
                            <p><b>TSh {{ number_format($totals['cogs'] ?? 0) }}</b></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="widget-small warning coloured-icon"><i class="icon fa fa-minus-circle fa-3x"></i>
                        <div class="info">
                            <h4>Operational Exp.</h4>
                            <p><b>TSh {{ number_format($totals['expense']) }}</b></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8 border-right">
                    <h5 class="mb-4 font-weight-bold text-dark"><i class="fa fa-area-chart text-info mr-2"></i> Performance Intensity (Daily)</h5>
                    <div style="position: relative; height: 350px;">
                        <canvas id="mainTrendChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3 font-weight-bold text-dark"><i class="fa fa-pie-chart text-warning mr-2"></i> Money Circulation Audit</h5>
                    <p class="text-muted small">Visual distribution of how every shilling of revenue is allocated.</p>
                    <div style="position: relative; height: 250px;">
                        <canvas id="circulationChart"></canvas>
                    </div>
                    <div class="mt-4 px-2">
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted small">Invested in Stock (COGS):</span>
                            <span class="font-weight-bold tiny">TSh {{ number_format($totals['cogs'] ?? 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted small">Consumed by Expenses:</span>
                            <span class="font-weight-bold tiny text-danger">TSh {{ number_format($totals['expense']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between pt-1">
                            <span class="text-muted small font-weight-bold">Net Profit Obtained:</span>
                            <span class="font-weight-bold text-success">TSh {{ number_format($totals['profit']) }}</span>
                        </div>
                        <div class="alert alert-light border mt-3 py-2 px-3 small italic" style="font-size: 0.75rem;">
                            <i class="fa fa-info-circle mr-1"></i> <strong>Note:</strong> Total Revenue (100%) = Cost (Inventory) + Expenses (Operational) + Net Profit.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Standard Table Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title mb-4">Historical Financial Comparison</h3>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="py-3">Month</th>
                            <th class="text-right py-3">Gross Revenue</th>
                            <th class="text-right py-3">Net Profit Obtained</th>
                            <th class="text-center py-3">Gross Margin (%)</th>
                            <th class="text-center py-3">Performance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyComparison as $stat)
                        @php 
                            $monthRevenue = floatval($stat->revenue);
                            $monthProfit = floatval($stat->profit);
                            $monthMargin = $monthRevenue > 0 ? ($monthProfit / $monthRevenue) * 100 : 0;
                        @endphp
                        <tr>
                            <td class="align-middle"><span class="font-weight-bold">{{ \Carbon\Carbon::parse($stat->month)->format('F Y') }}</span></td>
                            <td class="text-right align-middle font-weight-bold">TSh {{ number_format($monthRevenue) }}</td>
                            <td class="text-right align-middle font-weight-bold text-success">TSh {{ number_format($monthProfit) }}</td>
                            <td class="text-center align-middle font-weight-bold text-primary">
                                {{ number_format($monthMargin, 1) }}%
                            </td>
                            <td class="text-center align-middle">
                                @if($monthMargin > 20)
                                    <span class="badge badge-success px-3 py-2"><i class="fa fa-level-up mr-2"></i> HIGH PERFORMER</span>
                                @elseif($monthMargin > 10)
                                    <span class="badge badge-info px-3 py-2"><i class="fa fa-arrows-h mr-2"></i> STEADY</span>
                                @else
                                    <span class="badge badge-warning px-3 py-2"><i class="fa fa-level-down mr-2"></i> LOW MARGIN</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Awaiting full month reports for trend comparison.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
    (function() {
        // Main Trend Chart
        const lineCtx = document.getElementById("mainTrendChart");
        if (lineCtx) {
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['labels']) !!},
                    datasets: [
                        {
                            label: "Money Circulation (Revenue)",
                            borderColor: "rgba(30, 136, 229, 1)",
                            backgroundColor: "rgba(30, 136, 229, 0.1)",
                            data: {!! json_encode($chartData['revenue']) !!},
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: "rgba(30, 136, 229, 1)"
                        },
                        {
                            label: "Profit Obtained",
                            borderColor: "rgba(76, 175, 80, 1)",
                            backgroundColor: "rgba(76, 175, 80, 0.1)",
                            data: {!! json_encode($chartData['profit']) !!},
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: "rgba(76, 175, 80, 1)"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, font: { size: 12 } } },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
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
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: function(value) {
                                    return 'TSh ' + (value >= 1000 ? (value/1000).toFixed(0)+'k' : value);
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Circulation Chart
        const pieCtx = document.getElementById("circulationChart");
        if (pieCtx) {
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ["Net Profit", "Stock Investment (COGS)", "Operational Expenses"],
                    datasets: [{
                        backgroundColor: [
                            "rgba(76, 175, 80, 0.85)",  // Green for Profit
                            "rgba(30, 136, 229, 0.85)", // blue for COGS
                            "rgba(255, 82, 82, 0.85)"   // Red for Expenses
                        ],
                        hoverOffset: 15,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        data: [
                            {!! $circulationData['profit'] !!}, 
                            {!! $circulationData['cogs'] !!}, 
                            {!! $circulationData['expense'] !!}
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { size: 10 } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return ' TSh ' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    })();
</script>
@endpush
