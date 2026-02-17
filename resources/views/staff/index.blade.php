@extends('layouts.dashboard')

@section('title', 'Staff Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> Staff Management</h1>
    <p>Manage your staff members</p>
  </div>
  <div>
    <a href="{{ route('staff.create') }}" class="btn btn-primary">
      <i class="fa fa-plus"></i> Register New Staff
    </a>
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

      @if($staff->count() > 0)
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Staff ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Role</th>
                <th>Salary (TSh)</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($staff as $member)
                <tr>
                  <td><strong>{{ $member->staff_id }}</strong></td>
                  <td>{{ $member->full_name }}</td>
                  <td>{{ $member->email }}</td>
                  <td>{{ $member->phone_number }}</td>
                  <td>{{ ucfirst($member->gender) }}</td>
                  <td>
                    @if($member->role)
                      <span class="badge badge-info">{{ $member->role->name }}</span>
                    @else
                      <span class="badge badge-secondary">No Role</span>
                    @endif
                  </td>
                  <td>{{ number_format($member->salary_paid, 2) }}</td>
                  <td>
                    @if($member->is_active)
                      <span class="badge badge-success">Active</span>
                    @else
                      <span class="badge badge-danger">Inactive</span>
                    @endif
                  </td>
                  <td>{{ $member->created_at->format('M d, Y') }}</td>
                  <td>
                    <div class="btn-group" role="group">
                      <a href="{{ route('staff.show', $member->id) }}" class="btn btn-sm btn-info" title="View Details">
                        <i class="fa fa-eye"></i>
                      </a>
                      <a href="{{ route('staff.edit', $member->id) }}" class="btn btn-sm btn-primary" title="Edit">
                        <i class="fa fa-edit"></i>
                      </a>
                      <button type="button" class="btn btn-sm btn-danger" title="Delete" onclick="deleteStaff({{ $member->id }}, '{{ $member->full_name }}')">
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




