@extends('layouts.dashboard')

@section('title', 'Edit Business Configuration')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-edit"></i> Edit Business Configuration</h1>
    <p>Update your business settings, types, and roles</p>
  </div>
</div>

@if(session('success'))
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fa fa-check-circle"></i> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  </div>
</div>
@endif

@if(session('error'))
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fa fa-exclamation-circle"></i> {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  </div>
</div>
@endif

@if($errors->any())
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fa fa-exclamation-circle"></i> <strong>Please fix the following errors:</strong>
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  </div>
</div>
@endif

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <form action="{{ route('business-configuration.update') }}" method="POST" id="businessConfigForm">
        @csrf
        
        <!-- Business Information -->
        <h3 class="mb-4"><i class="fa fa-building"></i> Business Information</h3>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Business Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="business_name" 
                     value="{{ old('business_name', $user->business_name) }}" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Phone <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="phone" 
                     value="{{ old('phone', $user->phone) }}" required>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label>Address <span class="text-danger">*</span></label>
              <textarea class="form-control" name="address" rows="2" required>{{ old('address', $user->address) }}</textarea>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>City <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="city" 
                     value="{{ old('city', $user->city) }}" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label>Country <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="country" 
                     value="{{ old('country', $user->country) }}" required>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <!-- Business Types -->
        <h3 class="mb-4"><i class="fa fa-tags"></i> Business Types</h3>
        <p class="text-muted mb-3">Select the business types that apply to your business. You can leave all unchecked if none apply.</p>
        <div class="row">
          @foreach($businessTypes as $businessType)
          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body text-center">
                <input type="checkbox" 
                       class="business-type-checkbox" 
                       name="business_types[]" 
                       value="{{ $businessType->id }}" 
                       id="business_type_{{ $businessType->id }}"
                       {{ in_array($businessType->id, $selectedTypes) ? 'checked' : '' }}>
                <label for="business_type_{{ $businessType->id }}" class="card-link" style="cursor: pointer; width: 100%;">
                  <i class="fa {{ $businessType->icon }} fa-3x mb-2" style="color: #940000;"></i>
                  <h5 class="card-title">{{ $businessType->name }}</h5>
                  <p class="card-text text-muted small">{{ $businessType->description }}</p>
                </label>
              </div>
            </div>
          </div>
          @endforeach
        </div>
        <div class="alert alert-info mt-3">
          <i class="fa fa-info-circle"></i> <strong>Note:</strong> If you uncheck all business types, only common menus (Dashboard, Sales, Products, Customers, Reports, Settings) will be available.
        </div>

        <hr class="my-4">

        <!-- Roles & Permissions -->
        <h3 class="mb-4"><i class="fa fa-users"></i> Roles & Permissions</h3>
        <p class="text-muted mb-4">Select a business type from the dropdown to see suggested roles and their permissions, or create a custom role.</p>
        
        <div id="roles-container">
          <!-- Business Type Selection Dropdown -->
          <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="fa fa-filter"></i> Select Business Type to View Suggested Roles</h5>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <label><strong>Business Type:</strong></label>
                  <select id="business-type-select" class="form-control form-control-lg">
                    <option value="">-- Select a business type --</option>
                    <!-- Options will be dynamically populated based on selected business types above -->
                  </select>
                  <small class="text-muted">Select a business type to see its suggested roles and permissions</small>
                </div>
                <div class="col-md-6">
                  <label>&nbsp;</label>
                  <div>
                    <button type="button" class="btn btn-success btn-lg btn-block" onclick="addNewRole()">
                      <i class="fa fa-plus"></i> Create Custom Role
                    </button>
                    <small class="text-muted d-block mt-2">Create a role with custom permissions</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Suggested Roles Section (shown when business type is selected) -->
          <div id="suggested-roles-section" class="card mb-4 border-info" style="display: none;">
            <div class="card-header bg-info text-white">
              <h5 class="mb-0">
                <i class="fa fa-lightbulb-o"></i> Suggested Roles for: <span id="selected-business-type-name"></span>
              </h5>
            </div>
            <div class="card-body">
              <div id="suggested-roles-list" class="row">
                <!-- Suggested roles will be dynamically added here -->
              </div>
            </div>
          </div>

          <!-- Added Roles List (Visible roles added by user) -->
          <div id="added-roles-section" class="card mb-4 border-success" style="display: none;">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0"><i class="fa fa-check-circle"></i> Added Roles</h5>
            </div>
            <div class="card-body">
              <div id="roles-list">
                <!-- Roles added by user will appear here -->
              </div>
            </div>
          </div>

          <!-- Existing Roles List (Visible - users can edit these) -->
          @if($existingRoles && $existingRoles->count() > 0)
          <div id="existing-roles-section" class="card mb-4 border-warning">
            <div class="card-header bg-warning text-dark">
              <h5 class="mb-0"><i class="fa fa-users"></i> Existing Roles</h5>
            </div>
            <div class="card-body">
              <div id="existing-roles-list">
                @foreach($existingRoles as $role)
                <div class="card mb-3 role-item" data-role-id="{{ $role->id }}">
                  <div class="card-header bg-light" style="cursor: pointer;" onclick="toggleRolePermissions(this)">
                    <div class="row align-items-center">
                      <div class="col-md-1">
                        <i class="fa fa-chevron-down role-toggle-icon"></i>
                      </div>
                      <div class="col-md-5">
                        <input type="text" class="form-control form-control-lg" name="roles[{{ $role->id }}][name]" 
                               value="{{ $role->name }}" placeholder="Role Name" required
                               onclick="event.stopPropagation();">
                      </div>
                      <div class="col-md-5">
                        <input type="text" class="form-control" name="roles[{{ $role->id }}][description]" 
                               value="{{ $role->description }}" placeholder="Role Description"
                               onclick="event.stopPropagation();">
                      </div>
                      <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeRole(this); event.stopPropagation();">
                          <i class="fa fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="card-body role-permissions-body" style="display: block;">
                    <h6 class="mb-3"><i class="fa fa-key"></i> Permissions:</h6>
                    @php
                      // Debug: Show loaded permission IDs for this role
                      $debugPermissionIds = $role->permissions->pluck('id')->toArray();
                    @endphp
                    <!-- Debug: Role {{ $role->id }} ({{ $role->name }}) has {{ count($debugPermissionIds) }} permissions: {{ implode(', ', $debugPermissionIds) }} -->
                    <div class="row">
                      @foreach($permissions as $module => $modulePermissions)
                      <div class="col-md-3 mb-3">
                        <div class="card border">
                          <div class="card-header bg-secondary text-white">
                            <strong>{{ ucfirst($module) }}</strong>
                            <button type="button" class="btn btn-sm btn-link text-white float-right p-0" 
                                    onclick="toggleModulePermissions(this, '{{ $module }}')" title="Toggle All">
                              <i class="fa fa-check-square"></i>
                            </button>
                          </div>
                          <div class="card-body p-2">
                            @foreach($modulePermissions as $permission)
                            <div class="form-check">
                              @php
                                // Force reload permissions if not loaded
                                if (!$role->relationLoaded('permissions')) {
                                    $role->load('permissions');
                                }
                                $rolePermissionIds = $role->permissions->pluck('id')->map(function($id) {
                                    return (int) $id;
                                })->toArray();
                                $permissionId = (int) $permission->id;
                                $isChecked = in_array($permissionId, $rolePermissionIds, true);
                              @endphp
                              <input class="form-check-input module-{{ $module }}" type="checkbox" 
                                     name="roles[{{ $role->id }}][permissions][]" 
                                     value="{{ $permission->id }}" 
                                     id="perm_{{ $role->id }}_{{ $permission->id }}"
                                     {{ $isChecked ? 'checked' : '' }}>
                              <label class="form-check-label" for="perm_{{ $role->id }}_{{ $permission->id }}">
                                {{ ucfirst($permission->action) }}
                              </label>
                            </div>
                            @endforeach
                          </div>
                        </div>
                      </div>
                      @endforeach
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
            </div>
          </div>
          @endif
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Save Changes
          </button>
          <a href="{{ route('settings.index') }}" class="btn btn-secondary">
            <i class="fa fa-times"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
