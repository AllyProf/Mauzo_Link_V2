@extends('layouts.dashboard')

@section('title', 'Manager Dashboard')

@push('styles')
<style>
  /* ── Trend chart ── */
  #revenueTrendChart { max-height: 250px; }

  /* ── Top products bar ── */
  .product-bar-row { margin-bottom: 12px; }
  .product-bar-label { font-size: 13px; font-weight: 600; color: #37474f; margin-bottom: 4px; display: flex; justify-content: space-between; }
  .product-bar-track { height: 8px; background: #e8eaf6; border-radius: 4px; overflow: hidden; }
  .product-bar-fill  { height: 100%; background: #009688; border-radius: 4px; transition: width 1s ease; }

  /* ── Empty states ── */
  .empty-state { text-align: center; padding: 30px 0; color: #90a4ae; }
  .empty-state i { font-size: 36px; display: block; margin-bottom: 8px; }
</style>
@endpush

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-tachometer"></i> Manager Dashboard</h1>
    <p>Welcome back, <strong>{{ $staff->full_name }}</strong> &mdash; {{ now()->format('l, F j, Y') }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item">Manager Dashboard</li>
  </ul>
</div>

{{-- ═══════════════════════════════════════╗
     ROW 1 – KPI Cards                     ║
══════════════════════════════════════════--}}
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon" style="min-height: 110px;"><i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Today Revenue</h4>
        <p><b>TSh {{ number_format($todayRevenue) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon" style="min-height: 110px;"><i class="icon fa fa-line-chart fa-3x"></i>
      <div class="info">
        <h4>Month {{ now()->format('M Y') }}</h4>
        <p><b>TSh {{ number_format($monthRevenue) }}</b></p>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="widget-small danger coloured-icon" style="min-height: 110px;"><i class="icon fa fa-shopping-bag fa-3x"></i>
      <div class="info">
        <h4>Month Purchases</h4>
        <p><b>TSh {{ number_format($monthlyPurchaseCost) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon" style="min-height: 110px;"><i class="icon fa fa-trophy fa-3x"></i>
      <div class="info">
        <h4>Boss Profit (M)</h4>
        <p><b>TSh {{ number_format($monthProfit) }}</b></p>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════╗
     ROW 1.5 – Monthly Targets Progress     ║
══════════════════════════════════════════--}}
<div class="row">
  <div class="col-md-12 mb-4">
    <div class="tile pb-2">
      <div class="d-flex justify-content-between">
        <h6 class="text-muted small font-weight-bold"><i class="fa fa-glass mr-1"></i> MONTHLY GOAL</h6>
        <span class="badge badge-primary">{{ $barTargetProgress }}%</span>
      </div>
      <div class="product-bar-track mt-2">
        <div class="product-bar-fill bg-info" style="width: {{ $barTargetProgress }}%; background-color: #36b9cc !important;"></div>
      </div>
      <div class="d-flex justify-content-between mt-1 tiny text-muted">
        <span>TSh {{ number_format($monthRevenue) }}</span>
        <span>Target: TSh {{ number_format($barMonthlyTarget) }}</span>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════
     ROW 2 – Revenue Trend Chart  |  Category Distribution ║
═══════════════════════════════════════════════════--}}
<div class="row">
  <div class="col-md-8 mb-4">
    <div class="tile h-100 mb-0">
      <h3 class="tile-title"><i class="fa fa-area-chart"></i> Revenue – Last 7 Days</h3>
      <div class="tile-body">
        <canvas id="revenueTrendChart" style="max-height: 300px;"></canvas>
        @if($revenueTrend->isEmpty())
          <div class="empty-state"><i class="fa fa-bar-chart"></i> No revenue data yet</div>
        @endif
      </div>
    </div>
  </div>
  
  <div class="col-md-4 mb-4">
    <div class="tile h-100 mb-0">
      <h3 class="tile-title"><i class="fa fa-pie-chart"></i> Category Sales</h3>
      <div class="tile-body text-center">
        <div style="position: relative; height: 200px; width: 100%; display: flex; justify-content: center; align-items: center; margin-bottom:  १५px;">
          <canvas id="categoryDistributionChart"></canvas>
        </div>
        @if(isset($categoryDistribution) && $categoryDistribution->count() > 0)
          <ul class="list-group list-group-flush text-left" style="font-size: 13px;">
            @foreach($categoryDistribution->take(4) as $cat)
              <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1" style="border:none; border-bottom:1px solid #f0f0f0;">
                <span class="text-truncate" style="max-width: 70%;"><i class="fa fa-circle mr-2" style="font-size:8px; color:#1a237e;"></i>{{ $cat->category ?? 'Uncategorized' }}</span>
                <span class="badge badge-primary badge-pill">{{ number_format($cat->total_sold) }}</span>
              </li>
            @endforeach
          </ul>
        @else
          <p class="text-muted text-center mt-3">No data available for distribution.</p>
        @endif
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     ROW 3 – Top Products & Quick Links                    ║
═══════════════════════════════════════════════════════--}}
<div class="row">
  <div class="col-md-8 mb-4">
    <div class="tile h-100 mb-0">
      <h3 class="tile-title"><i class="fa fa-star"></i> Top Products This Month</h3>
      <div class="tile-body">
        @if($topProducts->count() > 0)
          @php $maxSold = $topProducts->max('total_sold') ?: 1; @endphp
          <div class="row">
            @foreach($topProducts as $tp)
              @php
                $name = $tp->display_name;
                $pct = round(($tp->total_sold / $maxSold) * 100);
              @endphp
              <div class="col-md-6 mb-3">
                <div class="product-bar-label">
                  <span class="text-truncate pr-2">{{ $name }}</span>
                  <span class="text-primary font-weight-bold">
                    {{ $tp->total_sold_formatted }}
                  </span>
                </div>
                <div class="product-bar-track">
                  <div class="product-bar-fill" style="width: {{ $pct }}%"></div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <div class="empty-state"><i class="fa fa-star-o"></i> No sales data this month</div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-4 mb-4">
    <div class="tile h-100 mb-0">
      <h3 class="tile-title"><i class="fa fa-bolt"></i> Quick Links</h3>
      <div class="tile-body">
        <div class="d-flex flex-column h-100 justify-content-center">

          <a href="{{ route('bar.manager.reconciliations') }}" class="btn btn-outline-danger mb-3 text-left">
            <i class="icon fa fa-balance-scale mr-2"></i> Reconciliation Center
          </a>

          <a href="{{ route('bar.stock-receipts.index') }}" class="btn btn-outline-success mb-3 text-left">
            <i class="fa fa-inbox mr-2"></i> Receiving Stock
          </a>

          <a href="{{ route('bar.products.index') }}" class="btn btn-outline-secondary mb-3 text-left">
            <i class="fa fa-cubes mr-2"></i> Products List
          </a>

          <a href="{{ route('manager.reports.trends') }}" class="btn btn-outline-info mb-3 text-left">
            <i class="fa fa-line-chart mr-2"></i> Business Trends
          </a>

          <a href="{{ route('manager.targets.index') }}" class="btn btn-outline-warning mb-3 text-left">
            <i class="fa fa-bullseye mr-2"></i> Monthly Targets
          </a>

          <div class="border-top mb-3 pt-3 text-muted small font-weight-bold ml-2">STOCK CONTROL</div>
          <a href="{{ route('bar.counter.counter-stock') }}" class="btn btn-outline-dark mb-3 text-left">
            <i class="fa fa-cubes mr-2"></i> Counter Stock
          </a>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function() {
  const trendData = @json($revenueTrend);

  // Fill missing days in last 7 days
  const allDays = [];
  const allRevenue = [];
  const allOrders = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    const dateStr = d.toISOString().slice(0, 10);
    allDays.push(d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }));
    const matching = trendData.find(r => r.date === dateStr);
    allRevenue.push(matching ? parseFloat(matching.revenue) : 0);
    allOrders.push(matching ? parseInt(matching.orders) : 0);
  }

  const ctx = document.getElementById('revenueTrendChart');
  if (!ctx) return;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: allDays,
      datasets: [
        {
          label: 'Revenue (TSh)',
          data: allRevenue,
          backgroundColor: 'rgba(30, 136, 229, 0.75)',
          borderColor: '#1565C0',
          borderWidth: 1,
          borderRadius: 6,
          yAxisID: 'y',
        },
        {
          type: 'line',
          label: 'Orders',
          data: allOrders,
          borderColor: '#E65100',
          backgroundColor: 'rgba(230, 81, 0, 0.1)',
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: '#E65100',
          tension: 0.4,
          fill: true,
          yAxisID: 'y1',
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { font: { size: 11 } } },
        tooltip: {
          callbacks: {
            label: function(ctx) {
              if (ctx.datasetIndex === 0) return ' TSh ' + ctx.parsed.y.toLocaleString();
              return ' ' + ctx.parsed.y + ' orders';
            }
          }
        }
      },
      scales: {
        y: {
          type: 'linear', position: 'left',
          ticks: { callback: v => 'TSh ' + (v >= 1000 ? (v/1000).toFixed(0)+'K' : v), font: { size: 10 } },
          grid: { color: 'rgba(0,0,0,0.04)' }
        },
        y1: {
          type: 'linear', position: 'right',
          ticks: { font: { size: 10 } },
          grid: { drawOnChartArea: false }
        }
      }
    }
  });

  // ── Category Distribution Chart ──
  const distData = @json($categoryDistribution);
  const distCtx = document.getElementById('categoryDistributionChart');
  
  if (distCtx && distData && distData.length > 0) {
    const labels = distData.map(d => d.category || 'Uncategorized');
    const data = distData.map(d => parseInt(d.total_sold));
    const bgColors = [
      'rgba(26, 35, 126, 0.8)',   // Indigo
      'rgba(0, 150, 136, 0.8)',   // Teal
      'rgba(233, 30, 99, 0.8)',   // Pink
      'rgba(255, 152, 0, 0.8)',   // Orange
      'rgba(76, 175, 80, 0.8)',   // Green
      'rgba(156, 39, 176, 0.8)',  // Purple
      'rgba(3, 169, 244, 0.8)'    // Light Blue
    ];

    new Chart(distCtx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: bgColors,
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } },
          tooltip: {
            callbacks: {
              label: function(ctx) { return ' ' + ctx.parsed + ' items sold'; }
            }
          }
        },
        cutout: '70%'
      }
    });
  }
})();
</script>
@endpush
