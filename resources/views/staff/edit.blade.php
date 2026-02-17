@extends('layouts.dashboard')

@section('title', 'Edit Staff')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-edit"></i> Edit Staff Member</h1>
    <p>Update staff member information</p>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <form action="{{ route('staff.update', $staff->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('full_name') is-invalid @enderror" 
                     name="full_name" value="{{ old('full_name', $staff->full_name) }}" required>
              @error('full_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" 
                     name="email" value="{{ old('email', $staff->email) }}" required>
              @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Gender <span class="text-danger">*</span></label>
              <select class="form-control @error('gender') is-invalid @enderror" name="gender" required>
                <option value="">Select Gender</option>
                <option value="male" {{ old('gender', $staff->gender) == 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $staff->gender) == 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender', $staff->gender) == 'other' ? 'selected' : '' }}>Other</option>
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
                     name="phone_number" value="{{ old('phone_number', $staff->phone_number) }}" required>
              @error('phone_number')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>NIDA (Optional)</label>
              <input type="text" class="form-control @error('nida') is-invalid @enderror" 
                     name="nida" value="{{ old('nida', $staff->nida) }}">
              @error('nida')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>Location/Branch (Optional)</label>
              <input type="text" class="form-control @error('location_branch') is-invalid @enderror" 
                     name="location_branch" value="{{ old('location_branch', $staff->location_branch) }}" 
                     placeholder="e.g., Main Branch, Branch 2">
              @error('location_branch')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Next of Kin (Optional)</label>
              <input type="text" class="form-control @error('next_of_kin') is-invalid @enderror" 
                     name="next_of_kin" value="{{ old('next_of_kin', $staff->next_of_kin) }}">
              @error('next_of_kin')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Next of Kin Phone (Optional)</label>
              <input type="text" class="form-control @error('next_of_kin_phone') is-invalid @enderror" 
                     name="next_of_kin_phone" value="{{ old('next_of_kin_phone', $staff->next_of_kin_phone) }}">
              @error('next_of_kin_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Business Type</label>
              <select class="form-control @error('business_type_id') is-invalid @enderror" 
                      name="business_type_id" id="business_type_id">
                <option value="">Select Business Type</option>
                @if(isset($businessTypes) && $businessTypes->count() > 0)
                  @foreach($businessTypes as $businessType)
                    <option value="{{ $businessType->id }}" {{ old('business_type_id', $staff->business_type_id) == $businessType->id ? 'selected' : '' }}>
                      {{ $businessType->name }}
                    </option>
                  @endforeach
                @endif
              </select>
              @error('business_type_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Role <span class="text-danger">*</span></label>
              <select class="form-control @error('role_id') is-invalid @enderror" name="role_id" id="role_id" required>
                <option value="">Select Business Type First</option>
                @if($roles->count() > 0)
                  @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $staff->role_id) == $role->id ? 'selected' : '' }}>
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

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Salary Paid (TSh) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" class="form-control @error('salary_paid') is-invalid @enderror" 
                     name="salary_paid" value="{{ old('salary_paid', $staff->salary_paid) }}" required min="0">
              @error('salary_paid')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="form-group">
              <label>Religion (Optional)</label>
              <input type="text" class="form-control @error('religion') is-invalid @enderror" 
                     name="religion" value="{{ old('religion', $staff->religion) }}">
              @error('religion')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Status</label>
              <select class="form-control @error('is_active') is-invalid @enderror" name="is_active">
                <option value="1" {{ old('is_active', $staff->is_active) == 1 ? 'selected' : '' }}>Active</option>
                <option value="0" {{ old('is_active', $staff->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
              </select>
              @error('is_active')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <hr class="my-4">

        <h5 class="mb-3"><i class="fa fa-paperclip"></i> Attachments (Optional)</h5>
        
        @if($staff->nida_attachment || $staff->voter_id_attachment || $staff->professional_certificate_attachment)
        <div class="alert alert-info mb-3">
          <strong>Current Attachments:</strong>
          <ul class="mb-0 mt-2">
            @if($staff->nida_attachment)
              <li>NIDA Document: <a href="{{ Storage::url($staff->nida_attachment) }}" target="_blank">View</a></li>
            @endif
            @if($staff->voter_id_attachment)
              <li>Voter ID: <a href="{{ Storage::url($staff->voter_id_attachment) }}" target="_blank">View</a></li>
            @endif
            @if($staff->professional_certificate_attachment)
              <li>Professional Certificate: <a href="{{ Storage::url($staff->professional_certificate_attachment) }}" target="_blank">View</a></li>
            @endif
          </ul>
          <small>Upload new files to replace existing ones.</small>
        </div>
        @endif

        <div class="form-check mb-3">
          <input type="checkbox" class="form-check-input" id="enable_attachments">
          <label class="form-check-label" for="enable_attachments">
            Update attachments for this staff member
          </label>
        </div>

        <div id="attachments-section" style="display: none;">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>NIDA Document</label>
                <div class="custom-file">
                  <input type="file" class="custom-file-input @error('nida_attachment') is-invalid @enderror"
                         id="nida_attachment" name="nida_attachment" accept=".pdf,.jpg,.jpeg,.png">
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
                  <input type="file" class="custom-file-input @error('voter_id_attachment') is-invalid @enderror"
                         id="voter_id_attachment" name="voter_id_attachment" accept=".pdf,.jpg,.jpeg,.png">
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
                  <input type="file" class="custom-file-input @error('professional_certificate_attachment') is-invalid @enderror"
                         id="professional_certificate_attachment" name="professional_certificate_attachment" accept=".pdf,.jpg,.jpeg,.png">
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

        <div class="mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Update Staff
          </button>
          <a href="{{ route('staff.show', $staff->id) }}" class="btn btn-info">
            <i class="fa fa-eye"></i> View Details
          </a>
          <a href="{{ route('staff.index') }}" class="btn btn-secondary">
            <i class="fa fa-times"></i> Cancel
          </a>
        </div>
      </form>
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

    var hasAttachmentError = document.querySelector(
      'input[name="nida_attachment"].is-invalid,' +
      'input[name="voter_id_attachment"].is-invalid,' +
      'input[name="professional_certificate_attachment"].is-invalid'
    );

    if (hasAttachmentError) {
      checkbox.checked = true;
      section.style.display = 'block';
    }

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
      var currentRoleId = {{ $staff->role_id ?? 'null' }};
      
      // Store initial roles HTML as fallback
      var initialRolesHTML = roleSelect.innerHTML;
      
      businessTypeSelect.addEventListener('change', function() {
        var businessTypeId = this.value;
        
        if (!businessTypeId) {
          // Restore initial roles if business type is cleared
          roleSelect.innerHTML = initialRolesHTML;
          roleSelect.disabled = false;
          return;
        }
        
        // Show loading state
        var loadingOption = document.createElement('option');
        loadingOption.textContent = 'Loading roles...';
        loadingOption.disabled = true;
        roleSelect.innerHTML = '';
        roleSelect.appendChild(loadingOption);
        roleSelect.disabled = true;
        
        // Fetch roles for selected business type
        var url = '{{ route("staff.roles-by-business-type") }}?business_type_id=' + businessTypeId;
        console.log('Fetching roles from:', url);
        
        fetch(url, {
          method: 'GET',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          credentials: 'same-origin'
        })
        .then(response => {
          console.log('Response status:', response.status);
          if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
          }
          return response.json();
        })
        .then(data => {
          console.log('Received data:', data);
          roleSelect.innerHTML = '<option value="">Select Role</option>';
          
          if (data.roles && data.roles.length > 0) {
            console.log('Found ' + data.roles.length + ' roles');
            data.roles.forEach(function(role) {
              var option = document.createElement('option');
              option.value = role.id;
              option.textContent = role.name + (role.description ? ' - ' + role.description : '');
              // Preserve current role selection
              if (role.id == currentRoleId) {
                option.selected = true;
              }
              roleSelect.appendChild(option);
            });
          } else {
            console.log('No roles found in response');
            // If no roles from API, restore initial roles as fallback
            roleSelect.innerHTML = initialRolesHTML;
          }
          
          roleSelect.disabled = false;
        })
        .catch(error => {
          console.error('Error loading roles:', error);
          // On error, restore initial roles as fallback
          roleSelect.innerHTML = initialRolesHTML;
          roleSelect.disabled = false;
        });
      });
      
      // Don't auto-trigger on page load - let user see initial roles first
      // Only reload if they change the business type
    }
  });
</script>
@endpush

