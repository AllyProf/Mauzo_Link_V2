@extends('layouts.dashboard')

@section('title', 'Sales Targets & Performance')

@push('styles')
<style>
    .target-card {
        border-radius: 15px;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .target-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    
    .progress-wrapper {
        position: relative;
        height: 12px;
        background: #eee;
        border-radius: 10px;
        overflow: hidden;
        margin: 15px 0;
    }
    .progress-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 1s ease-in-out;
    }
    .bg-bar { background: linear-gradient(90deg, #36b9cc 0%, #1a89a7 100%); }
    .bg-food { background: linear-gradient(90deg, #f6c23e 0%, #dfa100 100%); }
    .bg-staff { background: linear-gradient(90deg, #4e73df 0%, #224abe 100%); }
    
    .percentage-badge {
        font-size: 1.2rem;
        font-weight: 800;
        color: #333;
    }
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .status-on-track { background-color: #1cc88a; box-shadow: 0 0 8px #1cc88a; }
    .status-behind { background-color: #e74a3b; box-shadow: 0 0 8px #e74a3b; }
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
                <div>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#setMonthlyTargetModal">
                        <i class="fa fa-edit"></i> Set Monthly Targets
                    </button>
                    <button class="btn btn-info" data-toggle="modal" data-target="#setStaffTargetModal">
                        <i class="fa fa-user-plus"></i> Set Staff Daily Target
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Monthly Bar Target -->
    <div class="col-md-6 mb-4">
        @php
            $barTarget = $monthlyTargets['monthly_bar']->target_amount ?? 0;
            $barActual = $progress['bar_actual'] ?? 0;
            $barPercent = $barTarget > 0 ? min(100, round(($barActual / $barTarget) * 100)) : 0;
            $isPositive = $barPercent >= (date('j') / date('t')) * 100; // Simplified pacing check
        @endphp
        <div class="card target-card h-100 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="p-3 bg-soft-info rounded-circle" style="background-color: rgba(54, 185, 204, 0.1);">
                        <i class="fa fa-glass fa-2x text-info"></i>
                    </div>
                    <div class="text-right">
                        <span class="badge {{ $isPositive ? 'badge-success' : 'badge-warning' }} mb-2">
                             <i class="fa {{ $isPositive ? 'fa-line-chart' : 'fa-clock-o' }}"></i> 
                             {{ $isPositive ? 'On Track' : 'Pacing Behind' }}
                        </span>
                        <div class="h2 mb-0 font-weight-bold text-info">{{ $barPercent }}%</div>
                    </div>
                </div>
                
                <h6 class="text-muted text-uppercase mb-1 small font-weight-bold">Bar Sales Progress</h6>
                <div class="h4 mb-3 font-weight-bold">TSh {{ number_format($barActual) }} <span class="text-muted small">/ TSh {{ number_format($barTarget) }}</span></div>
                
                <div class="progress-wrapper mb-3" style="height: 15px; background: rgba(0,0,0,0.05); border-radius: 30px;">
                    <div class="progress-fill bg-bar progress-bar-striped progress-bar-animated" 
                         style="width: {{ $barPercent }}%; height: 100%; border-radius: 30px; background: linear-gradient(45deg, #36b9cc, #1a89a7);"></div>
                </div>
                
                <div class="row text-center mt-3">
                    <div class="col-6 border-right">
                        <div class="text-muted small">Remaining</div>
                        <div class="font-weight-bold text-danger">TSh {{ number_format(max(0, $barTarget - $barActual)) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Daily Needed</div>
                        @php 
                            $daysLeft = max(1, date('t') - date('j'));
                            $needed = max(0, $barTarget - $barActual) / $daysLeft;
                        @endphp
                        <div class="font-weight-bold text-dark">TSh {{ number_format($needed) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Food Target -->
    <div class="col-md-6 mb-4">
        @php
            $foodTarget = $monthlyTargets['monthly_food']->target_amount ?? 0;
            $foodActual = $progress['food_actual'] ?? 0;
            $foodPercent = $foodTarget > 0 ? min(100, round(($foodActual / $foodTarget) * 100)) : 0;
            $isFoodPositive = $foodPercent >= (date('j') / date('t')) * 100;
        @endphp
        <div class="card target-card h-100 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="p-3 bg-soft-warning rounded-circle" style="background-color: rgba(246, 194, 62, 0.1);">
                        <i class="fa fa-cutlery fa-2x text-warning"></i>
                    </div>
                    <div class="text-right">
                        <span class="badge {{ $isFoodPositive ? 'badge-success' : 'badge-warning' }} mb-2">
                             <i class="fa {{ $isFoodPositive ? 'fa-line-chart' : 'fa-clock-o' }}"></i> 
                             {{ $isFoodPositive ? 'On Track' : 'Pacing Behind' }}
                        </span>
                        <div class="h2 mb-0 font-weight-bold text-warning">{{ $foodPercent }}%</div>
                    </div>
                </div>
                
                <h6 class="text-muted text-uppercase mb-1 small font-weight-bold">Food Sales Progress</h6>
                <div class="h4 mb-3 font-weight-bold">TSh {{ number_format($foodActual) }} <span class="text-muted small">/ TSh {{ number_format($foodTarget) }}</span></div>
                
                <div class="progress-wrapper mb-3" style="height: 15px; background: rgba(0,0,0,0.05); border-radius: 30px;">
                    <div class="progress-fill bg-food progress-bar-striped progress-bar-animated" 
                         style="width: {{ $foodPercent }}%; height: 100%; border-radius: 30px; background: linear-gradient(45deg, #f6c23e, #dfa100);"></div>
                </div>
                
                <div class="row text-center mt-3">
                    <div class="col-6 border-right">
                        <div class="text-muted small">Remaining</div>
                        <div class="font-weight-bold text-danger">TSh {{ number_format(max(0, $foodTarget - $foodActual)) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Daily Needed</div>
                        @php 
                            $neededFood = max(0, $foodTarget - $foodActual) / $daysLeft;
                        @endphp
                        <div class="font-weight-bold text-dark">TSh {{ number_format($neededFood) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between mb-3">
                <h3 class="tile-title"><i class="fa fa-users"></i> Staff Daily Performance ({{ \Carbon\Carbon::parse($date)->format('M d, Y') }})</h3>
                <input type="date" class="form-control col-md-2" value="{{ $date }}" onchange="window.location.href='?date='+this.value">
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Staff Name</th>
                            <th>Target Amount</th>
                            <th>Actual Orders Total</th>
                            <th>Performance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffTargets as $st)
                            @php
                                $actual = $progress['staff_actual'][$st->staff_id] ?? 0;
                                $pct = $st->target_amount > 0 ? min(100, round(($actual / $st->target_amount) * 100)) : 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $st->staff->full_name }}</strong><br>
                                    <small class="text-muted">{{ $st->staff->role->name ?? 'Staff' }}</small>
                                </td>
                                <td>TSh {{ number_format($st->target_amount) }}</td>
                                <td>TSh {{ number_format($actual) }}</td>
                                <td width="30%">
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 mr-2" style="height: 8px;">
                                            <div class="progress-bar bg-staff" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <small class="font-weight-bold">{{ $pct }}%</small>
                                    </div>
                                </td>
                                <td>
                                    @if($pct >= 100)
                                        <span class="badge badge-success"><i class="fa fa-trophy"></i> Target Hit!</span>
                                    @elseif($pct >= 75)
                                        <span class="badge badge-info">On Track</span>
                                    @else
                                        <span class="badge badge-warning text-white">Needs Push</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="text-muted">No daily targets set for this date.</div>
                                    <button class="btn btn-sm btn-outline-info mt-2" data-toggle="modal" data-target="#setStaffTargetModal">Set First Target</button>
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
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Set Monthly Target ({{ date('F Y', mktime(0,0,0, $month, 1, $year)) }})</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Monthly Bar Target (TSh)</label>
                        <input type="number" name="bar_target" class="form-control" value="{{ $monthlyTargets['monthly_bar']->target_amount ?? '' }}" placeholder="Enter amount">
                    </div>
                    <div class="form-group">
                        <label>Monthly Food Target (TSh)</label>
                        <input type="number" name="food_target" class="form-control" value="{{ $monthlyTargets['monthly_food']->target_amount ?? '' }}" placeholder="Enter amount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Targets</button>
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
