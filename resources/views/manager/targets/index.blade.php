@extends('layouts.dashboard')

@section('title', 'Sales Targets & Performance')

@push('styles')
<style>
    .xx-small { font-size: 0.65rem; }
    .text-mauzo { color: #d39e00; }
    .bg-mauzo { background-color: #d39e00; }
    .btn-mauzo { background-color: #d39e00; color: #fff; border: none; }
    .btn-mauzo:hover { background-color: #b38600; color: #fff; }
</style>
@endpush

@section('content')
<div class="app-title">
    <div>
        <h1><i class="fa fa-bullseye"></i> Sales Targets & Performance</h1>
        <p>Set and track real-time business goals</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item">Targets</li>
    </ul>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center">
                <form class="form-inline" method="GET">
                    <select name="month" class="form-control mr-2" onchange="this.form.submit()">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" class="form-control mr-2" onchange="this.form.submit()">
                        @foreach(range(date('Y'), date('Y')-2) as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
                <div class="d-flex align-items-center">
                    @php $hasMonthlyTarget = $monthlyTargets->has('monthly_bar'); @endphp
                    
                    @if($hasMonthlyTarget)
                        <form action="{{ route('manager.targets.monthly.reset') }}" method="POST" class="mr-2 mb-0" onsubmit="return confirm('Are you sure you want to reset this month\'s target? This will clear the goal but keep your sales history.')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger shadow-sm px-4" style="border-radius: 20px;">
                                <i class="fa fa-refresh mr-1"></i> Reset Month
                            </button>
                        </form>
                    @endif
                    
                    <button class="btn btn-mauzo px-4 shadow-sm mr-2" style="border-radius: 20px;" data-toggle="modal" data-target="#setMonthlyTargetModal">
                        <i class="fa fa-plus-circle mr-1"></i> {{ $hasMonthlyTarget ? 'Adjust' : 'Set' }} Target
                    </button>

                    <button class="btn btn-primary px-4 shadow-sm mr-2" style="border-radius: 20px;" data-toggle="modal" data-target="#setStaffTargetModal">
                        <i class="fa fa-user-plus mr-1"></i> Set Staff Target
                    </button>

                    <form id="resetStaffTargetsForm" action="{{ route('manager.targets.staff.reset') }}" method="POST" class="mb-0">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="button" class="btn btn-outline-secondary px-3 shadow-sm" style="border-radius: 20px;" onclick="confirmResetStaffTargets()">
                            <i class="fa fa-trash-o mr-1"></i> Reset
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmResetStaffTargets() {
    Swal.fire({
        title: "Are you sure?",
        text: "This will reset all staff targets for {{ $date }} to zero!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#940000",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, Reset it!",
        cancelButtonText: "No, cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('resetStaffTargetsForm').submit();
        }
    });
}
</script>
@endpush
<div class="row">
    <div class="col-md-7">
        <div class="tile h-100">
            @php
                // Use the passed $barTarget (which contains fallback sum of daily targets)
                $barActual = $progress['bar_actual'] ?? 0;
                $barPercent = $barTarget > 0 ? min(100, round(($barActual / $barTarget) * 100)) : 0;
                $hasTarget = $barTarget > 0;
                $dayOfMonth = (int)date('j');
                $daysInMonth = (int)date('t');
                $daysLeft = max(1, $daysInMonth - $dayOfMonth + 1);
                $expectedPacing = ($dayOfMonth / $daysInMonth) * 100;
                $isPositive = $barPercent >= $expectedPacing;
                $isExplicit = $monthlyTargets->has('monthly_bar');
            @endphp
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="tile-title mb-0"><i class="fa fa-glass text-mauzo"></i> Monthly Drinks Target</h3>
                @if($hasTarget)
                    <span class="badge {{ $isPositive ? 'badge-success' : 'badge-warning' }} px-3 py-1">
                        {{ $isExplicit ? ($isPositive ? 'ON TRACK' : 'PACING BEHIND') : 'SYSTEM GOAL' }}
                    </span>
                @else
                    <span class="badge badge-secondary px-3 py-1">NOT SET</span>
                @endif
            </div>

            <div class="row mb-4">
                <div class="col-6 border-right">
                    <div class="text-muted small font-weight-bold text-uppercase">Month Revenue</div>
                    <div class="h3 mb-0 font-weight-bold">TSh {{ number_format($barActual) }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small font-weight-bold text-uppercase">Monthly Goal</div>
                    <div class="h3 mb-0 font-weight-bold text-mauzo">{{ $hasTarget ? 'TSh ' . number_format($barTarget) : '?' }}</div>
                </div>
            </div>

            <div class="mt-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="font-weight-bold">Goal Progress</span>
                    <span class="percentage-badge">{{ $barPercent }}%</span>
                </div>
                <div class="progress" style="height: 15px; border-radius: 10px; background-color: #eee;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated {{ $isPositive ? 'bg-success' : 'bg-info' }}" 
                         role="progressbar" style="width: {{ $hasTarget ? $barPercent : 0 }}%; border-radius: 10px;"></div>
                </div>
            </div>

            <div class="row mt-4 pt-3 border-top">
                <div class="col-4 border-right">
                    <div class="text-muted xx-small font-weight-bold text-uppercase">Remaining</div>
                    <div class="font-weight-bold {{ $hasTarget && $barTarget-$barActual > 0 ? 'text-danger' : '' }}">
                        {{ $hasTarget ? 'TSh ' . number_format(max(0, $barTarget - $barActual)) : '-' }}
                    </div>
                </div>
                <div class="col-4 border-right">
                    <div class="text-muted xx-small font-weight-bold text-uppercase">Daily Req.</div>
                    <div class="font-weight-bold">
                        {{ $hasTarget ? 'TSh ' . number_format(max(0, $barTarget - $barActual) / $daysLeft) : '-' }}
                    </div>
                </div>
                <div class="col-4">
                    <div class="text-muted xx-small font-weight-bold text-uppercase">Time Left</div>
                    <div class="font-weight-bold text-primary">{{ $daysLeft }} Days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Drivers Card -->
    <div class="col-md-5">
        <div class="tile h-100">
            <h3 class="tile-title mb-3"><i class="fa fa-line-chart text-success"></i> Top Beverage Drivers</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Drink Name</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topDrivers as $driver)
                        @php
                            $displayName = $driver->variant_name ?: ($driver->brand . ' ' . $driver->measurement);
                        @endphp
                        <tr>
                            <td><span class="font-weight-bold">{{ $displayName }}</span></td>
                            <td class="text-center"><span class="badge badge-info">{{ number_format($driver->total_qty) }}</span></td>
                            <td class="text-right font-weight-bold text-mauzo">TSh {{ number_format($driver->total_revenue) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">Identify your best sellers soon!</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                <h3 class="tile-title mb-0"><i class="fa fa-users text-primary"></i> Team Daily Performance ({{ \Carbon\Carbon::parse($date)->format('M d, Y') }})</h3>
                <div class="d-flex align-items-center">
                    <span class="mr-2 text-muted small">Change Date:</span>
                    <input type="date" class="form-control form-control-sm" value="{{ $date }}" onchange="window.location.href='?date='+this.value">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Staff Member</th>
                            <th>Daily Target</th>
                            <th class="text-center">Today's Sales</th>
                            <th class="text-center">MTD Sales (Month)</th>
                            <th width="20%">Progress</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($waiters as $staff)
                            @php
                                $targetRec = $staffTargets->firstWhere('staff_id', $staff->id);
                                $targetAmt = $targetRec->target_amount ?? 0;
                                $todayActual = $progress['staff_actual'][$staff->id] ?? 0;
                                $monthActual = $progress['staff_month_actual'][$staff->id] ?? 0;
                                $pct = $targetAmt > 0 ? min(100, round(($todayActual / $targetAmt) * 100)) : 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-2 bg-mauzo text-white rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 35px; height: 35px;">
                                            {{ substr($staff->full_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark">{{ $staff->full_name }}</div>
                                            <span class="badge badge-secondary xx-small">{{ strtoupper($staff->role->slug ?? 'STAFF') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-weight-bold">
                                    @if($targetAmt > 0)
                                        TSh {{ number_format($targetAmt) }}
                                    @else
                                        <span class="text-muted small italic">Not Set</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="font-weight-bold {{ $todayActual > 0 ? 'text-success' : 'text-muted' }}">
                                        TSh {{ number_format($todayActual) }}
                                    </div>
                                </td>
                                <td class="text-center font-weight-bold text-mauzo">
                                    TSh {{ number_format($monthActual) }}
                                </td>
                                <td>
                                    @if($targetAmt > 0)
                                        <div class="progress mb-1" style="height: 10px; border-radius: 10px;">
                                            <div class="progress-bar bg-info" style="width: {{ $pct }}%; border-radius: 10px;"></div>
                                        </div>
                                        <span class="small font-weight-bold">{{ $pct }}%</span>
                                    @else
                                        <div class="text-muted small italic px-2">Waiting for daily goal...</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($targetAmt > 0)
                                        @if($pct >= 100)
                                            <span class="badge badge-success px-3 py-1">HIT!</span>
                                        @elseif($pct >= 75)
                                            <span class="badge badge-info px-3 py-1">ON TRACK</span>
                                        @else
                                            <span class="badge badge-warning px-3 py-1">PUSHING</span>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary px-3 py-1">PENDING</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted mb-3"><i class="fa fa-users fa-3x opacity-2"></i><br>No active staff found in system.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Target Modal -->
<div class="modal fade" id="setMonthlyTargetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form action="{{ route('manager.targets.monthly.store') }}" method="POST">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <div class="modal-content border-0 shadow" style="border-radius: 15px;">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold px-2">Set Monthly Drinks Target</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Target for {{ date('F Y', mktime(0,0,0, $month, 1, $year)) }}</p>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Targeted Bar Sales (TSh)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0">TSh</span>
                            </div>
                            <input type="number" name="bar_target" class="form-control form-control-lg border-left-0" value="{{ $monthlyTargets['monthly_bar']->target_amount ?? '' }}" placeholder="e.g. 5,000,000">
                        </div>
                        <small class="form-text text-muted mt-2">Setting this goal will update the real-time progress bar on the dashboard.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary border-0" data-dismiss="modal" style="border-radius: 10px;">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" style="border-radius: 10px;">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Staff Target Modal -->
<div class="modal fade" id="setStaffTargetModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form action="{{ route('manager.staff-targets.store') }}" method="POST">
            @csrf
            <input type="hidden" name="target_date" value="{{ $date }}">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Set Daily Staff Target</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Staff Member</label>
                        @if($waiters->count() > 0)
                            <select name="staff_id" class="form-control" required>
                                <option value="">-- Select Option --</option>
                                <option value="all" style="font-weight: bold; color: #4e73df;">All Waiters (Bulk Set)</option>
                                <option disabled>──────────</option>
                                @foreach($waiters as $w)
                                    <option value="{{ $w->id }}">{{ $w->full_name }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="alert alert-warning py-2 mb-0">
                                <small><i class="fa fa-exclamation-triangle"></i> No staff found with <strong>Waiter</strong> role.</small>
                                <br>
                                <a href="{{ route('staff.index') }}" class="btn btn-sm btn-link p-0 mt-1">Go to Staff Management →</a>
                            </div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Daily Sales Target (TSh)</label>
                        <input type="number" name="target_amount" class="form-control" required placeholder="Enter daily target">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Save Staff Target</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
