@extends('layouts.dashboard')

@section('title', 'Attendance Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-check-circle"></i> Attendance Management</h1>
    <p>Track and manage staff attendance</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('hr.dashboard') }}">HR</a></li>
    <li class="breadcrumb-item">Attendance</li>
  </ul>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('hr.attendance') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="date" class="mr-2">Date:</label>
          <input type="date" name="date" id="date" class="form-control" value="{{ $date }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="staff_id" class="mr-2">Staff:</label>
          <select name="staff_id" id="staff_id" class="form-control">
            <option value="">All Staff</option>
            @foreach($staff as $s)
              <option value="{{ $s->id }}" {{ $staffId == $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> Filter
        </button>
        <div class="ml-auto d-flex align-items-center">
          <button type="button" id="autoSyncBtn" class="btn btn-secondary ml-2" title="Click to enable auto-refresh every 2 seconds">
            <i class="fa fa-sync" id="autoSyncIcon"></i> 
            <span id="autoSyncText">Auto Sync: OFF</span>
          </button>
          <button type="button" id="manualSyncBtn" class="btn btn-info ml-2" title="Sync attendance now">
            <i class="fa fa-sync"></i> Sync Now
          </button>
          <span id="lastSyncTime" class="ml-3 text-muted small"></span>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Attendance Table -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">
        Attendance Records - {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}
        <span id="syncStatus" class="badge badge-info ml-2" style="display: none;">
          <i class="fa fa-sync fa-spin"></i> Syncing...
        </span>
      </h3>
      <div class="tile-body" id="attendanceTableBody">
        @if($attendances->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Working Hours</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($attendances as $attendance)
                <tr>
                  <td>{{ $attendance->staff->full_name }}</td>
                  <td>
                    @if($attendance->check_in_time)
                      <span class="badge badge-success">
                        <i class="fa fa-sign-in"></i> {{ $attendance->check_in_time->format('H:i:s') }}
                      </span>
                    @else
                      <span class="badge badge-secondary">-</span>
                    @endif
                  </td>
                  <td>
                    @if($attendance->check_out_time)
                      <span class="badge badge-danger">
                        <i class="fa fa-sign-out"></i> {{ $attendance->check_out_time->format('H:i:s') }}
                      </span>
                    @else
                      <span class="badge badge-secondary">-</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $hours = $attendance->working_hours;
                      $wholeHours = floor($hours);
                      $minutes = round(($hours - $wholeHours) * 60);
                      if ($wholeHours > 0 && $minutes > 0) {
                        $displayHours = $wholeHours . 'h ' . $minutes . 'm';
                      } elseif ($wholeHours > 0) {
                        $displayHours = $wholeHours . 'h';
                      } elseif ($minutes > 0) {
                        $displayHours = $minutes . 'm';
                      } else {
                        $displayHours = '0h';
                      }
                    @endphp
                    <span class="badge badge-info">{{ $displayHours }}</span>
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
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No attendance records found for this date.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Mark Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Mark Attendance</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="attendanceForm">
        <div class="modal-body">
          <div class="form-group">
            <label>Staff</label>
            <select name="staff_id" id="modal_staff_id" class="form-control" required>
              <option value="">Select Staff</option>
              @foreach($staff as $s)
                <option value="{{ $s->id }}">{{ $s->full_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Date</label>
            <input type="date" name="attendance_date" id="modal_attendance_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Check In Time</label>
            <input type="datetime-local" name="check_in_time" id="modal_check_in_time" class="form-control">
          </div>
          <div class="form-group">
            <label>Check Out Time</label>
            <input type="datetime-local" name="check_out_time" id="modal_check_out_time" class="form-control">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" id="modal_status" class="form-control" required>
              <option value="present">Present</option>
              <option value="absent">Absent</option>
              <option value="late">Late</option>
              <option value="half_day">Half Day</option>
              <option value="leave">On Leave</option>
            </select>
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" id="modal_notes" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
  let autoSyncInterval = null;
  let deviceSyncInterval = null;
  let isAutoSyncEnabled = false; // Start disabled - user must click button
  const refreshInterval = 2000; // Refresh from database every 2 seconds
  const deviceSyncIntervalTime = 30000; // Sync from device every 30 seconds
  let lastAttendanceCount = 0; // Track attendance count for change detection
  
  // Get device configuration - try to get from first registered device or use config defaults
  @php
    $ownerId = 0;
    if (session('is_staff')) {
      $ownerId = session('staff_user_id');
    } elseif (auth()->check()) {
      $ownerId = auth()->user()->id;
    }
    $deviceMapping = \App\Models\BiometricDeviceMapping::where('user_id', $ownerId)
        ->where('is_registered', true)
        ->first();
    $syncDeviceIp = $deviceMapping ? $deviceMapping->device_ip : config('zkteco.ip', '192.168.100.118');
    $syncDevicePort = $deviceMapping ? $deviceMapping->device_port : config('zkteco.port', 4370);
  @endphp
  const deviceIp = '{{ $syncDeviceIp }}';
  const devicePort = {{ $syncDevicePort }};
  const devicePassword = 0;
  
  // Auto-sync toggle - user must click to enable
  $('#autoSyncBtn').on('click', function() {
    if (isAutoSyncEnabled) {
      // Disable auto-sync
      stopAutoSync();
    } else {
      // Enable auto-sync - will run continuously until disabled
      startAutoSync();
    }
  });
  
  // Start auto-sync - runs continuously
  function startAutoSync() {
    isAutoSyncEnabled = true;
    $('#autoSyncBtn').removeClass('btn-secondary').addClass('btn-success');
    $('#autoSyncIcon').addClass('fa-spin');
    $('#autoSyncText').text('Auto Sync: ON');
    
    // Sync from device immediately, then continuously every 30 seconds
    syncAttendanceFromDevice(false);
    deviceSyncInterval = setInterval(function() {
      syncAttendanceFromDevice(false); // Silent sync - runs continuously
    }, deviceSyncIntervalTime);
    
    // Refresh table from database immediately, then continuously every 2 seconds
    refreshAttendanceTable();
    lastAttendanceCount = 0; // Reset count when enabling
    autoSyncInterval = setInterval(function() {
      refreshAttendanceTable(true); // Show notifications for new records - runs continuously
    }, refreshInterval);
  }
  
  // Stop auto-sync
  function stopAutoSync() {
    isAutoSyncEnabled = false;
    if (autoSyncInterval) {
      clearInterval(autoSyncInterval);
      autoSyncInterval = null;
    }
    if (deviceSyncInterval) {
      clearInterval(deviceSyncInterval);
      deviceSyncInterval = null;
    }
    $('#autoSyncBtn').removeClass('btn-success').addClass('btn-secondary');
    $('#autoSyncIcon').removeClass('fa-spin');
    $('#autoSyncText').text('Auto Sync: OFF');
    $('#syncStatus').hide();
  }
  
  // Cleanup intervals when page is unloaded
  $(window).on('beforeunload', function() {
    stopAutoSync();
  });
  
  // Manual sync button
  $('#manualSyncBtn').on('click', function() {
    syncAttendanceFromDevice(true);
  });
  
  // Refresh attendance table from database
  function refreshAttendanceTable(showNotification = false) {
    const date = $('#date').val();
    const staffId = $('#staff_id').val();
    
    $.ajax({
      url: '{{ route("hr.attendance.json") }}',
      method: 'GET',
      data: {
        date: date,
        staff_id: staffId
      },
      success: function(response) {
        if (response.success) {
          // Check if attendance count changed (new record detected)
          const currentCount = response.attendances.length;
          const hasNewRecord = currentCount > lastAttendanceCount;
          
          if (hasNewRecord && showNotification) {
            // Show notification for new attendance
            const newRecords = currentCount - lastAttendanceCount;
            Swal.fire({
              icon: 'success',
              title: 'New Attendance Recorded!',
              text: `${newRecords} new attendance record(s) detected.`,
              timer: 2000,
              showConfirmButton: false,
              toast: true,
              position: 'top-end'
            });
          }
          
          lastAttendanceCount = currentCount;
          updateAttendanceTable(response.attendances);
          updateLastSyncTime();
        }
      },
      error: function(xhr) {
        console.error('Failed to refresh attendance:', xhr);
      }
    });
  }
  
  // Update attendance table with new data
  function updateAttendanceTable(attendances) {
    if (attendances.length === 0) {
      $('#attendanceTableBody').html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> No attendance records found for this date.</div>');
      return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover"><thead><tr>';
    html += '<th>Staff</th><th>Check In</th><th>Check Out</th><th>Working Hours</th><th>Status</th>';
    html += '</tr></thead><tbody>';
    
    attendances.forEach(function(att) {
      html += '<tr>';
      html += '<td>' + att.staff_name + '</td>';
      
      // Check In with badge
      if (att.check_in_time !== '-') {
        html += '<td><span class="badge badge-success"><i class="fa fa-sign-in"></i> ' + att.check_in_time + '</span></td>';
      } else {
        html += '<td><span class="badge badge-secondary">-</span></td>';
      }
      
      // Check Out with badge
      if (att.check_out_time !== '-') {
        html += '<td><span class="badge badge-danger"><i class="fa fa-sign-out"></i> ' + att.check_out_time + '</span></td>';
      } else {
        html += '<td><span class="badge badge-secondary">-</span></td>';
      }
      
      // Working Hours - already formatted from server (e.g., "1h 23m")
      html += '<td><span class="badge badge-info">' + att.working_hours + '</span></td>';
      
      html += '<td>';
      
      // Status badge
      if (att.status === 'present') {
        html += '<span class="badge badge-success">Present</span>';
      } else if (att.status === 'late') {
        html += '<span class="badge badge-warning">Late</span>';
      } else if (att.status === 'absent') {
        html += '<span class="badge badge-danger">Absent</span>';
      } else {
        html += '<span class="badge badge-secondary">' + att.status.charAt(0).toUpperCase() + att.status.slice(1) + '</span>';
      }
      
      // Biometric badge
      if (att.is_biometric) {
        html += '<span class="badge badge-info ml-1" title="Recorded via Biometric Device"><i class="fa fa-fingerprint"></i> Biometric</span>';
      }
      
      html += '</td>';
      html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    $('#attendanceTableBody').html(html);
  }
  
  // Sync attendance from device
  function syncAttendanceFromDevice(showNotification = false) {
    $('#syncStatus').show();
    const btn = $('#manualSyncBtn');
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');
    
    $.ajax({
      url: '{{ route("hr.biometric-devices.sync-attendance") }}',
      method: 'POST',
      data: {
        _token: '{{ csrf_token() }}',
        device_ip: deviceIp,
        device_port: devicePort,
        password: devicePassword,
        date: '{{ $date }}'
      },
      success: function(response) {
        if (response.success) {
          if (response.synced > 0) {
            // Refresh table from database to show new records
            refreshAttendanceTable(true); // Show notification
            if (showNotification) {
              Swal.fire({
                icon: 'success',
                title: 'Sync Complete!',
                text: `Synced ${response.synced} attendance records.`,
                timer: 2000,
                showConfirmButton: false
              });
            }
          } else {
            // No new records, just update last sync time
            refreshAttendanceTable(); // Still refresh to get latest data
            updateLastSyncTime();
            if (showNotification) {
              Swal.fire({
                icon: 'info',
                title: 'Sync Complete',
                text: 'No new attendance records found.',
                timer: 2000,
                showConfirmButton: false
              });
            }
          }
        } else {
          if (showNotification) {
            Swal.fire({
              icon: 'error',
              title: 'Sync Failed',
              text: response.message || 'Failed to sync attendance'
            });
          }
        }
      },
      error: function(xhr) {
        const error = xhr.responseJSON?.message || 'Sync failed';
        if (showNotification) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error
          });
        }
      },
      complete: function() {
        $('#syncStatus').hide();
        btn.prop('disabled', false).html(originalHtml);
      }
    });
  }
  
  // Update last sync time display
  function updateLastSyncTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString();
    $('#lastSyncTime').text('Last refresh: ' + timeStr);
  }
  
  // Initialize attendance count on page load
  function initializeAttendanceCount() {
    const date = $('#date').val();
    const staffId = $('#staff_id').val();
    $.ajax({
      url: '{{ route("hr.attendance.json") }}',
      method: 'GET',
      data: { date: date, staff_id: staffId },
      success: function(response) {
        if (response.success) {
          lastAttendanceCount = response.attendances.length;
        }
      }
    });
  }
  
  // Initialize on page load
  initializeAttendanceCount();
  
  // Load initial attendance data (one-time, not continuous)
  refreshAttendanceTable();
  
  // Open modal for new attendance
  $('.btn-primary').first().on('click', function() {
    if ($(this).text().includes('Mark')) {
      $('#attendanceForm')[0].reset();
      $('#modal_attendance_date').val('{{ $date }}');
      $('#attendanceModal').modal('show');
    }
  });

  // Edit attendance
  $(document).on('click', '.edit-attendance-btn', function() {
    $('#modal_staff_id').val($(this).data('staff-id'));
    $('#modal_attendance_date').val($(this).data('date'));
    $('#modal_check_in_time').val($(this).data('check-in'));
    $('#modal_check_out_time').val($(this).data('check-out'));
    $('#modal_status').val($(this).data('status'));
    $('#modal_notes').val($(this).data('notes'));
    $('#attendanceModal').modal('show');
  });

  // Submit attendance form
  $('#attendanceForm').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
      url: '{{ route("hr.attendance.mark") }}',
      method: 'POST',
      data: $(this).serialize() + '&_token={{ csrf_token() }}',
      success: function(response) {
        if (response.success) {
          location.reload();
        }
      },
      error: function(xhr) {
        alert(xhr.responseJSON?.error || 'Failed to save attendance');
      }
    });
  });
});
</script>
@endpush
@endsection