let roleCounter = {{ ($existingRoles && $existingRoles->count() > 0) ? $existingRoles->count() : 0 }};
const permissions = @json($permissions->toArray());
const suggestedRoles = @json(isset($suggestedRoles) ? $suggestedRoles : []);

// Update business type dropdown and suggested roles
document.addEventListener('DOMContentLoaded', function() {
  const businessTypeCheckboxes = document.querySelectorAll('.business-type-checkbox');
  const businessTypeSelect = document.getElementById('business-type-select');
  
  // Update dropdown when business types are selected/deselected
  businessTypeCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      updateBusinessTypeDropdown();
    });
  });
  
  // Show suggested roles when business type is selected from dropdown
  businessTypeSelect.addEventListener('change', function() {
    updateSuggestedRoles();
  });
  
  // Initialize - show all role permissions by default (user needs to see them to edit)
  document.querySelectorAll('.role-permissions-body').forEach(body => {
    body.style.display = 'block';
  });
  
  // Initial update
  updateBusinessTypeDropdown();
});

function updateBusinessTypeDropdown() {
  const selectedBusinessTypes = Array.from(document.querySelectorAll('.business-type-checkbox:checked'));
  const businessTypeSelect = document.getElementById('business-type-select');
  const businessTypeData = @json($businessTypes->map(function($bt) { 
    return ['id' => $bt->id, 'slug' => $bt->slug, 'name' => $bt->name]; 
  })->toArray());
  
  // Clear existing options (except the first placeholder)
  businessTypeSelect.innerHTML = '<option value="">-- Select a business type --</option>';
  
  if (selectedBusinessTypes.length === 0) {
    businessTypeSelect.innerHTML += '<option disabled>Select business types above first</option>';
    return;
  }
  
  // Add selected business types to dropdown
  selectedBusinessTypes.forEach(checkbox => {
    const checkedId = parseInt(checkbox.value);
    const businessType = businessTypeData.find(bt => bt.id === checkedId);
    if (businessType) {
      const option = document.createElement('option');
      option.value = businessType.slug;
      option.textContent = businessType.name;
      businessTypeSelect.appendChild(option);
    }
  });
}

