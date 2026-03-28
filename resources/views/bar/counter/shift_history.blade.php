@extends('layouts.dashboard')

@section('title', 'Staff Shift Tracking')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-history"></i> Staff Shift Tracking</h1>
    <p>Monitor staff operational shifts and financial handovers.</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Shift History</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <form method="GET" class="row mb-4">
            @if($canViewAll ?? false)
            <div class="col-md-3">
                <label class="small font-weight-bold">Staff Member</label>
                <select name="staff_id" class="form-control select2">
                    <option value="">All Staff</option>
                    @foreach($allStaff as $s)
                        <option value="{{ $s->id }}" {{ request('staff_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->full_name }} ({{ $s->role->name ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <label class="small font-weight-bold">Date</label>
                <input type="date" name="date" class="form-control" value="{{ request('date') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-block">Filter</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('bar.counter.shift.history') }}" class="btn btn-secondary btn-block">Clear</a>
            </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead class="bg-light">
              <tr>
                <th>Shift #</th>
                <th>Staff</th>
                <th>Opened / Closed</th>
                <th>Opening</th>
                <th>Gross Revenue</th>
                <th>Expenses</th>
                <th>Total Handover</th>
                <th>Status</th>
                <th>Manager Audit</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @if($shifts->count() > 0)
                @foreach($shifts as $shift)
                @php
                  $recon = $reconciliationsByShift[$shift->id] ?? null;
                  $handover = $handoversByShift[$shift->id] ?? null;
                  $totalExpRevenue = $recon['expected'] ?? ($shift->total_sales_cash + $shift->total_sales_digital);
                  $submittedTotal = $handover ? $handover->amount : ($recon['submitted'] ?? $shift->closing_balance);
                  $shiftExpenses = $recon['expenses'] ?? 0;
                  $finalShortage = $recon['shortage'] ?? 0;
                @endphp
                <tr>
                  <td><strong>{{ $shift->shift_number }}</strong></td>
                  <td>{{ $shift->staff->full_name }}</td>
                  <td>
                    <small><b>Op:</b> {{ $shift->opened_at->format('M d, H:i') }}</small><br>
                    <small><b>Cl:</b> {{ $shift->closed_at ? $shift->closed_at->format('M d, H:i') : '-' }}</small>
                  </td>
                  <td>TSh {{ number_format($shift->opening_balance) }}</td>
                  <td>
                    <span class="text-primary font-weight-bold">TSh {{ number_format($totalExpRevenue) }}</span>
                  </td>
                  <td>
                      @if($shiftExpenses > 0)
                          <span class="badge badge-warning px-2 py-1">TSh {{ number_format($shiftExpenses) }}</span>
                      @else
                          <span class="text-muted small">0.00</span>
                      @endif
                  </td>
                  <td>
                    <span class="text-success font-weight-bold">TSh {{ number_format($submittedTotal) }}</span>
                  </td>
                  <td>
                    @if($shift->status === 'open')
                      <span class="badge badge-success px-2 py-1">ACTIVE</span>
                    @else
                      <span class="badge badge-secondary px-2 py-1">CLOSED</span>
                    @endif
                  </td>
                  {{-- Manager Audit Status --}}
                  <td>
                    @php
                      $isAudited = ($handover && $handover->status === 'verified') || ($recon && $recon['status'] === 'verified');
                    @endphp
                    @if($isAudited)
                        <span class="badge badge-success px-2 py-1 mb-1" style="font-size:0.75rem;">
                          <i class="fa fa-check-circle"></i> VERIFIED & AUDITED
                        </span>
                        @if(abs($finalShortage) < 0.01)
                             <br><small class="text-success font-weight-bold"><i class="fa fa-shield"></i> Balanced</small>
                        @elseif($finalShortage > 0)
                          <br><small class="text-danger font-weight-bold mt-1 d-block">
                            <i class="fa fa-exclamation-triangle"></i>
                            Short: TSh {{ number_format($finalShortage) }}
                          </small>
                        @else
                          <br><small class="text-info font-weight-bold mt-1 d-block">
                            <i class="fa fa-arrow-up"></i>
                            Surplus: TSh {{ number_format(abs($finalShortage)) }}
                          </small>
                        @endif
                    @elseif($recon)
                        <span class="badge badge-warning px-2 py-1" style="font-size:0.75rem;">
                          <i class="fa fa-clock-o"></i> PENDING AUDIT
                        </span>
                        <br><small class="text-muted mt-1 d-block">Awaiting manager check</small>
                    @elseif($shift->status === 'closed')
                      <span class="badge badge-light border px-2 py-1 text-muted" style="font-size:0.75rem;">
                        <i class="fa fa-minus-circle"></i> NOT AUDITED
                      </span>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-nowrap">
                    @if($shift->status === 'closed')
                        <a href="{{ route('bar.counter.reconciliation') }}?shift_id={{ $shift->id }}" class="btn btn-sm btn-outline-info shadow-sm mr-1" title="View Detailed Reconciliation">
                            <i class="fa fa-eye"></i> RECON
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" onclick="printShiftReconciliation('{{ $shift->id }}')">
                            <i class="fa fa-print"></i> PRINT
                        </button>
                    @else
                        <a href="{{ route('bar.counter.reconciliation') }}" class="btn btn-sm btn-info shadow-sm">
                            <i class="fa fa-eye"></i> ACTIVE SHIFT
                        </a>
                    @endif
                  </td>
                </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="11" class="text-center p-5 text-muted">No shift records found.</td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $shifts->appends(request()->input())->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function printShiftReconciliation(shiftId) {
    // Navigate to print route if it exists, otherwise use window.print logic
    // For now, we will open a clean print view
    window.open("{{ url('bar/counter/shift/print') }}/" + shiftId, "Shift Report", "width=800,height=600");
}

$(document).ready(function() {
    // Explicitly catch alert_success if the layout missed it
    @if(session('alert_success'))
        if(typeof showAlert === 'function') {
            showAlert('success', '{{ session('alert_success') }}', 'Shift Closed!');
        }
    @endif
});
</script>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });
    });
</script>
@endpush
