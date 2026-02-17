@extends('layouts.dashboard')

@section('title', 'Register Staff')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-user-plus"></i> Register New Staff</h1>
    <p>Add a new staff member to your business</p>
  </div>
</div>

@if($roles->count() == 0)
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-warning">
      <i class="fa fa-exclamation-triangle"></i> <strong>No Roles Available!</strong>
      <p class="mb-0">You need to create roles in your Business Configuration before registering staff. 
      <a href="{{ route('business-configuration.edit') }}" class="alert-link">Click here to create roles</a>.</p>
    </div>
  </div>
</div>
@endif

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      @if($roles->count() > 0)
      <form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                     name="full_name" value="{{ old('full_name') }}" required>
              @error('full_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" 
                     name="email" value="{{ old('email') }}" required>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row: Gender & Phone -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Gender <span class="text-danger">*</span></label>
              <select class="form-control @error('gender') is-invalid @enderror" name="gender" required>
                <option value="">Select Gender</option>
                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
              </select>
              @error('gender')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Phone Number <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                     name="phone_number" value="{{ old('phone_number', '+255') }}" required>
              @error('phone_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">Format: +255710490428</small>
            </div>
          </div>
        </div>

        <!-- Row: NIDA & Location -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>NIDA (Optional)</label>
              <input type="text" class="form-control @error('nida') is-invalid @enderror" 
                     name="nida" value="{{ old('nida') }}">
              @error('nida')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>Location/Branch (Optional)</label>
              <input type="text" class="form-control @error('location_branch') is-invalid @enderror" 
                     name="location_branch" value="{{ old('location_branch') }}" 
                     placeholder="e.g., Main Branch, Branch 2">
              @error('location_branch')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row: Next of Kin & Next of Kin Phone -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Next of Kin (Optional)</label>
              <input type="text" class="form-control @error('next_of_kin') is-invalid @enderror" 
                     name="next_of_kin" value="{{ old('next_of_kin') }}">
              @error('next_of_kin')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Next of Kin Phone (Optional)</label>
              <input type="text" class="form-control @error('next_of_kin_phone') is-invalid @enderror" 
                     name="next_of_kin_phone" value="{{ old('next_of_kin_phone', '+255') }}">
              @error('next_of_kin_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row: Business Type & Role -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Business Type <span class="text-danger">*</span></label>
              <select class="form-control @error('business_type_id') is-invalid @enderror" 
                      name="business_type_id" id="business_type_id" required>
                <option value="">Select Business Type</option>
                @if(isset($businessTypes) && $businessTypes->count() > 0)
                  @foreach($businessTypes as $businessType)
                    <option value="{{ $businessType->id }}" {{ old('business_type_id') == $businessType->id ? 'selected' : '' }}>
                      {{ $businessType->name }}
                    </option>
                  @endforeach
                @else
                  <option value="" disabled>No business types configured. Please configure your business types first.</option>
                @endif
              </select>
              @error('business_type_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">
                <i class="fa fa-info-circle"></i> Select the business type this staff member will work in.
              </small>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Role <span class="text-danger">*</span></label>
              <select class="form-control @error('role_id') is-invalid @enderror" name="role_id" id="role_id" required>
                <option value="">Select Business Type First</option>
                @if($roles->count() > 0)
                  @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                      {{ $role->name }}
                      @if($role->description)
                        - {{ $role->description }}
                      @endif
                    </option>
                  @endforeach
                @else
                  <option value="" disabled>No roles available. Please create roles in Business Configuration first.</option>
                @endif
              </select>
              @error('role_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">
                <i class="fa fa-info-circle"></i> Roles will be filtered based on the selected business type.
              </small>
            </div>
          </div>
        </div>

        <!-- Row: Salary -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Salary Paid (TSh) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control @error('salary_paid') is-invalid @enderror" 
                     name="salary_paid" value="{{ old('salary_paid') }}" required min="0">
              @error('salary_paid')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Row: Religion -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Religion (Optional)</label>
              <input type="text" class="form-control @error('religion') is-invalid @enderror" 
                     name="religion" value="{{ old('religion') }}">
              @error('religion')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <hr class="my-4">

        <h5 class="mb-3"><i class="fa fa-paperclip"></i> Attachments (Optional)</h5>

        <div class="form-check mb-3">
          <input type="checkbox" class="form-check-input" id="enable_attachments">
          <label class="form-check-label" for="enable_attachments">
            Add attachments for this staff member
          </label>
        </div>

        <div id="attachments-section" style="display: none;">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>NIDA Document</label>
                <div class="custom-file">
                  <input
                    type="file"
                    class="custom-file-input @error('nida_attachment') is-invalid @enderror"
                    id="nida_attachment"
                    name="nida_attachment"
                    accept=".pdf,.jpg,.jpeg,.png"
                  >
                  <label class="custom-file-label" for="nida_attachment">Choose file...</label>
                  @error('nida_attachment')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <small class="form-text text-muted">PDF, JPG, PNG (Max 5MB)</small>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="form-group">
                <label>Voter ID</label>
                <div class="custom-file">
                  <input
                    type="file"
                    class="custom-file-input @error('voter_id_attachment') is-invalid @enderror"
                    id="voter_id_attachment"
                    name="voter_id_attachment"
                    accept=".pdf,.jpg,.jpeg,.png"
                  >
                  <label class="custom-file-label" for="voter_id_attachment">Choose file...</label>
                  @error('voter_id_attachment')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <small class="form-text text-muted">PDF, JPG, PNG (Max 5MB)</small>
              </div>
            </div>
            
            <div class="col-md-4">
              <div class="form-group">
                <label>Professional Certificate</label>
                <div class="custom-file">
                  <input
                    type="file"
                    class="custom-file-input @error('professional_certificate_attachment') is-invalid @enderror"
                    id="professional_certificate_attachment"
                    name="professional_certificate_attachment"
                    accept=".pdf,.jpg,.jpeg,.png"
                  >
                  <label class="custom-file-label" for="professional_certificate_attachment">Choose file...</label>
                  @error('professional_certificate_attachment')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <small class="form-text text-muted">PDF, JPG, PNG (Max 5MB)</small>
              </div>
            </div>
          </div>
        </div>

        <div class="alert alert-info mt-3">
          <i class="fa fa-info-circle"></i> <strong>Note:</strong> Password will be automatically generated from the staff's last name (in uppercase). SMS with credentials will be sent to the staff's phone number.
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Register Staff
          </button>
          <a href="{{ route('staff.index') }}" class="btn btn-secondary">
            <i class="fa fa-times"></i> Cancel
          </a>
        </div>
      </form>
      @else
      <div class="text-center py-5">
        <i class="fa fa-user-plus fa-3x text-muted mb-3"></i>
        <h4>No Roles Available</h4>
        <p class="text-muted">You need to create roles in your Business Configuration before registering staff members.</p>
        <a href="{{ route('business-configuration.edit') }}" class="btn btn-primary">
          <i class="fa fa-cog"></i> Go to Business Configuration
        </a>
      </div>
      @endif
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var checkbox = document.getElementById('enable_attachments');
    var section = document.getElementById('attachments-section');

    if (!checkbox || !section) return;

    checkbox.addEventListener('change', function () {
      section.style.display = this.checked ? 'block' : 'none';
    });

    // Auto-open if there were validation errors on attachments
    var hasAttachmentError = document.querySelector(
      'input[name="nida_attachment"].is-invalid,' +
      'input[name="voter_id_attachment"].is-invalid,' +
      'input[name="professional_certificate_attachment"].is-invalid'
    );

    if (hasAttachmentError) {
      checkbox.checked = true;
      section.style.display = 'block';
    }

    // Update custom file input labels with selected file name
    var fileInputs = document.querySelectorAll('#attachments-section .custom-file-input');
    fileInputs.forEach(function (input) {
      input.addEventListener('change', function () {
        var fileName = this.files && this.files.length > 0 ? this.files[0].name : 'Choose file...';
        var label = this.nextElementSibling;
        if (label && label.classList.contains('custom-file-label')) {
          label.textContent = fileName;
        }
      });
    });

    // Business Type and Role dynamic loading
    var businessTypeSelect = document.getElementById('business_type_id');
    var roleSelect = document.getElementById('role_id');
    
    if (businessTypeSelect && roleSelect) {
      businessTypeSelect.addEventListener('change', function() {
        var businessTypeId = this.value;
        
        // Clear and disable role select
        roleSelect.innerHTML = '<option value="">Loading roles...</option>';
        roleSelect.disabled = true;
        
        if (!businessTypeId) {
          roleSelect.innerHTML = '<option value="">Select Business Type First</option>';
          roleSelect.disabled = false;
          return;
        }
        
        // Fetch roles for selected business type
        fetch('{{ route("staff.roles-by-business-type") }}?business_type_id=' + businessTypeId, {
          method: 'GET',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
          }
        })
        .then(response => {
          if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          roleSelect.innerHTML = '<option value="">Select Role</option>';
          
          if (data.error) {
            console.error('Server error:', data.error);
            roleSelect.innerHTML = '<option value="">Error: ' + data.error + '</option>';
          } else if (data.roles && data.roles.length > 0) {
            data.roles.forEach(function(role) {
              var option = document.createElement('option');
              option.value = role.id;
              option.textContent = role.name + (role.description ? ' - ' + role.description : '');
              roleSelect.appendChild(option);
            });
          } else {
            roleSelect.innerHTML = '<option value="">No roles available for this business type</option>';
          }
          
          roleSelect.disabled = false;
        })
        .catch(error => {
          console.error('Error loading roles:', error);
          roleSelect.innerHTML = '<option value="">Error loading roles. Please refresh the page and try again.</option>';
          roleSelect.disabled = false;
        });
      });
      
      // Trigger change if business type is pre-selected (from old input)
      if (businessTypeSelect.value) {
        businessTypeSelect.dispatchEvent(new Event('change'));
      }
    }
  });
</script>
@endpush
