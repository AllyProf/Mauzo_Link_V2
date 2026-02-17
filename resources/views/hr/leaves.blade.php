@extends('layouts.dashboard')

@section('title', 'Leave Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-calendar"></i> Leave Management</h1>
    <p>Manage staff leave requests</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('hr.dashboard') }}">HR</a></li>
    <li class="breadcrumb-item">Leaves</li>
  </ul>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('hr.leaves') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="status" class="mr-2">Status:</label>
          <select name="status" id="status" class="form-control">
            <option value="">All</option>
            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
          </select>
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
      </form>
    </div>
  </div>
</div>

<!-- Leaves Table -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Leave Requests</h3>
      <div class="tile-body">
        @if($leaves->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Type</th>
                  <th>Start Date</th>
                  <th>End Date</th>
                  <th>Days</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($leaves as $leave)
                <tr>
                  <td>{{ $leave->staff->full_name }}</td>
                  <td><span class="badge badge-info">{{ ucfirst($leave->leave_type) }}</span></td>
                  <td>{{ $leave->start_date->format('M d, Y') }}</td>
                  <td>{{ $leave->end_date->format('M d, Y') }}</td>
                  <td>{{ $leave->days }}</td>
                  <td>{{ Str::limit($leave->reason, 50) }}</td>
                  <td>
                    @if($leave->status === 'approved')
                      <span class="badge badge-success">Approved</span>
                    @elseif($leave->status === 'rejected')
                      <span class="badge badge-danger">Rejected</span>
                    @elseif($leave->status === 'pending')
                      <span class="badge badge-warning">Pending</span>
                    @else
                      <span class="badge badge-secondary">{{ ucfirst($leave->status) }}</span>
                    @endif
                  </td>
                  <td>
                    @if($leave->status === 'pending')
                      <button class="btn btn-sm btn-success approve-leave-btn" data-leave-id="{{ $leave->id }}">
                        <i class="fa fa-check"></i> Approve
                      </button>
                      <button class="btn btn-sm btn-danger reject-leave-btn" data-leave-id="{{ $leave->id }}">
                        <i class="fa fa-times"></i> Reject
                      </button>
                    @endif
                    <button class="btn btn-sm btn-info view-leave-btn" data-leave-id="{{ $leave->id }}">
                      <i class="fa fa-eye"></i> View
                    </button>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $leaves->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No leave requests found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // Approve leave
  $(document).on('click', '.approve-leave-btn', function() {
    const leaveId = $(this).data('leave-id');
    
    Swal.fire({
      title: 'Approve Leave?',
      text: 'Are you sure you want to approve this leave request?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Approve',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '{{ url("hr/leaves") }}/' + leaveId + '/update-status',
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            status: 'approved'
          },
          success: function(response) {
            if (response.success) {
              Swal.fire('Approved!', 'Leave request has been approved.', 'success').then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.error || 'Failed to approve leave', 'error');
          }
        });
      }
    });
  });

  // Reject leave
  $(document).on('click', '.reject-leave-btn', function() {
    const leaveId = $(this).data('leave-id');
    
    Swal.fire({
      title: 'Reject Leave?',
      text: 'Please provide a reason for rejection:',
      input: 'text',
      inputPlaceholder: 'Enter rejection reason',
      showCancelButton: true,
      confirmButtonText: 'Reject',
      cancelButtonText: 'Cancel',
      inputValidator: (value) => {
        if (!value) {
          return 'Please provide a rejection reason';
        }
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '{{ url("hr/leaves") }}/' + leaveId + '/update-status',
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            status: 'rejected',
            rejection_reason: result.value
          },
          success: function(response) {
            if (response.success) {
              Swal.fire('Rejected!', 'Leave request has been rejected.', 'success').then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            Swal.fire('Error', xhr.responseJSON?.error || 'Failed to reject leave', 'error');
          }
        });
      }
    });
  });
});
</script>
@endpush
@endsection

