@extends('layouts.dashboard')

@section('title', 'Inventory Settings')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cog"></i> Inventory Settings</h1>
    <p>Configure inventory alerts and notifications</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Inventory Settings</li>
  </ul>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <i class="fa fa-check-circle"></i> {{ session('success') }}
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <i class="fa fa-exclamation-circle"></i> Please correct the errors below.
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
  </button>
</div>
@endif

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Stock Alert Settings</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.inventory-settings.update') }}">
          @csrf
          @method('PUT')

          <!-- Stock Threshold Settings -->
          <div class="form-section mb-4">
            <h5 class="mb-3"><i class="fa fa-exclamation-triangle text-warning"></i> Stock Threshold Levels</h5>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="low_stock_threshold">
                    Low Stock Threshold <span class="text-danger">*</span>
                    <small class="text-muted d-block">Items below this quantity will be marked as low stock</small>
                  </label>
                  <input type="number" 
                         class="form-control @error('low_stock_threshold') is-invalid @enderror" 
                         id="low_stock_threshold" 
                         name="low_stock_threshold" 
                         value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" 
                         min="1" 
                         max="1000" 
                         required>
                  @error('low_stock_threshold')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <small class="form-text text-muted">Current: {{ $settings['low_stock_threshold'] }} units</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="critical_stock_threshold">
                    Critical Stock Threshold <span class="text-danger">*</span>
                    <small class="text-muted d-block">Items below this quantity will trigger urgent alerts</small>
                  </label>
                  <input type="number" 
                         class="form-control @error('critical_stock_threshold') is-invalid @enderror" 
                         id="critical_stock_threshold" 
                         name="critical_stock_threshold" 
                         value="{{ old('critical_stock_threshold', $settings['critical_stock_threshold']) }}" 
                         min="1" 
                         max="1000" 
                         required>
                  @error('critical_stock_threshold')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <small class="form-text text-muted">Current: {{ $settings['critical_stock_threshold'] }} units</small>
                </div>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- SMS Notification Settings -->
          <div class="form-section mb-4">
            <h5 class="mb-3"><i class="fa fa-comment text-info"></i> SMS Notification Settings</h5>
            
            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="enable_low_stock_sms" 
                       name="enable_low_stock_sms" 
                       value="1"
                       {{ old('enable_low_stock_sms', $settings['enable_low_stock_sms']) ? 'checked' : '' }}>
                <label class="form-check-label" for="enable_low_stock_sms">
                  <strong>Enable SMS notifications for low stock</strong>
                  <small class="text-muted d-block">Receive SMS alerts when items fall below low stock threshold</small>
                </label>
              </div>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="enable_critical_stock_sms" 
                       name="enable_critical_stock_sms" 
                       value="1"
                       {{ old('enable_critical_stock_sms', $settings['enable_critical_stock_sms']) ? 'checked' : '' }}>
                <label class="form-check-label" for="enable_critical_stock_sms">
                  <strong>Enable SMS notifications for critical stock</strong>
                  <small class="text-muted d-block">Receive urgent SMS alerts when items fall below critical threshold</small>
                </label>
              </div>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="enable_stock_receipt_sms" 
                       name="enable_stock_receipt_sms" 
                       value="1"
                       {{ old('enable_stock_receipt_sms', $settings['enable_stock_receipt_sms'] ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="enable_stock_receipt_sms">
                  <strong>Enable SMS notifications when stock is received</strong>
                  <small class="text-muted d-block">Send SMS to stock keeper and counter staff when new stock is received</small>
                </label>
              </div>
            </div>

            <div class="form-group">
              <label for="low_stock_notification_phones">
                Notification Phone Numbers
                <small class="text-muted d-block">Enter phone numbers to receive alerts (comma-separated, e.g., 255712345678, 255765432109)</small>
              </label>
              <textarea class="form-control @error('low_stock_notification_phones') is-invalid @enderror" 
                        id="low_stock_notification_phones" 
                        name="low_stock_notification_phones" 
                        rows="3" 
                        placeholder="255712345678, 255765432109">{{ old('low_stock_notification_phones', $settings['low_stock_notification_phones']) }}</textarea>
              @error('low_stock_notification_phones')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <small class="form-text text-muted">
                Format: Start with country code (255 for Tanzania), no spaces or dashes. Separate multiple numbers with commas.
              </small>
            </div>
          </div>

          <hr class="my-4">

          <!-- Other Settings -->
          <div class="form-section mb-4">
            <h5 class="mb-3"><i class="fa fa-sliders text-secondary"></i> Other Settings</h5>
            
            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="enable_auto_transfer_notification" 
                       name="enable_auto_transfer_notification" 
                       value="1"
                       {{ old('enable_auto_transfer_notification', $settings['enable_auto_transfer_notification']) ? 'checked' : '' }}>
                <label class="form-check-label" for="enable_auto_transfer_notification">
                  <strong>Enable notifications for stock transfers</strong>
                  <small class="text-muted d-block">Receive notifications when stock transfers are requested or completed</small>
                </label>
              </div>
            </div>
          </div>

          <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Save Settings
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Information Card -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-info-circle text-info"></i> How It Works</h3>
      <div class="tile-body">
        <ul class="list-unstyled">
          <li><i class="fa fa-check text-success"></i> <strong>Low Stock Threshold:</strong> When total stock (warehouse + counter) falls below this number, items will be marked as "Low Stock" in the dashboard.</li>
          <li><i class="fa fa-check text-success"></i> <strong>Critical Stock Threshold:</strong> When stock falls below this number, items will be marked as "Critical" and trigger urgent alerts.</li>
          <li><i class="fa fa-check text-success"></i> <strong>SMS Notifications:</strong> If enabled, SMS alerts will be sent to the phone numbers you specify when stock levels trigger alerts.</li>
          <li><i class="fa fa-check text-success"></i> <strong>Phone Format:</strong> Use format 255XXXXXXXXX (Tanzania country code + phone number without leading 0).</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

