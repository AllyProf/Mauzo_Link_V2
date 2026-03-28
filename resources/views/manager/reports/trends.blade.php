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
                    <a href="?range=7" class="btn btn-sm btn-outline-info {{ $range == '7' || empty($range) ? 'active' : '' }}">Last 7 Days</a>
                    <a href="?range=30" class="btn btn-sm btn-outline-info {{ $range == '30' ? 'active' : '' }}">Last 30 Days</a>
                    <a href="?range=90" class="btn btn-sm btn-outline-info {{ $range == '90' ? 'active' : '' }}">Last 90 Days</a>
                    <a href="?range=year" class="btn btn-sm btn-outline-info {{ $range == 'year' ? 'active' : '' }}">Year to Date</a>
                </div>
            </div>




            <div class="row">
                {{-- LEFT: Profit Trend Chart --}}
                <div class="col-md-8 border-right">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">
                                <i class="fa fa-area-chart text-success mr-2"></i> Performance Intensity (Daily)
                            </h5>
                            <small class="text-muted">Net profit captured each day in the selected period</small>
                        </div>
                        <div class="text-right">
                            <div class="font-weight-bold text-success" style="font-size:1.15rem;">TSh {{ number_format($totals['profit']) }}</div>
                            <small class="text-muted">Period Net Profit</small>
                        </div>
                    </div>
                    <div style="position: relative; height: 340px;">
                        <canvas id="mainTrendChart"></canvas>
                    </div>
                </div>

                {{-- RIGHT: Money Circulation Donut --}}
                <div class="col-md-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">
                                <i class="fa fa-pie-chart text-warning mr-2"></i> Money Circulation Audit
                            </h5>
                            <small class="text-muted">How every shilling of revenue is allocated</small>
                        </div>
                    </div>
                    <div style="position: relative; height: 240px;">
                        <canvas id="circulationChart"></canvas>
                        <div style="position:absolute;top:45%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                            <div style="font-size:0.62rem;color:#888;text-transform:uppercase;letter-spacing:0.5px;">Revenue</div>
                            <div style="font-size:0.95rem;font-weight:700;color:#333;">{{ number_format($totals['revenue']) }}</div>
                        </div>
                    </div>
                    <div class="mt-3 px-2">
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted small"><i class="fa fa-circle text-primary mr-1" style="font-size:0.6rem"></i> Invested in Stock (COGS):</span>
                            <span class="font-weight-bold small">TSh {{ number_format($totals['cogs'] ?? 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                            <span class="text-muted small"><i class="fa fa-circle text-danger mr-1" style="font-size:0.6rem"></i> Consumed by Expenses:</span>
                            <span class="font-weight-bold small text-danger">TSh {{ number_format($totals['expense']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between pt-1">
                            <span class="text-muted small font-weight-bold"><i class="fa fa-circle text-success mr-1" style="font-size:0.6rem"></i> Net Profit Obtained:</span>
                            <span class="font-weight-bold small text-success">TSh {{ number_format($totals['profit']) }}</span>
                        </div>
                        <div class="alert alert-light border mt-3 py-2 px-3" style="font-size:0.72rem;">
                            <i class="fa fa-info-circle mr-1 text-info"></i>
                            <strong>Formula:</strong> Revenue = COGS + Expenses + Net Profit
                        </div>
                    </div>
                </div>
            </div>

            {{-- Expenses vs Revenue Full-Width Chart --}}
            <div class="row mt-5 pt-4 border-top">
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">
                                <i class="fa fa-line-chart text-danger mr-2"></i> Expenses vs Money in Circulation
                            </h5>
                            <small class="text-muted">Daily revenue flow vs. operational expenses — watch for cost creep</small>
                        </div>
                        <div class="text-right">
                            @php $expRatio = $totals['revenue'] > 0 ? round(($totals['expense'] / $totals['revenue']) * 100, 1) : 0; @endphp
                            <div class="font-weight-bold {{ $expRatio > 30 ? 'text-danger' : 'text-success' }}" style="font-size:1.15rem;">{{ $expRatio }}%</div>
                            <small class="text-muted">Expense Ratio</small>
                        </div>
                    </div>
                    <div style="position: relative; height: 340px;">
                        <canvas id="expensesTrendChart"></canvas>
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
                            label: "Net Profit (Daily)",
                            borderColor: "rgba(76, 175, 80, 1)",
                            backgroundColor: "rgba(76, 175, 80, 0.12)",
                            data: {!! json_encode($chartData['profit']) !!},
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: "rgba(76, 175, 80, 1)",
                            yAxisID: 'y'
                        },
                        {
                            label: "Profit Margin (%)",
                            borderColor: "rgba(255, 152, 0, 1)",
                            backgroundColor: "rgba(255, 152, 0, 0)",
                            data: {!! json_encode($chartData['margin']) !!},
                            fill: false,
                            tension: 0.4,
                            borderWidth: 2,
                            borderDash: [6, 4],
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(255, 152, 0, 1)",
                            yAxisID: 'y2'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, font: { size: 12 } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if (context.dataset.yAxisID === 'y2') {
                                        return ' ' + context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                                    }
                                    return ' ' + context.dataset.label + ': TSh ' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            grid: { color: 'rgba(0,0,0,0.05)' },
                            ticks: {
                                callback: function(value) {
                                    return 'TSh ' + (value >= 1000 ? (value/1000).toFixed(0)+'k' : value);
                                }
                            }
                        },
                        y2: {
                            beginAtZero: true,
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: {
                                callback: function(value) { return value + '%'; }
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

        // Expenses vs Circulation Trend Chart
        const expCtx = document.getElementById("expensesTrendChart");
        if (expCtx) {
            new Chart(expCtx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['labels']) !!},
                    datasets: [
                        {
                            label: "Money Circulation",
                            borderColor: "rgba(30, 136, 229, 1)",
                            backgroundColor: "rgba(30, 136, 229, 0)",
                            data: {!! json_encode($chartData['revenue']) !!},
                            fill: false,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(30, 136, 229, 1)"
                        },
                        {
                            label: "Operational Expenses",
                            borderColor: "rgba(255, 82, 82, 1)",
                            backgroundColor: "rgba(255, 82, 82, 0.1)",
                            data: {!! json_encode($chartData['expense']) !!},
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(255, 82, 82, 1)"
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
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: function(value) { return 'TSh ' + (value >= 1000 ? (value/1000).toFixed(0)+'k' : value); } } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    })();
</script>
@endpush
