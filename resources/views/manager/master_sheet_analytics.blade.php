@extends('layouts.dashboard')

@section('title', 'Master Sheet Analytics')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-bar-chart"></i> Master Sheet Analytics</h1>
    <p>Executive financial summary, trends, and expense distribution.</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item">Manager</li>
    <li class="breadcrumb-item active">Master Sheet Analytics</li>
  </ul>
</div>

{{-- TOP SUMMARY CARDS --}}
<div class="row">
  <div class="col-md-6 col-lg-3">
    <div class="widget-small primary coloured-icon"><i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>TOTAL REVENUE</h4>
        <p><b>TSh {{ number_format($summary->revenue ?? 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="widget-small danger coloured-icon"><i class="icon fa fa-minus-circle fa-3x"></i>
      <div class="info">
        <h4>TOTAL EXPENSES</h4>
        <p><b>TSh {{ number_format($summary->expenses ?? 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="widget-small success coloured-icon"><i class="icon fa fa-trophy fa-3x"></i>
      <div class="info">
        <h4>TOTAL PROFIT</h4>
        <p><b>TSh {{ number_format($summary->profit ?? 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="widget-small info coloured-icon"><i class="icon fa fa-line-chart fa-3x"></i>
      <div class="info">
        <h4>AVG REVENUE</h4>
        <p><b>TSh {{ number_format($summary->avg_revenue ?? 0) }}</b></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  {{-- TREND CHART --}}
  <div class="col-md-8">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title"><i class="fa fa-area-chart"></i> Revenue & Profit Trend</h3>
        <div class="btn-group btn-group-sm">
           <a href="?days=7" class="btn btn-{{ $days == 7 ? 'primary' : 'outline-primary' }}">7D</a>
           <a href="?days=30" class="btn btn-{{ $days == 30 ? 'primary' : 'outline-primary' }}">30D</a>
           <a href="?days=90" class="btn btn-{{ $days == 90 ? 'primary' : 'outline-primary' }}">90D</a>
        </div>
      </div>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas class="embed-responsive-item" id="trendChart"></canvas>
      </div>
    </div>
  </div>

  {{-- EXPENSE PIE CHART --}}
  <div class="col-md-4">
    <div class="tile">
      <h3 class="tile-title text-center"><i class="fa fa-pie-chart"></i> Expense Distribution</h3>
      <div class="embed-responsive embed-responsive-1by1">
        <canvas class="embed-responsive-item" id="expensePieChart"></canvas>
      </div>
      <div class="mt-3">
         @foreach($expenseGroups->take(4) as $ex)
            <div class="d-flex justify-content-between small text-muted mb-1">
               <span>{{ $ex->category }}:</span>
               <span class="font-weight-bold">TSh {{ number_format($ex->total) }}</span>
            </div>
         @endforeach
      </div>
    </div>
  </div>
</div>

<div class="row">
  {{-- PENDING COLLECTIONS SECTION --}}
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
         <h3 class="tile-title text-danger"><i class="fa fa-hand-holding-usd"></i> Pending Profit Collections</h3>
         <small class="text-muted">You must physically confirm receipt of these amounts.</small>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-bordered">
          <thead class="bg-light text-center">
            <tr>
              <th>Date</th>
              <th>Source</th>
              <th>Submitted By</th>
              <th>Amount to Receive</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody class="text-center">
             @forelse($pendingCollections as $handover)
                <tr>
                   <td>{{ $handover->handover_date->format('d M, Y') }}</td>
                   <td><span class="badge badge-info">Master Sheet</span></td>
                   <td>{{ $handover->staff->full_name ?? 'Accountant' }}</td>
                   <td class="font-weight-bold">TSh {{ number_format($handover->amount) }}</td>
                   <td>
                      <form action="{{ route('manager.master-sheet.confirm-handover', $handover->id) }}" method="POST" id="confirm-form-{{ $handover->id }}">
                         @csrf
                         <button type="button" class="btn btn-sm btn-success" onclick="confirmReceipt({{ $handover->id }}, '{{ number_format($handover->amount) }}')">
                            <i class="fa fa-check"></i> Confirm Receipt
                         </button>
                      </form>
                   </td>
                </tr>
             @empty
                <tr><td colspan="5" class="text-muted italic py-4">No pending profits to be collected at the moment. All collections are verified.</td></tr>
             @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row">
  {{-- MONTHLY BREAKDOWN TABLE --}}
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-calendar"></i> Monthly Financial Performance</h3>
      <div class="table-responsive">
        <table class="table table-hover table-bordered">
          <thead class="bg-light text-center small uppercase">
            <tr>
              <th>Month</th>
              <th>Total Revenue</th>
              <th class="text-danger">Total Expenses</th>
              <th class="text-success">Net Profit</th>
              <th>Growth</th>
            </tr>
          </thead>
          <tbody class="text-center">
             @if($monthlyStats->count() > 0)
               @foreach($monthlyStats as $stat)
                  <tr>
                     <td class="font-weight-bold text-primary">{{ \Carbon\Carbon::create(now()->year, $stat->month, 1)->format('F Y') }}</td>
                     <td>TSh {{ number_format($stat->revenue) }}</td>
                     <td class="text-danger">TSh {{ number_format($stat->expenses) }}</td>
                     <td class="text-success font-weight-bold">TSh {{ number_format($stat->profit) }}</td>
                     <td>
                        <span class="badge badge-success"><i class="fa fa-arrow-up"></i> Active</span>
                     </td>
                  </tr>
               @endforeach
             @else
                <tr><td colspan="5" class="text-muted italic">No analytical data recorded for this year.</td></tr>
             @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script type="text/javascript">
  const trendData = {
      labels: {!! json_encode($chartLabels) !!},
      datasets: [
          {
              label: "Daily Revenue",
              data: {!! json_encode($revenueData) !!},
              borderColor: "rgba(0, 150, 136, 1)",
              backgroundColor: "rgba(0, 150, 136, 0.2)",
              fill: true,
              tension: 0.4
          },
          {
              label: "Daily Profit",
              data: {!! json_encode($profitData) !!},
              borderColor: "rgba(76, 175, 80, 1)",
              backgroundColor: "rgba(76, 175, 80, 0.2)",
              fill: true,
              tension: 0.4
          }
      ]
  };

  const trendCtx = document.getElementById('trendChart').getContext('2d');
  new Chart(trendCtx, {
      type: 'line',
      data: trendData,
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { position: 'top' }
          }
      }
  });

  // 2. Pie Chart: Expenses
  const pieLabels = {!! json_encode($expenseGroups->pluck('category')) !!};
  const pieValues = {!! json_encode($expenseGroups->pluck('total')) !!};
  const pieData = {
      labels: pieLabels,
      datasets: [{
          data: pieValues,
          backgroundColor: ['#F44336', '#FF9800', '#2196F3', '#4CAF50', '#9C27B0', '#607D8B'],
          hoverOffset: 4
      }]
  };

  const pieCtx = document.getElementById('expensePieChart').getContext('2d');
  new Chart(pieCtx, {
      type: 'doughnut',
      data: pieData,
      options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
              legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
          }
      }
  });

  function confirmReceipt(id, amount) {
      showConfirm(
          "Are you sure you have physically received TSh " + amount + " from the accountant?",
          "Confirm Profit Receipt?",
          function() {
              document.getElementById('confirm-form-' + id).submit();
          }
      );
  }
</script>
@endsection
