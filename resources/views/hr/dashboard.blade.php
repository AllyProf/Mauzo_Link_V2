@extends('layouts.dashboard')

@section('title', 'HR Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> HR Dashboard</h1>
    <p>Human Resources Management</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">HR</li>
  </ul>
</div>

<!-- Statistics Cards -->
<div class="row mb-3">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-users fa-3x"></i>
      <div class="info">
        <h4>Total Staff</h4>
        <p><b>{{ $totalStaff }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-check-circle fa-3x"></i>
      <div class="info">
        <h4>Today's Attendance</h4>
        <p><b>{{ $todayAttendance }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-calendar fa-3x"></i>
      <div class="info">
        <h4>Pending Leaves</h4>
        <p><b>{{ $pendingLeaves }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>This Month Payrolls</h4>
        <p><b>{{ $thisMonthPayrolls }}</b></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Recent Attendance -->
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Today's Attendance</h3>
      <div class="tile-body">
        @if($recentAttendance->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recentAttendance as $attendance)
                <tr>
                  <td>{{ $attendance->staff->full_name }}</td>
                  <td>
                    @if($attendance->check_in_time)
                      <span class="badge badge-success">
                        <i class="fa fa-sign-in"></i> {{ $attendance->check_in_time->format('H:i') }}
                      </span>
                    @else
                      <span class="badge badge-secondary">-</span>
                    @endif
                  </td>
                  <td>
                    @if($attendance->check_out_time)
                      <span class="badge badge-danger">
                        <i class="fa fa-sign-out"></i> {{ $attendance->check_out_time->format('H:i') }}
                      </span>
                    @else
                      <span class="badge badge-secondary">-</span>
                    @endif
                  </td>
                  <td>
                    @if($attendance->status === 'present')
                      <span class="badge badge-success">Present</span>
                    @elseif($attendance->status === 'late')
                      <span class="badge badge-warning">Late</span>
                    @elseif($attendance->status === 'absent')
                      <span class="badge badge-danger">Absent</span>
                    @else
                      <span class="badge badge-secondary">{{ ucfirst($attendance->status) }}</span>
                    @endif
                    @if($attendance->is_biometric ?? false)
                      <span class="badge badge-info ml-1" title="Recorded via Biometric Device">
                        <i class="fa fa-fingerprint"></i> Biometric
                      </span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">No attendance records for today.</p>
        @endif
        <div class="mt-3">
          <a href="{{ route('hr.attendance') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-eye"></i> View All Attendance
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Leave Requests -->
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Pending Leave Requests</h3>
      <div class="tile-body">
        @if($pendingLeaveRequests->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Type</th>
                  <th>Days</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($pendingLeaveRequests as $leave)
                <tr>
                  <td>{{ $leave->staff->full_name }}</td>
                  <td><span class="badge badge-info">{{ ucfirst($leave->leave_type) }}</span></td>
                  <td>{{ $leave->days }}</td>
                  <td>{{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d, Y') }}</td>
                  <td>
                    <a href="{{ route('hr.leaves') }}" class="btn btn-sm btn-info">
                      <i class="fa fa-eye"></i> View
                    </a>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">No pending leave requests.</p>
        @endif
        <div class="mt-3">
          <a href="{{ route('hr.leaves') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-eye"></i> View All Leaves
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Attendance Summary Chart -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">This Month Attendance Summary</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-3">
            <div class="text-center">
              <h4 class="text-success">{{ $attendanceSummary['present'] ?? 0 }}</h4>
              <p class="text-muted">Present</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <h4 class="text-danger">{{ $attendanceSummary['absent'] ?? 0 }}</h4>
              <p class="text-muted">Absent</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <h4 class="text-warning">{{ $attendanceSummary['late'] ?? 0 }}</h4>
              <p class="text-muted">Late</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="text-center">
              <h4 class="text-info">{{ $attendanceSummary['leave'] ?? 0 }}</h4>
              <p class="text-muted">On Leave</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-2">
            <a href="{{ route('hr.attendance') }}" class="btn btn-block btn-primary btn-lg">
              <i class="fa fa-check-circle"></i><br>Mark Attendance
            </a>
          </div>
          <div class="col-md-2">
            <a href="{{ route('hr.biometric-devices') }}" class="btn btn-block btn-secondary btn-lg">
              <i class="fa fa-fingerprint"></i><br>Biometric Devices
            </a>
          </div>
          <div class="col-md-2">
            <a href="{{ route('hr.leaves') }}" class="btn btn-block btn-info btn-lg">
              <i class="fa fa-calendar"></i><br>Manage Leaves
            </a>
          </div>
          <div class="col-md-2">
            <a href="{{ route('hr.payroll') }}" class="btn btn-block btn-success btn-lg">
              <i class="fa fa-money"></i><br>Payroll
            </a>
          </div>
          <div class="col-md-2">
            <a href="{{ route('hr.performance-reviews') }}" class="btn btn-block btn-warning btn-lg">
              <i class="fa fa-star"></i><br>Performance Reviews
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