function updateSuggestedRoles() {
  const businessTypeSelect = document.getElementById('business-type-select');
  const selectedSlug = businessTypeSelect.value;
  
  const suggestedRolesSection = document.getElementById('suggested-roles-section');
  const suggestedRolesList = document.getElementById('suggested-roles-list');
  const selectedBusinessTypeName = document.getElementById('selected-business-type-name');
  
  if (!selectedSlug) {
    suggestedRolesSection.style.display = 'none';
    return;
  }
  
  // Get business type name
  const businessTypeData = @json($businessTypes->map(function($bt) { 
    return ['id' => $bt->id, 'slug' => $bt->slug, 'name' => $bt->name]; 
  })->toArray());
  const businessType = businessTypeData.find(bt => bt.slug === selectedSlug);
  selectedBusinessTypeName.textContent = businessType ? businessType.name : selectedSlug;
  
  // Map business type slugs to their suggested roles
  const businessTypeRoleMap = {
    'bar': ['Manager', 'Stock Keeper', 'Counter', 'Waiter'],
    'restaurant': ['Manager', 'Chef', 'Waiter', 'Cashier'],
    'juice': ['Manager', 'Juice Maker', 'Counter']
  };
  
  const rolesForType = businessTypeRoleMap[selectedSlug] || [];
  
  // Filter suggested roles for this business type
  const filteredRoles = suggestedRoles.filter(role => rolesForType.includes(role.name));
  
  if (filteredRoles.length === 0) {
    suggestedRolesSection.style.display = 'none';
    return;
  }
  
  // Display suggested roles with their permissions
  suggestedRolesSection.style.display = 'block';
  suggestedRolesList.innerHTML = '';
  
  filteredRoles.forEach((role) => {
    // Find the role in the original suggestedRoles array
    const roleIndex = suggestedRoles.findIndex(r => r.name === role.name && JSON.stringify(r.permissions) === JSON.stringify(role.permissions));
    
    // Build permissions preview
    let permissionsPreview = '';
    if (role.permissions) {
      const permissionModules = Object.keys(role.permissions);
      permissionsPreview = permissionModules.slice(0, 3).map(module => {
        const actions = role.permissions[module].join(', ');
        return `<span class="badge badge-secondary mr-1">${module}: ${actions}</span>`;
      }).join('');
      if (permissionModules.length > 3) {
        permissionsPreview += `<span class="badge badge-light">+${permissionModules.length - 3} more</span>`;
      }
    }
    
    const roleCard = `
      <div class="col-md-12 mb-3">
        <div class="card border-info">
          <div class="card-header bg-light">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h5 class="mb-0">
                  <i class="fa fa-user-circle text-info"></i> ${role.name}
                </h5>
                <small class="text-muted">${role.description || 'No description'}</small>
              </div>
              <div class="col-md-4 text-right">
                <button type="button" class="btn btn-info btn-sm" onclick="addSuggestedRole(suggestedRoles[${roleIndex}])">
                  <i class="fa fa-plus"></i> Add This Role
                </button>
              </div>
            </div>
          </div>
          <div class="card-body">
            <h6 class="mb-2"><i class="fa fa-key"></i> Permissions:</h6>
            <div class="mb-2">${permissionsPreview || 'No permissions defined'}</div>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="showRolePermissions(${roleIndex})">
              <i class="fa fa-eye"></i> View All Permissions
            </button>
          </div>
        </div>
      </div>
    `;
    suggestedRolesList.insertAdjacentHTML('beforeend', roleCard);
  });
}

