@extends('layouts.dashboard')

@section('title', 'Staff Management')

@push('styles')
<style>
  .badge-pill { border-radius: 50px; }
  .table-hover tbody tr:hover { 
    background-color: rgba(148, 0, 0, 0.05); 
    transition: background 0.3s ease;
  }
  .align-middle td { vertical-align: middle !important; }
  .search-box .form-control:focus {
    border-color: #940000;
    box-shadow: 0 0 0 0.2rem rgba(148, 0, 0, 0.25);
  }
</style>
@endpush

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> Staff Management</h1>
    <p>
      @if(session('active_location'))
        Viewing staff for branch: <strong>{{ session('active_location') }}</strong>
      @else
        Manage all staff members
      @endif
    </p>
  </div>
  <div>
    @if(session('active_location'))
      <a href="javascript:void(0)" onclick="switchLocation('all')" class="btn btn-secondary mr-2">
        <i class="fa fa-globe"></i> Show All Branches
      </a>
    @endif
    <a href="{{ route('staff.create') }}" class="btn btn-primary">
      <i class="fa fa-plus"></i> Register New Staff
    </a>
  </div>
</div>
<!-- Statistics Cards -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-users fa-3x"></i>
      <div class="info">
        <h4>Total Staff</h4>
        <p><b>{{ $stats['total'] }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-check-circle fa-3x"></i>
      <div class="info">
        <h4>Active</h4>
        <p><b>{{ $stats['active'] }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Payroll (MTD)</h4>
        <p><b>{{ number_format($stats['total_salary'], 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-map-marker fa-3x"></i>
      <div class="info">
        <h4>Branches</h4>
        <p><b>{{ $stats['branches'] }}</b></p>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title mb-0">Staff List</h3>
        <div class="search-box">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text bg-white border-right-0"><i class="fa fa-search text-muted"></i></span>
            </div>
            <input type="text" id="staffSearch" class="form-control border-left-0" placeholder="Search staff members..." style="width: 250px;">
          </div>
        </div>
      </div>
      @if($staff->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover table-bordered bg-white">
            <thead class="bg-light">
              <tr>
                <th width="120">Staff ID</th>
                <th>Full Name</th>
                <th>Staff Role</th>
                <th>Location</th>
                <th width="100">Status</th>
                <th width="120" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($staff as $member)
                <tr class="align-middle">
                  <td><strong>{{ $member->staff_id }}</strong></td>
                  <td>
                    <div class="d-flex align-items-center">
                      <img src="https://ui-avatars.com/api/?name={{ urlencode($member->full_name) }}&background=E9ECEF&color=940000&size=32" class="rounded-circle mr-2" alt="Avatar">
                      <div>
                        <strong>{{ $member->full_name }}</strong><br>
                        <small class="text-muted">{{ $member->phone_number }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    @if($member->role)
                      <span class="badge badge-info badge-pill px-3 py-1">{{ $member->role->name }}</span>
                    @else
                      <span class="badge badge-secondary badge-pill px-3 py-1">No Role</span>
                    @endif
                  </td>
                  <td>
                    <span class="text-secondary">
                      <i class="fa fa-map-marker text-danger mr-1"></i> {{ $member->location_branch ?? 'Main' }}
                    </span>
                  </td>
                  <td>
                    @if($member->is_active)
                      <span class="badge badge-success badge-pill px-3 py-1">Active</span>
                    @else
                      <span class="badge badge-danger badge-pill px-3 py-1">Inactive</span>
                    @endif
                  </td>
                  <td class="text-center">
                    <div class="btn-group" role="group">
                      <a href="{{ route('staff.show', $member->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                        <i class="fa fa-eye"></i>
                      </a>
                      <a href="{{ route('staff.edit', $member->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="fa fa-edit"></i>
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteStaff({{ $member->id }}, '{{ $member->full_name }}')">
                        <i class="fa fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-5">
          <i class="fa fa-users fa-3x text-muted mb-3"></i>
          <p class="text-muted">No staff members registered yet.</p>
          <a href="{{ route('staff.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Register First Staff Member
          </a>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function deleteStaff(staffId, staffName) {
  Swal.fire({
    title: 'Delete Staff Member?',
    html: `Are you sure you want to delete <strong>${staffName}</strong>?<br><br>This action cannot be undone.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      // Create a form and submit it
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = `/staff/${staffId}`;
      
      // Add CSRF token
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = '_token';
      csrfInput.value = '{{ csrf_token() }}';
      form.appendChild(csrfInput);
      
      // Add method spoofing for DELETE
      const methodInput = document.createElement('input');
      methodInput.type = 'hidden';
      methodInput.name = '_method';
      methodInput.value = 'DELETE';
      form.appendChild(methodInput);
      
      document.body.appendChild(form);
      form.submit();
    }
  });
}
</script>
@endpush




