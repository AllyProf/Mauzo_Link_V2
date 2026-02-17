@if($isBasicPlan)
<div class="alert alert-info">
  <h5><i class="fa fa-info-circle"></i> Basic Plan - Sole Proprietor</h5>
  <p>As a Basic Plan user, you will be set up as the Owner with full access. Staff management is not available on this plan.</p>
</div>
@endif

<form action="{{ route('business-configuration.step3') }}" method="POST" id="rolesForm">
  @csrf
  <h3 class="mb-4">Step 3: Roles & Permissions</h3>
  <p class="text-muted mb-4">Create roles for your business and assign permissions based on your needs. Select what each role can access.</p>
  
  @if(!$isBasicPlan)
  <div id="roles-container">
    <div class="form-group mb-3">
      <button type="button" class="btn btn-success" onclick="addNewRole()">
        <i class="fa fa-plus"></i> Add New Role
      </button>
    </div>

    <div id="roles-list">
      @if($existingRoles && $existingRoles->count() > 0)
        @foreach($existingRoles as $role)
          <div class="card mb-3 role-item" data-role-id="{{ $role->id }}">
            <div class="card-header bg-light">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <input type="text" class="form-control form-control-lg" name="roles[{{ $role->id }}][name]" 
                         value="{{ $role->name }}" placeholder="Role Name" required>
                </div>
                <div class="col-md-5">
                  <input type="text" class="form-control" name="roles[{{ $role->id }}][description]" 
                         value="{{ $role->description }}" placeholder="Role Description">
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
                        <input class="form-check-input module-{{ $module }}" type="checkbox" 
                               name="roles[{{ $role->id }}][permissions][]" 
                               value="{{ $permission->id }}" 
                               id="perm_{{ $role->id }}_{{ $permission->id }}"
                               {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
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
      @else
        {{-- Suggested roles based on business types --}}
        @php
          // Use suggested roles if available, otherwise fallback to generic defaults
          $defaultRoles = [];
          if (isset($suggestedRoles) && count($suggestedRoles) > 0) {
            // Add Owner role first
            $defaultRoles[] = ['name' => 'Owner', 'description' => 'Full access to all features', 'permissions' => 'all'];
            // Then add suggested roles
            foreach ($suggestedRoles as $suggestedRole) {
              $defaultRoles[] = $suggestedRole;
            }
          } else {
            // Fallback to generic defaults
            $defaultRoles = [
              ['name' => 'Owner', 'description' => 'Full access to all features', 'permissions' => 'all'],
              ['name' => 'Manager', 'description' => 'Manage operations and staff', 'permissions' => 'most'],
              ['name' => 'Cashier', 'description' => 'Handle sales and transactions', 'permissions' => 'sales'],
              ['name' => 'Staff', 'description' => 'Limited access based on permissions', 'permissions' => 'view'],
            ];
          }
        @endphp
        @foreach($defaultRoles as $index => $defaultRole)
          <div class="card mb-3 role-item" data-role-index="{{ $index }}">
            <div class="card-header bg-light">
              <div class="row align-items-center">
                <div class="col-md-6">
                  <input type="text" class="form-control form-control-lg" name="roles[new_{{ $index }}][name]" 
                         value="{{ $defaultRole['name'] }}" placeholder="Role Name" required>
                </div>
                <div class="col-md-5">
                  <input type="text" class="form-control" name="roles[new_{{ $index }}][description]" 
                         value="{{ $defaultRole['description'] }}" placeholder="Role Description">
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
                        <input class="form-check-input module-{{ $module }}" type="checkbox" 
                               name="roles[new_{{ $index }}][permissions][]" 
                               value="{{ $permission->id }}" 
                               id="perm_new_{{ $index }}_{{ $permission->id }}"
                               {{ 
                                 ($defaultRole['permissions'] === 'all' || 
                                  ($defaultRole['permissions'] === 'most' && $permission->action !== 'delete') || 
                                  ($defaultRole['permissions'] === 'sales' && ($module === 'sales' || $module === 'products')) || 
                                  ($defaultRole['permissions'] === 'view' && $permission->action === 'view') ||
                                  (isset($defaultRole['permissions'][$module]) && in_array($permission->action, $defaultRole['permissions'][$module]))) 
                                 ? 'checked' : '' 
                               }}>
                        <label class="form-check-label" for="perm_new_{{ $index }}_{{ $permission->id }}">
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
      @endif
    </div>
  </div>
  @endif

  <div class="mt-4">
    <a href="{{ route('business-configuration.step2') }}" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Previous
    </a>
    <button type="submit" class="btn btn-primary">
      Next Step <i class="fa fa-arrow-right"></i>
    </button>
  </div>
</form>

<script>
let roleCounter = {{ ($existingRoles && $existingRoles->count() > 0) ? $existingRoles->count() : 4 }};

function addNewRole() {
  const permissions = @json($permissions->toArray());
  const roleIndex = 'new_' + roleCounter++;
  
  const roleHtml = `
    <div class="card mb-3 role-item" data-role-index="${roleIndex}">
      <div class="card-header bg-light">
        <div class="row align-items-center">
          <div class="col-md-6">
            <input type="text" class="form-control form-control-lg" name="roles[${roleIndex}][name]" 
                   placeholder="Role Name" required>
          </div>
          <div class="col-md-5">
            <input type="text" class="form-control" name="roles[${roleIndex}][description]" 
                   placeholder="Role Description">
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
  
  document.getElementById('roles-list').insertAdjacentHTML('beforeend', roleHtml);
}

function removeRole(btn) {
  if (confirm('Are you sure you want to remove this role?')) {
    btn.closest('.role-item').remove();
  }
}

function toggleModulePermissions(btn, module) {
  const checkboxes = document.querySelectorAll(`.module-${module}`);
  const allChecked = Array.from(checkboxes).every(cb => cb.checked);
  
  checkboxes.forEach(cb => {
    cb.checked = !allChecked;
  });
  
  btn.querySelector('i').className = allChecked ? 'fa fa-square' : 'fa fa-check-square';
}
</script>

<style>
.role-item {
  transition: all 0.3s;
}
.role-item:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.card-header.bg-secondary {
  font-size: 0.9rem;
}
</style>
