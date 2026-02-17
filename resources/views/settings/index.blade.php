@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cog"></i> Settings</h1>
    <p>System and account settings</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ $user && $user->isAdmin() ? route('admin.dashboard.index') : route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Settings</li>
  </ul>
</div>

@if($user && $user->isAdmin())
<!-- System Settings (Admin Only) -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-server"></i> System Settings</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('settings.update-system') }}">
          @csrf
          
          <!-- Company Information -->
          <h5 class="mb-3"><i class="fa fa-building"></i> Company Information</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="company_name">Company Name <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control" 
                       id="company_name" 
                       name="company_name" 
                       value="{{ old('company_name', \App\Models\SystemSetting::get('company_name', 'EmCa Technologies')) }}" 
                       required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="company_email">Company Email <span class="text-danger">*</span></label>
                <input type="email" 
                       class="form-control" 
                       id="company_email" 
                       name="company_email" 
                       value="{{ old('company_email', \App\Models\SystemSetting::get('company_email', 'emca@emca.tech')) }}" 
                       required>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="company_phone">Company Phone</label>
                <input type="text" 
                       class="form-control" 
                       id="company_phone" 
                       name="company_phone" 
                       value="{{ old('company_phone', \App\Models\SystemSetting::get('company_phone', '+255 749 719 998')) }}">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="company_address">Company Address</label>
                <input type="text" 
                       class="form-control" 
                       id="company_address" 
                       name="company_address" 
                       value="{{ old('company_address', \App\Models\SystemSetting::get('company_address', 'Ben Bella Street, Moshi')) }}">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="company_website">Company Website</label>
                <input type="url" 
                       class="form-control" 
                       id="company_website" 
                       name="company_website" 
                       value="{{ old('company_website', \App\Models\SystemSetting::get('company_website', 'www.emca.tech')) }}">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="company_tin">TIN Number</label>
                <input type="text" 
                       class="form-control" 
                       id="company_tin" 
                       name="company_tin" 
                       value="{{ old('company_tin', \App\Models\SystemSetting::get('company_tin', '181-103-264')) }}">
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Payment Settings -->
          <h5 class="mb-3"><i class="fa fa-money"></i> Payment Settings</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="bank_name">Bank Name <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control" 
                       id="bank_name" 
                       name="bank_name" 
                       value="{{ old('bank_name', \App\Models\SystemSetting::get('bank_name', 'CRDB Bank')) }}" 
                       required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="bank_account_number">Account Number <span class="text-danger">*</span></label>
                <input type="text" 
                       class="form-control" 
                       id="bank_account_number" 
                       name="bank_account_number" 
                       value="{{ old('bank_account_number', \App\Models\SystemSetting::get('bank_account_number', '329876567')) }}" 
                       required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="payment_instructions">Payment Instructions</label>
            <textarea class="form-control" 
                      id="payment_instructions" 
                      name="payment_instructions" 
                      rows="4">{{ old('payment_instructions', \App\Models\SystemSetting::get('payment_instructions', 'Please make payment to the above account number and upload proof of payment for verification.')) }}</textarea>
          </div>

          <hr class="my-4">

          <!-- SMS Settings -->
          <h5 class="mb-3"><i class="fa fa-comment"></i> SMS Gateway Settings</h5>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="sms_username">SMS Username</label>
                <input type="text" 
                       class="form-control" 
                       id="sms_username" 
                       name="sms_username" 
                       value="{{ old('sms_username', \App\Models\SystemSetting::get('sms_username', 'emcatechn')) }}">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="sms_password">SMS Password</label>
                <input type="password" 
                       class="form-control" 
                       id="sms_password" 
                       name="sms_password" 
                       value="{{ old('sms_password', \App\Models\SystemSetting::get('sms_password', '')) }}">
                <small class="form-text text-muted">Leave blank to keep current password</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="sms_sender_id">Sender ID</label>
                <input type="text" 
                       class="form-control" 
                       id="sms_sender_id" 
                       name="sms_sender_id" 
                       value="{{ old('sms_sender_id', \App\Models\SystemSetting::get('sms_sender_id', 'MauzoLink')) }}">
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- General Settings -->
          <h5 class="mb-3"><i class="fa fa-sliders"></i> General Settings</h5>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="currency">Currency</label>
                <select class="form-control" id="currency" name="currency">
                  <option value="TSh" {{ \App\Models\SystemSetting::get('currency', 'TSh') == 'TSh' ? 'selected' : '' }}>Tanzanian Shilling (TSh)</option>
                  <option value="USD" {{ \App\Models\SystemSetting::get('currency', 'TSh') == 'USD' ? 'selected' : '' }}>US Dollar (USD)</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="timezone">Timezone</label>
                <select class="form-control" id="timezone" name="timezone">
                  <option value="Africa/Dar_es_Salaam" {{ \App\Models\SystemSetting::get('timezone', 'Africa/Dar_es_Salaam') == 'Africa/Dar_es_Salaam' ? 'selected' : '' }}>Africa/Dar es Salaam</option>
                  <option value="UTC" {{ \App\Models\SystemSetting::get('timezone', 'Africa/Dar_es_Salaam') == 'UTC' ? 'selected' : '' }}>UTC</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label for="date_format">Date Format</label>
                <select class="form-control" id="date_format" name="date_format">
                  <option value="Y-m-d" {{ \App\Models\SystemSetting::get('date_format', 'Y-m-d') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                  <option value="d/m/Y" {{ \App\Models\SystemSetting::get('date_format', 'Y-m-d') == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                  <option value="m/d/Y" {{ \App\Models\SystemSetting::get('date_format', 'Y-m-d') == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <div class="form-check">
                  <input class="form-check-input" 
                         type="checkbox" 
                         id="registration_enabled" 
                         name="registration_enabled" 
                         value="1"
                         {{ \App\Models\SystemSetting::get('registration_enabled', true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="registration_enabled">
                    Enable User Registration
                  </label>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <div class="form-check">
                  <input class="form-check-input" 
                         type="checkbox" 
                         id="maintenance_mode" 
                         name="maintenance_mode" 
                         value="1"
                         {{ \App\Models\SystemSetting::get('maintenance_mode', false) ? 'checked' : '' }}>
                  <label class="form-check-label" for="maintenance_mode">
                    Maintenance Mode
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Save System Settings
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endif

@if(!$user || !$user->isAdmin())
<!-- Business Configuration -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-cog"></i> Business Configuration</h3>
      <div class="tile-body">
        <p>Manage your business types, roles, and permissions.</p>
        <a href="{{ route('business-configuration.edit') }}" class="btn btn-primary">
          <i class="fa fa-edit"></i> Edit Business Configuration
        </a>
      </div>
    </div>
  </div>
</div>
@endif

<!-- Profile Settings (All Users) -->
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Profile Information</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('settings.update-profile') }}">
          @csrf
          
          <div class="form-group">
            <label for="name">Full Name <span class="text-danger">*</span></label>
            <input type="text" 
                   class="form-control @error('name') is-invalid @enderror" 
                   id="name" 
                   name="name" 
                   value="{{ old('name', $user->name) }}" 
                   required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="email">Email Address <span class="text-danger">*</span></label>
            <input type="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   id="email" 
                   name="email" 
                   value="{{ old('email', $user->email) }}" 
                   required>
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" 
                   class="form-control @error('phone') is-invalid @enderror" 
                   id="phone" 
                   name="phone" 
                   value="{{ old('phone', $user->phone) }}">
            @error('phone')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Update Profile
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Change Password</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('settings.update-password') }}">
          @csrf
          
          <div class="form-group">
            <label for="current_password">Current Password <span class="text-danger">*</span></label>
            <input type="password" 
                   class="form-control @error('current_password') is-invalid @enderror" 
                   id="current_password" 
                   name="current_password" 
                   required>
            @error('current_password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="password">New Password <span class="text-danger">*</span></label>
            <input type="password" 
                   class="form-control @error('password') is-invalid @enderror" 
                   id="password" 
                   name="password" 
                   required>
            <small class="form-text text-muted">Minimum 8 characters</small>
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="form-group">
            <label for="password_confirmation">Confirm New Password <span class="text-danger">*</span></label>
            <input type="password" 
                   class="form-control" 
                   id="password_confirmation" 
                   name="password_confirmation" 
                   required>
          </div>

          <div class="form-group">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-key"></i> Update Password
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@if($user && $user->isAdmin())
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Account Information</h3>
      <div class="tile-body">
        <table class="table table-borderless">
          <tr>
            <th width="30%">Role:</th>
            <td>
              @if($user->isAdmin())
                <span class="badge badge-danger">Super Admin</span>
              @else
                <span class="badge badge-info">Customer</span>
              @endif
            </td>
          </tr>
          <tr>
            <th>Account Created:</th>
            <td>{{ $user->created_at->format('F d, Y h:i A') }}</td>
          </tr>
          <tr>
            <th>Last Updated:</th>
            <td>{{ $user->updated_at->format('F d, Y h:i A') }}</td>
          </tr>
          @if($user->email_verified_at)
          <tr>
            <th>Email Verified:</th>
            <td><span class="badge badge-success">Yes</span> - {{ $user->email_verified_at->format('F d, Y') }}</td>
          </tr>
          @endif
        </table>
      </div>
    </div>
  </div>
</div>
@endif

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if(session('success'))
<script>
  Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: '{{ session('success') }}',
    confirmButtonColor: '#940000',
    cancelButtonColor: '#000000'
  });
</script>
@endif

@if($errors->any())
<script>
  Swal.fire({
    icon: 'error',
    title: 'Validation Error',
    html: '@foreach($errors->all() as $error){{ $error }}<br>@endforeach',
    confirmButtonColor: '#940000',
    cancelButtonColor: '#000000'
  });
</script>
@endif
@endsection
