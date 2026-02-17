@extends('layouts.dashboard')

@section('title', 'Counter Settings')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cog"></i> Counter Settings</h1>
    <p>Configure counter dashboard and notification preferences</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Counter Dashboard</a></li>
    <li class="breadcrumb-item">Counter Settings</li>
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
      <h3 class="tile-title">Dashboard Display Settings</h3>
      <div class="tile-body">
        <form method="POST" action="{{ route('bar.counter-settings.update') }}">
          @csrf
          @method('PUT')

          <!-- Display Preferences -->
          <div class="form-section mb-4">
            <h5 class="mb-3"><i class="fa fa-desktop text-primary"></i> Display Preferences</h5>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="auto_refresh_interval">
                    Auto Refresh Interval (seconds) <span class="text-danger">*</span>
                    <small class="text-muted d-block">How often the dashboard automatically refreshes</small>
                  </label>
                  <input type="number" 
                         class="form-control @error('auto_refresh_interval') is-invalid @enderror" 
                         id="auto_refresh_interval" 
                         name="auto_refresh_interval" 
                         value="{{ old('auto_refresh_interval', $settings['auto_refresh_interval']) }}" 
                         min="10" 
                         max="300" 
                         required>
                  @error('auto_refresh_interval')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <small class="form-text text-muted">Current: {{ $settings['auto_refresh_interval'] }} seconds (10-300 seconds)</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="items_per_page">
                    Items Per Page <span class="text-danger">*</span>
                    <small class="text-muted d-block">Number of items to display per page in lists</small>
                  </label>
                  <input type="number" 
                         class="form-control @error('items_per_page') is-invalid @enderror" 
                         id="items_per_page" 
                         name="items_per_page" 
                         value="{{ old('items_per_page', $settings['items_per_page']) }}" 
                         min="10" 
                         max="100" 
                         required>
                  @error('items_per_page')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                  <small class="form-text text-muted">Current: {{ $settings['items_per_page'] }} items</small>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="default_order_view">
                Default Order View <span class="text-danger">*</span>
                <small class="text-muted d-block">Default filter when viewing orders</small>
              </label>
              <select class="form-control @error('default_order_view') is-invalid @enderror" 
                      id="default_order_view" 
                      name="default_order_view" 
                      required>
                <option value="all" {{ old('default_order_view', $settings['default_order_view']) === 'all' ? 'selected' : '' }}>All Orders</option>
                <option value="pending" {{ old('default_order_view', $settings['default_order_view']) === 'pending' ? 'selected' : '' }}>Pending Orders</option>
                <option value="today" {{ old('default_order_view', $settings['default_order_view']) === 'today' ? 'selected' : '' }}>Today's Orders</option>
              </select>
              @error('default_order_view')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <hr class="my-4">

          <!-- Dashboard Features -->
          <div class="form-section mb-4">
            <h5 class="mb-3"><i class="fa fa-dashboard text-info"></i> Dashboard Features</h5>
            
            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="show_pending_orders_badge" 
                       name="show_pending_orders_badge" 
                       value="1"
                       {{ old('show_pending_orders_badge', $settings['show_pending_orders_badge']) ? 'checked' : '' }}>
                <label class="form-check-label" for="show_pending_orders_badge">
                  <strong>Show Pending Orders Badge</strong>
                  <small class="text-muted d-block">Display badge with count of pending orders</small>
                </label>
              </div>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="show_low_stock_alerts" 
                       name="show_low_stock_alerts" 
                       value="1"
                       {{ old('show_low_stock_alerts', $settings['show_low_stock_alerts']) ? 'checked' : '' }}>
                <label class="form-check-label" for="show_low_stock_alerts">
                  <strong>Show Low Stock Alerts</strong>
                  <small class="text-muted d-block">Display low stock items section on dashboard</small>
                </label>
              </div>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="show_revenue_stats" 
                       name="show_revenue_stats" 
                       value="1"
                       {{ old('show_revenue_stats', $settings['show_revenue_stats']) ? 'checked' : '' }}>
                <label class="form-check-label" for="show_revenue_stats">
                  <strong>Show Revenue Statistics</strong>
                  <small class="text-muted d-block">Display revenue statistics cards on dashboard</small>
                </label>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Notification Settings -->
          <div class="form-section mb-4">
            <h5 class="mb-3"><i class="fa fa-bell text-warning"></i> Notification Settings</h5>
            
            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="enable_order_notifications" 
                       name="enable_order_notifications" 
                       value="1"
                       {{ old('enable_order_notifications', $settings['enable_order_notifications']) ? 'checked' : '' }}>
                <label class="form-check-label" for="enable_order_notifications">
                  <strong>Enable Order Notifications</strong>
                  <small class="text-muted d-block">Receive notifications when new orders are placed</small>
                </label>
              </div>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="enable_stock_transfer_notifications" 
                       name="enable_stock_transfer_notifications" 
                       value="1"
                       {{ old('enable_stock_transfer_notifications', $settings['enable_stock_transfer_notifications']) ? 'checked' : '' }}>
                <label class="form-check-label" for="enable_stock_transfer_notifications">
                  <strong>Enable Stock Transfer Notifications</strong>
                  <small class="text-muted d-block">Receive notifications for stock transfer requests and updates</small>
                </label>
              </div>
            </div>

            <div class="form-group">
              <div class="form-check">
                <input class="form-check-input" 
                       type="checkbox" 
                       id="enable_sound_notifications" 
                       name="enable_sound_notifications" 
                       value="1"
                       {{ old('enable_sound_notifications', $settings['enable_sound_notifications']) ? 'checked' : '' }}>
                <label class="form-check-label" for="enable_sound_notifications">
                  <strong>Enable Sound Notifications</strong>
                  <small class="text-muted d-block">Play sound alert for new orders and important notifications</small>
                </label>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <!-- Contact Information for Notifications -->
          <div class="form-section mb-4">
            <h5 class="mb-3"><i class="fa fa-envelope text-success"></i> Notification Contact Information</h5>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="notification_email">
                    Notification Email
                    <small class="text-muted d-block">Email address to receive counter notifications</small>
                  </label>
                  <input type="email" 
                         class="form-control @error('notification_email') is-invalid @enderror" 
                         id="notification_email" 
                         name="notification_email" 
                         value="{{ old('notification_email', $settings['notification_email']) }}" 
                         placeholder="counter@example.com">
                  @error('notification_email')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label for="notification_phone">
                    Notification Phone
                    <small class="text-muted d-block">Phone number to receive SMS notifications (format: 255XXXXXXXXX)</small>
                  </label>
                  <input type="text" 
                         class="form-control @error('notification_phone') is-invalid @enderror" 
                         id="notification_phone" 
                         name="notification_phone" 
                         value="{{ old('notification_phone', $settings['notification_phone']) }}" 
                         placeholder="255712345678">
                  @error('notification_phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>
          </div>

          <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Save Settings
            </button>
            <a href="{{ route('bar.counter.dashboard') }}" class="btn btn-secondary">
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
          <li><i class="fa fa-check text-success"></i> <strong>Auto Refresh:</strong> The dashboard will automatically refresh every set number of seconds to show the latest data.</li>
          <li><i class="fa fa-check text-success"></i> <strong>Display Features:</strong> Toggle dashboard features on/off to customize your view.</li>
          <li><i class="fa fa-check text-success"></i> <strong>Notifications:</strong> Receive alerts for new orders, stock transfers, and other important events.</li>
          <li><i class="fa fa-check text-success"></i> <strong>Contact Info:</strong> Provide email or phone number to receive notifications outside the system.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection


