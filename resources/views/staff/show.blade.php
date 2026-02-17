@extends('layouts.dashboard')

@section('title', 'Staff Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-user"></i> Staff Details</h1>
    <p>View staff member information</p>
  </div>
  <div>
    <a href="{{ route('staff.index') }}" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Back to List
    </a>
    <a href="{{ route('staff.edit', $staff->id) }}" class="btn btn-primary">
      <i class="fa fa-edit"></i> Edit
    </a>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="row">
        <div class="col-md-6">
          <h4 class="mb-4"><i class="fa fa-user-circle"></i> Personal Information</h4>
          <table class="table table-borderless">
            <tr>
              <th width="40%">Staff ID:</th>
              <td><strong>{{ $staff->staff_id }}</strong></td>
            </tr>
            <tr>
              <th>Full Name:</th>
              <td>{{ $staff->full_name }}</td>
            </tr>
            <tr>
              <th>Email:</th>
              <td>{{ $staff->email }}</td>
            </tr>
            <tr>
              <th>Phone Number:</th>
              <td>{{ $staff->phone_number }}</td>
            </tr>
            <tr>
              <th>Gender:</th>
              <td>{{ ucfirst($staff->gender) }}</td>
            </tr>
            <tr>
              <th>NIDA:</th>
              <td>{{ $staff->nida ?? 'N/A' }}</td>
            </tr>
            <tr>
              <th>Religion:</th>
              <td>{{ $staff->religion ?? 'N/A' }}</td>
            </tr>
          </table>
        </div>
        
        <div class="col-md-6">
          <h4 class="mb-4"><i class="fa fa-briefcase"></i> Employment Information</h4>
          <table class="table table-borderless">
            <tr>
              <th width="40%">Role:</th>
              <td>
                @if($staff->role)
                  <span class="badge badge-info">{{ $staff->role->name }}</span>
                @else
                  <span class="badge badge-secondary">No Role</span>
                @endif
              </td>
            </tr>
            <tr>
              <th>Business Type:</th>
              <td>
                @if($staff->businessType)
                  <span class="badge badge-primary">{{ $staff->businessType->name }}</span>
                @else
                  <span class="badge badge-secondary">Not Assigned</span>
                @endif
              </td>
            </tr>
            <tr>
              <th>Salary (TSh):</th>
              <td><strong>{{ number_format($staff->salary_paid, 2) }}</strong></td>
            </tr>
            <tr>
              <th>Location/Branch:</th>
              <td>{{ $staff->location_branch ?? 'N/A' }}</td>
            </tr>
            <tr>
              <th>Status:</th>
              <td>
                @if($staff->is_active)
                  <span class="badge badge-success">Active</span>
                @else
                  <span class="badge badge-danger">Inactive</span>
                @endif
              </td>
            </tr>
            <tr>
              <th>Registered:</th>
              <td>{{ $staff->created_at->format('M d, Y') }}</td>
            </tr>
            <tr>
              <th>Last Login:</th>
              <td>{{ $staff->last_login_at ? $staff->last_login_at->format('M d, Y H:i') : 'Never' }}</td>
            </tr>
          </table>
        </div>
      </div>

      <hr>

      <div class="row">
        <div class="col-md-6">
          <h4 class="mb-4"><i class="fa fa-users"></i> Next of Kin</h4>
          <table class="table table-borderless">
            <tr>
              <th width="40%">Name:</th>
              <td>{{ $staff->next_of_kin ?? 'N/A' }}</td>
            </tr>
            <tr>
              <th>Phone:</th>
              <td>{{ $staff->next_of_kin_phone ?? 'N/A' }}</td>
            </tr>
          </table>
        </div>
        
        <div class="col-md-6">
          <h4 class="mb-4"><i class="fa fa-paperclip"></i> Attachments</h4>
          <table class="table table-borderless">
            <tr>
              <th width="40%">NIDA Document:</th>
              <td>
                @if($staff->nida_attachment)
                  <a href="{{ Storage::url($staff->nida_attachment) }}" target="_blank" class="btn btn-sm btn-info">
                    <i class="fa fa-download"></i> View Document
                  </a>
                @else
                  <span class="text-muted">Not uploaded</span>
                @endif
              </td>
            </tr>
            <tr>
              <th>Voter ID:</th>
              <td>
                @if($staff->voter_id_attachment)
                  <a href="{{ Storage::url($staff->voter_id_attachment) }}" target="_blank" class="btn btn-sm btn-info">
                    <i class="fa fa-download"></i> View Document
                  </a>
                @else
                  <span class="text-muted">Not uploaded</span>
                @endif
              </td>
            </tr>
            <tr>
              <th>Professional Certificate:</th>
              <td>
                @if($staff->professional_certificate_attachment)
                  <a href="{{ Storage::url($staff->professional_certificate_attachment) }}" target="_blank" class="btn btn-sm btn-info">
                    <i class="fa fa-download"></i> View Document
                  </a>
                @else
                  <span class="text-muted">Not uploaded</span>
                @endif
              </td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection








