@extends('layouts.dashboard')

@section('title', 'Biometric Device Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-fingerprint"></i> Biometric Device Management</h1>
    <p>Register staff to fingerprint devices and manage attendance</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('hr.dashboard') }}">HR</a></li>
    <li class="breadcrumb-item">Biometric Devices</li>
  </ul>
</div>

<!-- Device Configuration -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Device Configuration</h3>
      <div class="tile-body">
        <form id="testConnectionForm">
          @csrf
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label>Device IP Address *</label>
                <input type="text" name="ip" id="device_ip" class="form-control" value="{{ $deviceIp }}" required>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Port *</label>
                <input type="number" name="port" id="device_port" class="form-control" value="{{ $devicePort }}" required>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>Comm Key</label>
                <input type="number" name="password" id="device_password" class="form-control" value="0">
                <small class="text-muted">Default: 0</small>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label>&nbsp;</label>
                <div>
                  <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-plug"></i> Test Connection
                  </button>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>&nbsp;</label>
                <div>
                  <button type="button" id="syncAttendanceBtn" class="btn btn-info btn-block" title="Manually pull attendance from device">
                    <i class="fa fa-sync"></i> Sync Attendance Now
                  </button>
                </div>
                <small class="text-muted">Manual sync (auto-sync runs every 5 minutes)</small>
              </div>
            </div>
          </div>
          <div id="connectionResult" class="mt-2"></div>
        </form>
        
        <div class="alert alert-info mt-3">
          <h5><i class="fa fa-info-circle"></i> Automatic Sync</h5>
          <p class="mb-2">
            <strong>Automatic attendance syncing is enabled!</strong> The system will automatically sync attendance from your biometric device every 5 minutes.
          </p>
          <p class="mb-0">
            <strong>How it works:</strong>
            <ul class="mb-0 mt-2">
              <li>System automatically syncs attendance every 5 minutes</li>
              <li>No manual action required - attendance appears automatically</li>
              <li>You can also manually sync using the "Sync Attendance Now" button above</li>
              <li>For real-time sync (instant), configure Push SDK on your device (see below)</li>
            </ul>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Push SDK Configuration -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">
        <i class="fa fa-cloud-upload"></i> Push SDK Configuration
        <small class="text-muted">(Real-time Attendance Collection)</small>
      </h3>
      <div class="tile-body">
        <div class="alert alert-info">
          <h5><i class="fa fa-info-circle"></i> Configure Device for Real-time Attendance</h5>
          <p>To enable automatic attendance collection when staff scan their fingerprint, configure your ZKTeco device with the following Push SDK settings:</p>
          
          <div class="mt-3">
            <strong>Push Server URL:</strong>
            <div class="input-group mt-2">
              <input type="text" class="form-control" id="pushServerUrl" 
                     value="{{ request()->getSchemeAndHttpHost() . '/api/iclock' }}" readonly>
              <div class="input-group-append">
                <button class="btn btn-secondary" type="button" onclick="copyPushUrl()">
                  <i class="fa fa-copy"></i> Copy
                </button>
              </div>
            </div>
            <small class="text-muted">
              <i class="fa fa-lightbulb-o"></i> 
              Copy this URL and configure it in your device: <strong>System → Communication → Push Server</strong>
            </small>
          </div>

          <div class="mt-3">
            <strong>How it works:</strong>
            <ul class="mt-2">
              <li><strong>First fingerprint scan</strong> of the day = <span class="badge badge-success">Check In</span></li>
              <li><strong>Second fingerprint scan</strong> of the day = <span class="badge badge-info">Check Out</span></li>
              <li><strong>Real-time mode (Push SDK):</strong> Attendance is automatically recorded when staff scan their fingerprint</li>
              <li><strong>Manual sync mode:</strong> If Push SDK is not configured, use the "Sync Attendance" button to manually pull attendance from the device</li>
            </ul>
          </div>

          <div class="mt-3">
            <strong>Device Configuration Steps:</strong>
            <ol class="mt-2">
              <li>On your ZKTeco device, go to <strong>System → Communication</strong></li>
              <li>Enable <strong>Push SDK</strong> or <strong>ADMS Protocol</strong></li>
              <li>Set <strong>Push Server URL</strong> to: <code>{{ url('/api/iclock') }}</code></li>
              <li>Set <strong>Push Interval</strong> to <code>1</code> (1 second) for real-time updates</li>
              <li>Save settings and restart the device if required</li>
              <li>Test by scanning a registered staff member's fingerprint</li>
            </ol>
          </div>

          <div class="mt-3 alert alert-warning">
            <strong><i class="fa fa-exclamation-triangle"></i> Important:</strong>
            <ul class="mb-0 mt-2">
              <li>Ensure your device is connected to the same network as this server</li>
              <li>The server must be accessible from the device's network</li>
              <li>If using a firewall, allow connections to port <code>8000</code> (or your configured port)</li>
              <li><strong>CRITICAL:</strong> Use the server's actual IP address (e.g., <code>http://192.168.100.106:8000/api/iclock</code>), NOT <code>localhost</code> or <code>127.0.0.1</code></li>
              <li>To find your server IP: Check the URL in your browser (e.g., <code>http://192.168.100.106:8000</code>)</li>
              <li><strong>If Push SDK doesn't work:</strong> You can manually sync attendance using the "Sync Attendance" button above</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Staff Registration Status -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">
        Staff Registration Status
        <span class="badge badge-info ml-2">{{ $registeredCount }} Registered</span>
      </h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Staff Name</th>
                <th>Staff ID</th>
                <th>Email</th>
                <th>Enroll ID</th>
                <th>Status</th>
                <th>Registered At</th>
                <th>Last Sync</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($staff as $index => $s)
                <tr data-staff-id="{{ $s->id }}">
                  <td>{{ $index + 1 }}</td>
                  <td><strong>{{ $s->full_name }}</strong></td>
                  <td>{{ $s->staff_id }}</td>
                  <td>{{ $s->email }}</td>
                  <td>
                    @if($s->biometricMapping)
                      <span class="badge badge-primary">{{ $s->biometricMapping->enroll_id }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($s->biometricMapping && $s->biometricMapping->is_registered)
                      <span class="badge badge-success">
                        <i class="fa fa-check-circle"></i> Registered
                      </span>
                    @else
                      <span class="badge badge-secondary">
                        <i class="fa fa-times-circle"></i> Not Registered
                      </span>
                    @endif
                  </td>
                  <td>
                    @if($s->biometricMapping && $s->biometricMapping->registered_at)
                      {{ $s->biometricMapping->registered_at->format('M d, Y H:i') }}
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($s->biometricMapping && $s->biometricMapping->last_sync_at)
                      {{ $s->biometricMapping->last_sync_at->diffForHumans() }}
                    @else
                      <span class="text-muted">Never</span>
                    @endif
                  </td>
                  <td>
                    @if($s->biometricMapping && $s->biometricMapping->is_registered)
                      <button class="btn btn-sm btn-danger unregister-btn" data-staff-id="{{ $s->id }}" data-staff-name="{{ $s->full_name }}">
                        <i class="fa fa-trash"></i> Unregister
                      </button>
                    @else
                      <button class="btn btn-sm btn-primary register-btn" data-staff-id="{{ $s->id }}" data-staff-name="{{ $s->full_name }}">
                        <i class="fa fa-fingerprint"></i> Register
                      </button>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="9" class="text-center text-muted">No staff members found</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Register Staff Modal -->
<div class="modal fade" id="registerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Register Staff to Device</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form id="registerForm">
        @csrf
        <div class="modal-body">
          <input type="hidden" name="staff_id" id="register_staff_id">
          <div class="form-group">
            <label>Staff Name</label>
            <input type="text" class="form-control" id="register_staff_name" readonly>
          </div>
          <div class="form-group">
            <label>Enroll ID (PIN) *</label>
            <input type="number" name="enroll_id" id="register_enroll_id" class="form-control" placeholder="Auto-generated from Staff ID" min="1" max="65535" required>
            <small class="text-muted">
              <strong>This is the primary identifier for the staff on the device.</strong><br>
              Auto-generated from Staff ID (numeric suffix). Must be between 1-65535.<br>
              Example: Staff ID "STF2025120001" → Enroll ID "1" (last digits)
            </small>
          </div>
          <div class="form-group">
            <label>Device IP *</label>
            <input type="text" name="device_ip" id="register_device_ip" class="form-control" value="{{ $deviceIp }}" required>
          </div>
          <div class="form-group">
            <label>Device Port *</label>
            <input type="number" name="device_port" id="register_device_port" class="form-control" value="{{ $devicePort }}" required>
          </div>
          <div class="form-group">
            <label>Comm Key</label>
            <input type="number" name="password" id="register_password" class="form-control" value="0">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-fingerprint"></i> Register to Device
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Test Connection
    $('#testConnectionForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');
        
        $.ajax({
            url: '{{ route("hr.biometric-devices.test-connection") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    let html = '<div class="alert alert-success">' +
                        '<i class="fa fa-check-circle"></i> <strong>Connection successful!</strong><br>';
                    
                    if (response.device_info) {
                        if (response.device_info.name) html += '<strong>Device:</strong> ' + response.device_info.name + '<br>';
                        if (response.device_info.serial) html += '<strong>Serial:</strong> ' + response.device_info.serial + '<br>';
                        if (response.device_info.version) html += '<strong>Version:</strong> ' + response.device_info.version + '<br>';
                        if (response.device_info.users_count !== undefined) html += '<strong>Users on Device:</strong> ' + response.device_info.users_count + '<br>';
                    }
                    
                    html += '</div>';
                    $('#connectionResult').html(html);
                } else {
                    let html = '<div class="alert alert-danger">' +
                        '<i class="fa fa-times-circle"></i> <strong>Connection Failed</strong><br>' +
                        '<p>' + response.message + '</p>';
                    
                    if (response.troubleshooting && response.troubleshooting.length > 0) {
                        html += '<hr><strong>Troubleshooting Steps:</strong><ul class="mb-0">';
                        response.troubleshooting.forEach(function(step) {
                            html += '<li>' + step + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    if (response.errors && response.errors.length > 0) {
                        html += '<hr><strong>Additional Errors:</strong><ul class="mb-0">';
                        response.errors.forEach(function(error) {
                            html += '<li class="text-warning">' + error + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    html += '</div>';
                    $('#connectionResult').html(html);
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Connection failed';
                $('#connectionResult').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fa fa-times-circle"></i> ' + error +
                    '</div>'
                );
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // Register Staff
    $('.register-btn').on('click', function() {
        const staffId = $(this).data('staff-id');
        const staffName = $(this).data('staff-name');
        const staffIdValue = $(this).closest('tr').find('td:eq(2)').text().trim(); // Get staff_id from table
        
        $('#register_staff_id').val(staffId);
        $('#register_staff_name').val(staffName);
        
        // Auto-generate enroll_id from staff_id (extract numeric suffix)
        // Example: STF2025120001 -> 1 (last 4 digits)
        let enrollId = '';
        if (staffIdValue) {
            const numericMatch = staffIdValue.match(/(\d{1,4})$/);
            if (numericMatch) {
                enrollId = parseInt(numericMatch[1]);
            }
        }
        $('#register_enroll_id').val(enrollId);
        
        $('#register_device_ip').val($('#device_ip').val());
        $('#register_device_port').val($('#device_port').val());
        $('#register_password').val($('#device_password').val());
        
        $('#registerModal').modal('show');
    });
    
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        const staffId = $('#register_staff_id').val();
        const btn = $(this).find('button[type="submit"]');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Registering...');
        
        $.ajax({
            url: '{{ route("hr.biometric-devices.register-staff", ":id") }}'.replace(':id', staffId),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                    btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Registration failed';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
    
    // Unregister Staff
    $('.unregister-btn').on('click', function() {
        const staffId = $(this).data('staff-id');
        const staffName = $(this).data('staff-name');
        
        Swal.fire({
            title: 'Unregister Staff?',
            html: `Are you sure you want to unregister <strong>${staffName}</strong> from the biometric device?<br><br>This will remove their fingerprint registration from the device.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Unregister',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("hr.biometric-devices.unregister-staff", ":id") }}'.replace(':id', staffId),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.message || 'Unregistration failed';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error
                        });
                    }
                });
            }
        });
    });
    
    // Sync Attendance
    $('#syncAttendanceBtn').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');
        
        $.ajax({
            url: '{{ route("hr.biometric-devices.sync-attendance") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                device_ip: $('#device_ip').val(),
                device_port: $('#device_port').val(),
                password: $('#device_password').val(),
                date: '' // Empty = sync all dates
            },
            success: function(response) {
                if (response.success) {
                    let html = `Synced <strong>${response.synced}</strong> attendance records.`;
                    
                    if (response.errors > 0) {
                        html += `<br><br><span class="text-warning"><strong>${response.errors} errors occurred.</strong></span>`;
                        
                        if (response.failed_enroll_ids && response.failed_enroll_ids.length > 0) {
                            html += `<br><br><span class="text-info"><strong>Note:</strong> Found attendance records from old/deleted users:</span><br>`;
                            html += `<code>${response.failed_enroll_ids.join(', ')}</code>`;
                            html += `<br><br><small>These are attendance records from users that were deleted from the device.</small>`;
                            html += `<br><small>Registered enroll_ids: <code>${response.registered_enroll_ids.join(', ')}</code></small>`;
                            if (response.device_users && response.device_users.length > 0) {
                                html += `<br><small>Current users on device: <code>${response.device_users.map(u => u.uid).join(', ')}</code></small>`;
                            }
                            html += `<br><br><small><strong>Solution:</strong> These old records are ignored. To remove them, clear attendance logs on the device (System → Data Management → Clear Attendance Logs).</small>`;
                        }
                    }
                    
                    Swal.fire({
                        icon: response.synced > 0 ? 'success' : 'warning',
                        title: 'Sync Complete!',
                        html: html,
                        width: '600px',
                        timer: response.errors > 0 ? 8000 : 3000,
                        showConfirmButton: true
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Sync Failed',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Sync failed';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});

// Copy Push SDK URL to clipboard
function copyPushUrl() {
    const urlInput = document.getElementById('pushServerUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Push SDK URL copied to clipboard',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (err) {
        // Fallback for modern browsers
        navigator.clipboard.writeText(urlInput.value).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Push SDK URL copied to clipboard',
                timer: 2000,
                showConfirmButton: false
            });
        }, function(err) {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: 'Could not copy URL. Please copy manually.'
            });
        });
    }
}
</script>
@endpush
@endsection