function showRolePermissions(roleIndex) {
  const role = suggestedRoles[roleIndex];
  if (!role) return;
  
  // Build full permissions list
  let permissionsHtml = '<div class="row">';
  const permissionMap = {};
  @foreach($permissions->flatten() as $perm)
    permissionMap['{{ $perm->module }}.{{ $perm->action }}'] = {id: {{ $perm->id }}, module: '{{ $perm->module }}', action: '{{ $perm->action }}'};
  @endforeach
  
  // Group permissions by module
  const modules = {};
  Object.keys(permissionMap).forEach(key => {
    const perm = permissionMap[key];
    if (!modules[perm.module]) {
      modules[perm.module] = [];
    }
    modules[perm.module].push(perm);
  });
  
  Object.keys(modules).forEach(module => {
    const modulePerms = modules[module];
    const rolePerms = role.permissions && role.permissions[module] ? role.permissions[module] : [];
    
    permissionsHtml += `
      <div class="col-md-3 mb-3">
        <div class="card border">
          <div class="card-header bg-secondary text-white">
            <strong>${module.charAt(0).toUpperCase() + module.slice(1)}</strong>
            <button type="button" class="btn btn-sm btn-link text-white float-right p-0" 
                    onclick="toggleModalModulePermissions(this, 'modal-${module}')" title="Toggle All">
              <i class="fa fa-check-square"></i>
            </button>
          </div>
          <div class="card-body p-2">
            ${modulePerms.map(perm => {
              const isChecked = rolePerms.includes(perm.action);
              return `
                <div class="form-check">
                  <input class="form-check-input modal-permission-checkbox modal-${module}" 
                         type="checkbox" 
                         data-module="${module}" 
                         data-action="${perm.action}" 
                         data-permission-id="${perm.id}"
                         ${isChecked ? 'checked' : ''}>
                  <label class="form-check-label">${perm.action.charAt(0).toUpperCase() + perm.action.slice(1)}</label>
                </div>
              `;
            }).join('')}
          </div>
        </div>
      </div>
    `;
  });
  
  permissionsHtml += '</div>';
  
  // Create or update modal
  let modal = document.getElementById('role-permissions-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'role-permissions-modal';
    modal.className = 'modal fade';
    modal.innerHTML = `
      <div class="modal-dialog modal-xl" style="max-width: 95%; width: 95%;">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"></h5>
            <button type="button" class="close text-white" data-dismiss="modal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-success" onclick="checkAllModalPermissions()">
              <i class="fa fa-check-square"></i> Check All
            </button>
            <button type="button" class="btn btn-warning" onclick="uncheckAllModalPermissions()">
              <i class="fa fa-square"></i> Uncheck All
            </button>
            <button type="button" class="btn btn-info" onclick="applyModalPermissionsToRole(${roleIndex})">
              <i class="fa fa-check"></i> Apply & Add Role
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }
  
  modal.querySelector('.modal-title').textContent = `${role.name} - Permissions`;
  modal.querySelector('.modal-body').innerHTML = permissionsHtml;
  
  // Store role index for later use
  modal.setAttribute('data-role-index', roleIndex);
  
  // Initialize module toggle icons based on current checkbox states
  Object.keys(modules).forEach(module => {
    const moduleCheckboxes = modal.querySelectorAll(`.modal-${module}`);
    if (moduleCheckboxes.length > 0) {
      const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
      const firstCheckbox = moduleCheckboxes[0];
      const moduleCard = firstCheckbox.closest('.card');
      const toggleBtn = moduleCard.querySelector('.card-header button');
      if (toggleBtn) {
        const icon = toggleBtn.querySelector('i');
        if (icon) {
          icon.className = allChecked ? 'fa fa-check-square' : 'fa fa-square';
        }
      }
    }
  });
  
  // Add event listeners to update module toggle icons when checkboxes change
  modal.querySelectorAll('.modal-permission-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const module = this.getAttribute('data-module');
      const moduleCheckboxes = modal.querySelectorAll(`.modal-${module}`);
      const allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
      const moduleCard = this.closest('.card');
      const toggleBtn = moduleCard.querySelector('.card-header button');
      if (toggleBtn) {
        const icon = toggleBtn.querySelector('i');
        if (icon) {
          icon.className = allChecked ? 'fa fa-check-square' : 'fa fa-square';
        }
      }
    });
  });
  
  $(modal).modal('show');
}

function toggleModalModulePermissions(btn, moduleClass) {
  const modal = document.getElementById('role-permissions-modal');
  const checkboxes = modal.querySelectorAll(`.${moduleClass}`);
  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
  
  checkboxes.forEach(cb => {
    cb.checked = !allChecked;
  });
  
  btn.querySelector('i').className = allChecked ? 'fa fa-square' : 'fa fa-check-square';
}

function checkAllModalPermissions() {
  const modal = document.getElementById('role-permissions-modal');
  const checkboxes = modal.querySelectorAll('.modal-permission-checkbox');
  checkboxes.forEach(cb => cb.checked = true);
  
  // Update all module toggle icons
  modal.querySelectorAll('.card-header button').forEach(btn => {
    const icon = btn.querySelector('i');
    if (icon) icon.className = 'fa fa-check-square';
  });
}

function uncheckAllModalPermissions() {
  const modal = document.getElementById('role-permissions-modal');
  const checkboxes = modal.querySelectorAll('.modal-permission-checkbox');
  checkboxes.forEach(cb => cb.checked = false);
  
  // Update all module toggle icons
  modal.querySelectorAll('.card-header button').forEach(btn => {
    const icon = btn.querySelector('i');
    if (icon) icon.className = 'fa fa-square';
  });
}

function applyModalPermissionsToRole(roleIndex) {
  const modal = document.getElementById('role-permissions-modal');
  const role = suggestedRoles[roleIndex];
  if (!role) return;
  
  // Get all checked permissions from modal
  const checkedCheckboxes = modal.querySelectorAll('.modal-permission-checkbox:checked');
  const selectedPermissions = {};
  
  checkedCheckboxes.forEach(cb => {
    const module = cb.getAttribute('data-module');
    const action = cb.getAttribute('data-action');
    
    if (!selectedPermissions[module]) {
      selectedPermissions[module] = [];
    }
    selectedPermissions[module].push(action);
  });
  
  // Update the role's permissions with selected ones
  role.permissions = selectedPermissions;
  
  // Close modal
  $(modal).modal('hide');
  
  // Add the role with updated permissions
  addSuggestedRole(role);
}

function toggleRolePermissions(header) {
  const card = header.closest('.role-item');
  const body = card.querySelector('.role-permissions-body');
  const icon = header.querySelector('.role-toggle-icon');
  
  if (body.style.display === 'none') {
    body.style.display = 'block';
    if (icon) {
      icon.classList.remove('fa-chevron-down');
      icon.classList.add('fa-chevron-up');
    }
  } else {
    body.style.display = 'none';
    if (icon) {
      icon.classList.remove('fa-chevron-up');
      icon.classList.add('fa-chevron-down');
    }
  }
}

function addSuggestedRole(roleData) {
  const roleIndex = 'new_' + roleCounter++;
  
  // Get permission IDs for this role
  const permissionMap = {};
  @foreach($permissions->flatten() as $perm)
    permissionMap['{{ $perm->module }}.{{ $perm->action }}'] = {{ $perm->id }};
  @endforeach
  
  let permissionsHtml = '';
  Object.keys(permissions).forEach(module => {
    const rolePerms = roleData.permissions && roleData.permissions[module] ? roleData.permissions[module] : [];
    permissionsHtml += `
      <div class="col-md-3 mb-3">
        <div class="card border">
          <div class="card-header bg-secondary text-white">
            <strong>${module.charAt(0).toUpperCase() + module.slice(1)}</strong>
            <button type="button" class="btn btn-sm btn-link text-white float-right p-0" 
                    onclick="toggleModulePermissions(this, '${module}')" title="Toggle All">
              <i class="fa fa-check-square"></i>
            </button>
          </div>
          <div class="card-body p-2">
            ${permissions[module].map(perm => {
              const isChecked = rolePerms.includes(perm.action);
              return `
                <div class="form-check">
                  <input class="form-check-input module-${module}" type="checkbox" 
                         name="roles[${roleIndex}][permissions][]" 
                         value="${perm.id}" 
                         id="perm_${roleIndex}_${perm.id}"
                         ${isChecked ? 'checked' : ''}>
                  <label class="form-check-label" for="perm_${roleIndex}_${perm.id}">
                    ${perm.action.charAt(0).toUpperCase() + perm.action.slice(1)}
                  </label>
                </div>
              `;
            }).join('')}
          </div>
        </div>
      </div>
    `;
  });
  
  const roleHtml = `
    <div class="card mb-3 role-item" data-role-index="${roleIndex}">
      <div class="card-header bg-light" style="cursor: pointer;" onclick="toggleRolePermissions(this)">
        <div class="row align-items-center">
          <div class="col-md-1">
            <i class="fa fa-chevron-down role-toggle-icon"></i>
          </div>
          <div class="col-md-5">
            <input type="text" class="form-control form-control-lg" name="roles[${roleIndex}][name]" 
                   value="${roleData.name}" placeholder="Role Name" required
                   onclick="event.stopPropagation();">
          </div>
          <div class="col-md-5">
            <input type="text" class="form-control" name="roles[${roleIndex}][description]" 
                   value="${roleData.description || ''}" placeholder="Role Description"
                   onclick="event.stopPropagation();">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeRole(this); event.stopPropagation();">
              <i class="fa fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="card-body role-permissions-body" style="display: block;">
        <h6 class="mb-3"><i class="fa fa-key"></i> Permissions:</h6>
        <div class="row">
          ${permissionsHtml}
        </div>
      </div>
    </div>
  `;
  
  const rolesList = document.getElementById('roles-list');
  const addedRolesSection = document.getElementById('added-roles-section');
  
  if (rolesList) {
    rolesList.insertAdjacentHTML('beforeend', roleHtml);
    if (addedRolesSection) {
      addedRolesSection.style.display = 'block';
    }
    // Show success message
    Swal.fire({
      icon: 'success',
      title: 'Role Added!',
      text: 'Role "' + roleData.name + '" has been added! Review and adjust permissions, then click "Save Changes" to save.',
      confirmButtonColor: '#940000'
    });
  } else {
    console.error('roles-list element not found');
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Could not add role. Please refresh the page and try again.',
      confirmButtonColor: '#940000'
    });
  }
}

function addNewRole() {
  const roleIndex = 'new_' + roleCounter++;
  
  const roleHtml = `
    <div class="card mb-3 role-item" data-role-index="${roleIndex}">
      <div class="card-header bg-light" style="cursor: pointer;" onclick="toggleRolePermissions(this)">
        <div class="row align-items-center">
          <div class="col-md-1">
            <i class="fa fa-chevron-down role-toggle-icon"></i>
          </div>
          <div class="col-md-5">
            <input type="text" class="form-control form-control-lg" name="roles[${roleIndex}][name]" 
                   placeholder="Role Name" required
                   onclick="event.stopPropagation();">
          </div>
          <div class="col-md-5">
            <input type="text" class="form-control" name="roles[${roleIndex}][description]" 
                   placeholder="Role Description"
                   onclick="event.stopPropagation();">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeRole(this); event.stopPropagation();">
              <i class="fa fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="card-body role-permissions-body" style="display: none;">
        <h6 class="mb-3"><i class="fa fa-key"></i> Permissions:</h6>
        <div class="row">
          ${Object.keys(permissions).map(module => `
            <div class="col-md-3 mb-3">
              <div class="card border">
                <div class="card-header bg-secondary text-white">
                  <strong>${module.charAt(0).toUpperCase() + module.slice(1)}</strong>
                  <button type="button" class="btn btn-sm btn-link text-white float-right p-0" 
                          onclick="toggleModulePermissions(this, '${module}')" title="Toggle All">
                    <i class="fa fa-check-square"></i>
                  </button>
                </div>
                <div class="card-body p-2">
                  ${permissions[module].map(perm => `
                    <div class="form-check">
                      <input class="form-check-input module-${module}" type="checkbox" 
                             name="roles[${roleIndex}][permissions][]" 
                             value="${perm.id}" 
                             id="perm_${roleIndex}_${perm.id}">
                      <label class="form-check-label" for="perm_${roleIndex}_${perm.id}">
                        ${perm.action.charAt(0).toUpperCase() + perm.action.slice(1)}
                      </label>
                    </div>
                  `).join('')}
                </div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    </div>
  `;
  
  const rolesList = document.getElementById('roles-list');
  const addedRolesSection = document.getElementById('added-roles-section');
  
  if (rolesList) {
    rolesList.insertAdjacentHTML('beforeend', roleHtml);
    if (addedRolesSection) {
      addedRolesSection.style.display = 'block';
    }
    Swal.fire({
      icon: 'success',
      title: 'Custom Role Added!',
      text: 'Custom role has been added! Configure permissions and click "Save Changes" to save.',
      confirmButtonColor: '#940000'
    });
  } else {
    console.error('roles-list element not found');
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Could not add role. Please refresh the page and try again.',
      confirmButtonColor: '#940000'
    });
  }
}

function removeRole(btn) {
  Swal.fire({
    title: 'Remove Role?',
    text: 'Are you sure you want to remove this role?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, remove it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      const roleItem = btn.closest('.role-item');
      const roleName = roleItem.querySelector('input[name*="[name]"]')?.value || 'this role';
      
      // Check if this is an existing role (has numeric ID in data-role-id)
      const roleId = roleItem.getAttribute('data-role-id');
      if (roleId) {
        // This is an existing role - mark it for deletion by adding a hidden input
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = `roles[${roleId}][_delete]`;
        deleteInput.value = '1';
        roleItem.appendChild(deleteInput);
        // Hide the role item instead of removing it so form data is still submitted
        roleItem.style.display = 'none';
      } else {
        // This is a new role - can safely remove it
        roleItem.remove();
      }
      
      // Hide added roles section if no visible roles left
      const rolesList = document.getElementById('roles-list');
      const addedRolesSection = document.getElementById('added-roles-section');
      if (rolesList && Array.from(rolesList.querySelectorAll('.role-item')).every(item => item.style.display === 'none' || !item.parentElement)) {
        if (addedRolesSection) {
          addedRolesSection.style.display = 'none';
        }
      }
      
      Swal.fire({
        icon: 'success',
        title: 'Removed!',
        text: 'Role has been removed.',
        confirmButtonColor: '#940000',
        timer: 1500,
        showConfirmButton: false
      });
    }
  });
}

