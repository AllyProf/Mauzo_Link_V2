@extends('layouts.dashboard')

@section('title', 'Chef Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cutlery"></i> Chef Dashboard</h1>
    <p>Manage food orders from kitchen</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Chef Dashboard</li>
  </ul>
</div>

<!-- Statistics Cards -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-clock-o fa-3x"></i>
      <div class="info">
        <h4>Pending</h4>
        <p><b>{{ $stats['pending_count'] }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-fire fa-3x"></i>
      <div class="info">
        <h4>Preparing</h4>
        <p><b>{{ $stats['preparing_count'] }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-check-circle fa-3x"></i>
      <div class="info">
        <h4>Ready</h4>
        <p><b>{{ $stats['ready_count'] }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-trophy fa-3x"></i>
      <div class="info">
        <h4>Today Completed</h4>
        <p><b>{{ $stats['today_completed'] }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Audio Enable Banner -->
<div id="audio-enable-banner" class="alert alert-warning" style="display: none; margin-bottom: 20px;">
  <div class="row align-items-center">
    <div class="col-md-10">
      <strong><i class="fa fa-volume-up"></i> Audio Not Enabled</strong>
      <p class="mb-0">Click the button below to enable audio announcements for new orders.</p>
    </div>
    <div class="col-md-2 text-right">
      <button id="enable-audio-btn" class="btn btn-primary btn-lg">
        <i class="fa fa-volume-up"></i> Enable Audio
      </button>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title"><i class="fa fa-cutlery"></i> Kitchen Orders</h3>
        <div class="btn-group">
          <button class="btn btn-primary" id="refresh-btn">
            <i class="fa fa-refresh"></i> Refresh
          </button>
          <a href="{{ route('bar.chef.kds') }}" class="btn btn-info" target="_blank">
            <i class="fa fa-tv"></i> KDS View
          </a>
          <a href="{{ route('bar.chef.food-items') }}" class="btn btn-success">
            <i class="fa fa-cutlery"></i> Manage Food Items
          </a>
          <a href="{{ route('bar.chef.ingredients') }}" class="btn btn-warning">
            <i class="fa fa-flask"></i> Manage Ingredients
          </a>
        </div>
      </div>
      <div class="tile-body">
        <!-- Pending Orders Section -->
        <div class="mb-4">
          <h4 class="text-danger">
            <i class="fa fa-clock-o"></i> 🔴 Pending Orders ({{ $stats['pending_count'] }})
          </h4>
          <div class="row" id="pending-orders">
            @forelse($pendingOrders as $orderData)
              @foreach($orderData['kitchen_items'] as $item)
                <div class="col-md-4 mb-3" data-order-id="{{ $orderData['order']->id }}" data-item-id="{{ $item->id }}">
                  <div class="card border-danger shadow-sm">
                    <div class="card-header bg-danger text-white">
                      <strong>Order #{{ $orderData['order']->order_number }}</strong>
                      @if($orderData['order']->table)
                        <span class="badge badge-light">Table: {{ $orderData['order']->table->table_number }}</span>
                      @endif
                      <small class="float-right">{{ $orderData['order']->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="card-body">
                      <p class="mb-2">
                        <i class="fa fa-user"></i> <strong>Waiter:</strong> {{ $orderData['order']->waiter->full_name ?? 'N/A' }}
                      </p>
                      @if($orderData['order']->customer_name)
                        <p class="mb-2">
                          <i class="fa fa-user-circle"></i> <strong>Customer:</strong> {{ $orderData['order']->customer_name }}
                        </p>
                      @endif
                      <hr>
                      <div class="mb-2">
                        <strong>{{ $item->quantity }}x {{ $item->food_item_name }}</strong>
                        @if($item->variant_name)
                          <br><small class="text-muted">{{ $item->variant_name }}</small>
                        @endif
                        @if($item->special_instructions)
                          <br><small class="text-warning"><i class="fa fa-exclamation-triangle"></i> {{ $item->special_instructions }}</small>
                        @endif
                      </div>
                      @if($orderData['order']->notes && strpos($orderData['order']->notes, 'ORDER NOTES:') !== false)
                        <div class="alert alert-info mb-2" style="font-size: 0.85rem;">
                          <strong>Order Notes:</strong> {{ str_replace('ORDER NOTES: ', '', explode(' | ', $orderData['order']->notes)[1] ?? '') }}
                        </div>
                      @endif
                      <button class="btn btn-success btn-block start-cooking-btn" data-item-id="{{ $item->id }}">
                        <i class="fa fa-play"></i> Start Cooking
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            @empty
              <div class="col-12">
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> No pending orders at the moment.
                </div>
              </div>
            @endforelse
          </div>
        </div>

        <!-- Preparing Orders Section -->
        <div class="mb-4">
          <h4 class="text-warning">
            <i class="fa fa-fire"></i> 🟡 Preparing ({{ $stats['preparing_count'] }})
          </h4>
          <div class="row" id="preparing-orders">
            @forelse($preparingOrders as $orderData)
              @foreach($orderData['kitchen_items'] as $item)
                <div class="col-md-4 mb-3" data-order-id="{{ $orderData['order']->id }}" data-item-id="{{ $item->id }}">
                  <div class="card border-warning shadow-sm">
                    <div class="card-header bg-warning text-dark">
                      <strong>Order #{{ $orderData['order']->order_number }}</strong>
                      @if($orderData['order']->table)
                        <span class="badge badge-dark">Table: {{ $orderData['order']->table->table_number }}</span>
                      @endif
                      <small class="float-right">
                        @if($item->prepared_at)
                          ⏱️ {{ $item->prepared_at->diffForHumans() }}
                        @endif
                      </small>
                    </div>
                    <div class="card-body">
                      <p class="mb-2">
                        <i class="fa fa-user"></i> <strong>Waiter:</strong> {{ $orderData['order']->waiter->full_name ?? 'N/A' }}
                      </p>
                      <hr>
                      <div class="mb-2">
                        <strong>{{ $item->quantity }}x {{ $item->food_item_name }}</strong>
                        @if($item->variant_name)
                          <br><small class="text-muted">{{ $item->variant_name }}</small>
                        @endif
                        @if($item->special_instructions)
                          <br><small class="text-warning"><i class="fa fa-exclamation-triangle"></i> {{ $item->special_instructions }}</small>
                        @endif
                      </div>
                      <button class="btn btn-primary btn-block mark-ready-btn" data-item-id="{{ $item->id }}">
                        <i class="fa fa-check"></i> Mark Ready
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            @empty
              <div class="col-12">
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> No orders being prepared at the moment.
                </div>
              </div>
            @endforelse
          </div>
        </div>

        <!-- Ready Orders Section -->
        <div class="mb-4">
          <h4 class="text-info">
            <i class="fa fa-check-circle"></i> 🟢 Ready for Pickup ({{ $stats['ready_count'] }})
          </h4>
          <div class="row" id="ready-orders">
            @forelse($readyOrders as $orderData)
              @foreach($orderData['kitchen_items'] as $item)
                <div class="col-md-4 mb-3" data-order-id="{{ $orderData['order']->id }}" data-item-id="{{ $item->id }}">
                  <div class="card border-info shadow-sm">
                    <div class="card-header bg-info text-white">
                      <strong>Order #{{ $orderData['order']->order_number }}</strong>
                      @if($orderData['order']->table)
                        <span class="badge badge-light">Table: {{ $orderData['order']->table->table_number }}</span>
                      @endif
                      <small class="float-right">
                        @if($item->ready_at)
                          ⏱️ Ready {{ $item->ready_at->diffForHumans() }}
                        @endif
                      </small>
                    </div>
                    <div class="card-body">
                      <p class="mb-2">
                        <i class="fa fa-user"></i> <strong>Waiter:</strong> {{ $orderData['order']->waiter->full_name ?? 'N/A' }}
                      </p>
                      <hr>
                      <div class="mb-2">
                        <strong>{{ $item->quantity }}x {{ $item->food_item_name }}</strong>
                        @if($item->variant_name)
                          <br><small class="text-muted">{{ $item->variant_name }}</small>
                        @endif
                      </div>
                      <div class="alert alert-success mb-3">
                        <i class="fa fa-bell"></i> Waiting for waiter pickup
                      </div>
                      <button class="btn btn-success btn-block btn-lg mark-taken-btn" 
                              data-item-id="{{ $item->id }}" 
                              data-order-id="{{ $orderData['order']->id }}"
                              data-order-number="{{ $orderData['order']->order_number }}"
                              style="font-size: 16px; padding: 12px; font-weight: bold;">
                        <i class="fa fa-check-circle"></i> Mark as Taken
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            @empty
              <div class="col-12">
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> No ready orders at the moment.
                </div>
              </div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let audioEnabled = false;
  let lastOrderId = {{ $pendingOrders->max('order.id') ?? 0 }};
  let refreshInterval;

  // Enable audio
  $('#enable-audio-btn').on('click', function() {
    // Request audio permission
    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjGH0fPTgjMGHm7A7+OZURAJR6Hh8sFvJQUwgM/y2Yk3CBxou+3nn00QDE+n4/C2YxwGOJLX8sx5LAUkd8fw3ZBACg==');
    audio.play().then(() => {
      audioEnabled = true;
      $('#audio-enable-banner').hide();
      localStorage.setItem('chef_audio_enabled', 'true');
    }).catch(() => {
      alert('Please allow audio permissions in your browser settings.');
    });
  });

  // Check if audio was previously enabled
  if (localStorage.getItem('chef_audio_enabled') === 'true') {
    audioEnabled = true;
    $('#audio-enable-banner').hide();
  } else {
    $('#audio-enable-banner').show();
  }

  // Start cooking button
  $(document).on('click', '.start-cooking-btn', function() {
    const itemId = $(this).data('item-id');
    const btn = $(this);
    
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Starting...');

    $.ajax({
      url: `/bar/chef/kitchen-items/${itemId}/update-status`,
      method: 'POST',
      data: {
        status: 'preparing',
        _token: '{{ csrf_token() }}'
      },
      success: function(response) {
        if (response.success) {
          location.reload();
        } else {
          // Format error message for insufficient ingredients
          let errorMessage = response.error || 'Unknown error';
          let errorTitle = 'Error';
          let errorHtml = errorMessage;
          
          // Check if it's an insufficient ingredients error
          if (errorMessage.includes('Insufficient ingredients')) {
            errorTitle = 'Insufficient Ingredients';
            if (response.missing_ingredients && response.missing_ingredients.length > 0) {
              errorHtml = '<div class="text-left"><strong>Insufficient ingredients available:</strong><ul class="mt-2 mb-0">';
              response.missing_ingredients.forEach(function(missing) {
                errorHtml += `<li><strong>${missing.ingredient}:</strong> Need ${missing.required} ${missing.unit}, Have ${missing.available} ${missing.unit}</li>`;
              });
              errorHtml += '</ul></div>';
            } else {
              // Fallback: parse the error message
              errorHtml = '<div class="text-left">' + errorMessage.replace(/; /g, '<br>') + '</div>';
            }
          }
          
          Swal.fire({
            title: errorTitle,
            html: errorHtml,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
          });
          
          btn.prop('disabled', false).html('<i class="fa fa-play"></i> Start Cooking');
        }
      },
      error: function(xhr) {
        const errorMessage = xhr.responseJSON?.error || 'Failed to update status';
        let errorHtml = errorMessage;
        
        // Check if it's an insufficient ingredients error
        if (errorMessage.includes('Insufficient ingredients')) {
          if (xhr.responseJSON?.missing_ingredients && xhr.responseJSON.missing_ingredients.length > 0) {
            errorHtml = '<div class="text-left"><strong>Insufficient ingredients available:</strong><ul class="mt-2 mb-0">';
            xhr.responseJSON.missing_ingredients.forEach(function(missing) {
              errorHtml += `<li><strong>${missing.ingredient}:</strong> Need ${missing.required} ${missing.unit}, Have ${missing.available} ${missing.unit}</li>`;
            });
            errorHtml += '</ul></div>';
          } else {
            errorHtml = '<div class="text-left">' + errorMessage.replace(/; /g, '<br>') + '</div>';
          }
        }
        
        Swal.fire({
          title: 'Error!',
          html: errorHtml,
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#d33'
        });
        
        btn.prop('disabled', false).html('<i class="fa fa-play"></i> Start Cooking');
      }
    });
  });

  // Mark ready button
  $(document).on('click', '.mark-ready-btn', function() {
    const itemId = $(this).data('item-id');
    const btn = $(this);
    
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Marking...');

    $.ajax({
      url: `/bar/chef/kitchen-items/${itemId}/update-status`,
      method: 'POST',
      data: {
        status: 'ready',
        _token: '{{ csrf_token() }}'
      },
      success: function(response) {
        if (response.success) {
          // Play notification sound
          if (audioEnabled) {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjGH0fPTgjMGHm7A7+OZURAJR6Hh8sFvJQUwgM/y2Yk3CBxou+3nn00QDE+n4/C2YxwGOJLX8sx5LAUkd8fw3ZBACg==');
            audio.play();
          }
          location.reload();
        } else {
          Swal.fire({
            title: 'Error!',
            text: response.error || 'Failed to update status',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#d33'
          });
          btn.prop('disabled', false).html('<i class="fa fa-check"></i> Mark Ready');
        }
      },
      error: function(xhr) {
        Swal.fire({
          title: 'Error!',
          text: xhr.responseJSON?.error || 'Failed to update status',
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#d33'
        });
        btn.prop('disabled', false).html('<i class="fa fa-check"></i> Mark Ready');
      }
    });
  });

  // Mark item as taken (when waiter picks up)
  $(document).on('click', '.mark-taken-btn', function() {
    const itemId = $(this).data('item-id');
    const orderId = $(this).data('order-id');
    const orderNumber = $(this).data('order-number');
    const btn = $(this);
    
    // Show SweetAlert confirmation
    Swal.fire({
      title: 'Mark as Taken?',
      html: `Are you sure you want to mark this item as taken for <strong>Order #${orderNumber}</strong>?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: '<i class="fa fa-check-circle"></i> Yes, Mark as Taken',
      cancelButtonText: '<i class="fa fa-times"></i> Cancel',
      reverseButtons: true,
      focusConfirm: false,
      allowOutsideClick: false
    }).then((result) => {
      if (result.isConfirmed) {
        // Disable button and show loading state
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Marking...');
        
        // Show loading SweetAlert
        Swal.fire({
          title: 'Processing...',
          text: 'Marking item as taken',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        $.ajax({
          url: `/bar/chef/kitchen-items/${itemId}/mark-taken`,
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          success: function(response) {
            if (response.success) {
              // Show success message
              Swal.fire({
                title: 'Success!',
                html: `Item for <strong>Order #${orderNumber}</strong> has been marked as taken.`,
                icon: 'success',
                confirmButtonColor: '#28a745',
                confirmButtonText: 'OK',
                timer: 2000,
                timerProgressBar: true
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                title: 'Error!',
                text: response.error || 'Failed to mark as taken',
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK'
              });
              btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> Mark as Taken');
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to mark item as taken';
            Swal.fire({
              title: 'Error!',
              text: error,
              icon: 'error',
              confirmButtonColor: '#dc3545',
              confirmButtonText: 'OK'
            });
            btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> Mark as Taken');
          }
        });
      }
    });
  });

  // Refresh button
  $('#refresh-btn').on('click', function() {
    location.reload();
  });

  // Auto-refresh every 10 seconds
  function checkForNewOrders() {
    $.ajax({
      url: '{{ route("bar.chef.latest-orders") }}',
      method: 'GET',
      data: {
        last_order_id: lastOrderId
      },
      success: function(response) {
        if (response.success && response.new_orders.length > 0) {
          // Play notification sound
          if (audioEnabled) {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjGH0fPTgjMGHm7A7+OZURAJR6Hh8sFvJQUwgM/y2Yk3CBxou+3nn00QDE+n4/C2YxwGOJLX8sx5LAUkd8fw3ZBACg==');
            audio.play();
          }
          
          // Show notification
          if (Notification.permission === 'granted') {
            new Notification('New Order Received!', {
              body: 'You have ' + response.new_orders.length + ' new order(s)',
              icon: '/img/notification-icon.png'
            });
          }
          
          // Update last order ID
          lastOrderId = response.latest_order_id;
          
          // Reload page to show new orders
          setTimeout(() => {
            location.reload();
          }, 2000);
        }
      }
    });
  }

  // Request notification permission
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }

  // Start auto-refresh
  refreshInterval = setInterval(checkForNewOrders, 10000); // Check every 10 seconds

  // Cleanup on page unload
  $(window).on('beforeunload', function() {
    if (refreshInterval) {
      clearInterval(refreshInterval);
    }
  });
</script>
@endpush