function toggleModulePermissions(btn, module) {
  const roleItem = btn.closest('.role-item');
  const checkboxes = roleItem.querySelectorAll(`.module-${module}`);
  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
  
  checkboxes.forEach(cb => {
    cb.checked = !allChecked;
  });
  
  btn.querySelector('i').className = allChecked ? 'fa fa-square' : 'fa fa-check-square';
}

@if(isset($suggestedRoles) && count($suggestedRoles) > 0)
function addSuggestedRoles() {
  const suggestedRoles = @json($suggestedRoles);
  const permissions = @json($permissions->toArray());
  const roleCounter = {{ ($existingRoles && $existingRoles->count() > 0) ? $existingRoles->count() : 0 }};
  
  suggestedRoles.forEach((role, index) => {
    const roleIndex = 'suggested_' + (roleCounter + index);
    
    let permissionsHtml = '';
    Object.keys(permissions).forEach(module => {
      permissionsHtml += `
        <div class="col-md-3 mb-3">
          <div class="card border">
            <div class="card-header bg-secondary text-white">
              <strong>${module.charAt(0).toUpperCase() + module.slice(1)}</strong>
              <button type="button" class="btn btn-sm btn-link text-white float-right p-0" 
                      onclick="toggleModulePermissions(this, '${module}')" title="Toggle All">
                <i class="fa fa-check-square"></i>
              </button>
            </div>
            <div class="card-body p-2">
              ${permissions[module].map(perm => {
                const isChecked = role.permissions && role.permissions[module] && role.permissions[module].includes(perm.action);
                return `
                  <div class="form-check">
                    <input class="form-check-input module-${module}" type="checkbox" 
                           name="roles[${roleIndex}][permissions][]" 
                           value="${perm.id}" 
                           id="perm_${roleIndex}_${perm.id}"
                           ${isChecked ? 'checked' : ''}>
                    <label class="form-check-label" for="perm_${roleIndex}_${perm.id}">
                      ${perm.action.charAt(0).toUpperCase() + perm.action.slice(1)}
                    </label>
                  </div>
                `;
              }).join('')}
            </div>
          </div>
        </div>
      `;
    });
    
    const roleHtml = `
      <div class="card mb-3 role-item" data-role-index="${roleIndex}">
        <div class="card-header bg-light">
          <div class="row align-items-center">
            <div class="col-md-6">
              <input type="text" class="form-control form-control-lg" name="roles[${roleIndex}][name]" 
                     value="${role.name}" placeholder="Role Name" required>
            </div>
            <div class="col-md-5">
              <input type="text" class="form-control" name="roles[${roleIndex}][description]" 
                     value="${role.description || ''}" placeholder="Role Description">
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-sm btn-danger" onclick="removeRole(this)">
                <i class="fa fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="card-body">
          <h6 class="mb-3">Permissions:</h6>
          <div class="row">
            ${permissionsHtml}
          </div>
        </div>
      </div>
    `;
    
    document.getElementById('roles-list').insertAdjacentHTML('beforeend', roleHtml);
  });
  
  Swal.fire({
    icon: 'success',
    title: 'Roles Added!',
    text: 'Suggested roles have been added! Review and adjust permissions as needed.',
    confirmButtonColor: '#940000'
  });
}
@endif

// Debug and ensure permissions are always sent
document.getElementById('businessConfigForm').addEventListener('submit', function(e) {
  console.log('Form submitting...');
  
  // Find all role items (both visible and hidden)
  const roleItems = document.querySelectorAll('.role-item');
  const formData = new FormData(this);
  const rolesData = {};
  
  // Collect all form data for debugging
  for (let [key, value] of formData.entries()) {
    if (key.startsWith('roles[')) {
      console.log('Form data:', key, '=', value);
      const match = key.match(/roles\[([^\]]+)\]\[([^\]]+)\](?:\[([^\]]+)\])?/);
      if (match) {
        const roleKey = match[1];
        const field = match[2];
        const subField = match[3];
        
        // Skip delete markers
        if (field === '_delete') continue;
        
        if (!rolesData[roleKey]) rolesData[roleKey] = {};
        if (subField) {
          if (!rolesData[roleKey][field]) rolesData[roleKey][field] = [];
          rolesData[roleKey][field].push(value);
        } else {
          rolesData[roleKey][field] = value;
        }
      }
    }
  }
  
  console.log('Roles data being submitted:', JSON.stringify(rolesData, null, 2));
  
  // Ensure all roles have permissions array (even if empty)
  roleItems.forEach(function(roleItem) {
    // Skip hidden/deleted roles
    if (roleItem.style.display === 'none') return;
    
    // Get the role key from the name attribute of the first input
    const roleNameInput = roleItem.querySelector('input[name*="[name]"]');
    if (!roleNameInput) return;
    
    const roleNameMatch = roleNameInput.name.match(/roles\[([^\]]+)\]/);
    if (!roleNameMatch) return;
    
    const roleKey = roleNameMatch[1];
    
    // Check if this role is marked for deletion
    const deleteInput = roleItem.querySelector(`input[name="roles[${roleKey}][_delete]"]`);
    if (deleteInput && deleteInput.value === '1') {
      console.log(`Role ${roleKey} - Marked for deletion, skipping`);
      return;
    }
    
    // Check if there are any checked permission checkboxes for this role
    const permissionCheckboxes = roleItem.querySelectorAll(`input[name="roles[${roleKey}][permissions][]"]`);
    const checkedPermissions = Array.from(permissionCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
    
    console.log(`Role ${roleKey} - Checked permissions:`, checkedPermissions);
    
    // Remove any hidden inputs that might have been added previously (with empty values)
    const existingHiddenInputs = roleItem.querySelectorAll(`input[type="hidden"][name="roles[${roleKey}][permissions][]"]`);
    existingHiddenInputs.forEach(input => {
      if (input.value === '') {
        input.remove();
      }
    });
    
    // If no permissions are checked, that's fine - Laravel will receive an empty array
    // Don't add hidden inputs with empty values as they cause validation errors
  });
  
  // Ensure all roles from both visible and hidden sections are included
  // Collect all role items including those in hidden divs
  const allRoleItems = document.querySelectorAll('#existing-roles-list .role-item, #roles-list .role-item');
  
  allRoleItems.forEach(function(roleItem) {
    // Skip if marked for deletion
    if (roleItem.style.display === 'none') {
      const deleteInput = roleItem.querySelector('input[name*="[_delete]"]');
      if (deleteInput && deleteInput.value === '1') {
        return; // Skip deleted roles
      }
    }
    
    const roleNameInput = roleItem.querySelector('input[name*="[name]"]');
    if (!roleNameInput) return;
    
    const roleNameMatch = roleNameInput.name.match(/roles\[([^\]]+)\]/);
    if (!roleNameMatch) return;
    
    const roleKey = roleNameMatch[1];
    
    // Ensure role name is not empty
    if (!roleNameInput.value || roleNameInput.value.trim() === '') {
      console.warn(`Role ${roleKey} has empty name, skipping`);
      return;
    }
    
    // Count checked permissions
    const permissionCheckboxes = roleItem.querySelectorAll(`input[name="roles[${roleKey}][permissions][]"]`);
    const checkedCount = Array.from(permissionCheckboxes).filter(cb => cb.checked).length;
    
    console.log(`Role ${roleKey} (${roleNameInput.value}): ${checkedCount} permissions checked`);
  });
  
  // Final verification before submit
  console.log('Final form data before submit:');
  const finalFormData = new FormData(this);
  const rolesSummary = {};
  for (let [key, value] of finalFormData.entries()) {
    if (key.startsWith('roles[')) {
      const match = key.match(/roles\[([^\]]+)\]\[([^\]]+)\](?:\[([^\]]+)\])?/);
      if (match) {
        const roleKey = match[1];
        const field = match[2];
        if (field === 'permissions' && match[3] === undefined) {
          if (!rolesSummary[roleKey]) rolesSummary[roleKey] = { permissions: [] };
          rolesSummary[roleKey].permissions.push(value);
        } else if (field === 'name') {
          if (!rolesSummary[roleKey]) rolesSummary[roleKey] = {};
          rolesSummary[roleKey].name = value;
        }
      }
    }
  }
  
  console.log('Roles summary:', JSON.stringify(rolesSummary, null, 2));
  
  // Don't prevent default - let form submit normally
});
</script>
@endsection

