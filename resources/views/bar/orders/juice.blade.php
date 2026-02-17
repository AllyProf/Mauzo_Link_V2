@extends('layouts.dashboard')

@section('title', 'Juice Orders')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-tint"></i> Juice Orders</h1>
    <p>Juice station - Manage juice orders</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.orders.index') }}">Orders</a></li>
    <li class="breadcrumb-item">Juice Orders</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Juice Orders - Juice Station</h3>
        <div>
          <a href="{{ route('bar.orders.index') }}" class="btn btn-secondary">
            <i class="fa fa-list"></i> All Orders
          </a>
        </div>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body" id="juiceOrdersContainer">
        @if($orders->count() > 0)
          <div class="row">
            @foreach($orders as $order)
              <div class="col-md-6 col-lg-4 mb-4 order-card" data-order-id="{{ $order->id }}">
                <div class="card shadow-sm border-0 h-100">
                  <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <div>
                      <strong>{{ $order->order_number }}</strong>
                      @if($order->table)
                        <br><small>Table: {{ $order->table->table_number }}</small>
                      @endif
                    </div>
                    <div>
                      @if($order->status === 'pending')
                        <span class="badge badge-warning">Pending</span>
                      @elseif($order->status === 'preparing')
                        <span class="badge badge-info">Preparing</span>
                      @elseif($order->status === 'served')
                        <span class="badge badge-success">Served</span>
                      @elseif($order->status === 'cancelled')
                        <span class="badge badge-danger">Cancelled</span>
                      @endif
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <strong>Customer:</strong> {{ $order->customer_name ?? 'Walk-in' }}<br>
                      @if($order->customer_phone)
                        <small class="text-muted">{{ $order->customer_phone }}</small>
                      @endif
                    </div>

                    <div class="mb-3">
                      <h6 class="text-warning"><i class="fa fa-tint"></i> YOUR JUICE ITEMS:</h6>
                      <ul class="list-unstyled mb-0">
                        @foreach($order->juice_items as $item)
                          <li class="mb-2 p-2 bg-light rounded">
                            <strong>{{ $item['quantity'] }}x</strong> {{ $item['name'] }}
                            @if($item['variant'])
                              <small class="text-muted">({{ $item['variant'] }})</small>
                            @endif
                            <br><small class="text-primary">Tsh {{ number_format($item['price'], 0) }}</small>
                          </li>
                        @endforeach
                      </ul>
                    </div>

                    @if(count($order->other_items) > 0)
                      <div class="mb-3 border-top pt-3">
                        <h6 class="text-muted"><i class="fa fa-info-circle"></i> OTHER ITEMS IN THIS ORDER:</h6>
                        <ul class="list-unstyled mb-0">
                          @foreach($order->other_items as $item)
                            <li class="mb-1">
                              <small>
                                <span class="badge badge-secondary">{{ $item['type'] }}</span>
                                {{ $item['quantity'] }}x {{ $item['name'] }}
                                @if($item['variant'])
                                  <span class="text-muted">({{ $item['variant'] }})</span>
                                @endif
                              </small>
                            </li>
                          @endforeach
                        </ul>
                      </div>
                    @endif

                    <div class="border-top pt-2">
                      <strong>Total: Tsh {{ number_format($order->total_amount, 0) }}</strong><br>
                      <small class="text-muted">{{ $order->created_at->format('M d, Y H:i') }}</small>
                    </div>
                  </div>
                  <div class="card-footer bg-light">
                    <div class="btn-group w-100" role="group">
                      <a href="{{ route('bar.orders.show', $order) }}" class="btn btn-sm btn-info">
                        <i class="fa fa-eye"></i> View Full
                      </a>
                      @if($order->status === 'pending')
                        <button type="button" class="btn btn-sm btn-warning update-status" data-order-id="{{ $order->id }}" data-status="preparing">
                          <i class="fa fa-clock-o"></i> Preparing
                        </button>
                      @elseif($order->status === 'preparing')
                        <button type="button" class="btn btn-sm btn-success update-status" data-order-id="{{ $order->id }}" data-status="served">
                          <i class="fa fa-check"></i> Ready
                        </button>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <div class="mt-3">
            {{ $orders->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No juice orders found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@if(config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key'))
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
@endif
<script>
  $(document).ready(function() {
    // Initialize Pusher for real-time updates
    @if(config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key'))
      const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster', 'mt1') }}',
        encrypted: true
      });

      const channel = pusher.subscribe('orders');
      
      channel.bind('order.updated', function(data) {
        if (data.order && data.order.notes && data.order.notes.includes('JUICE ITEMS:')) {
          location.reload();
        }
      });

      channel.bind('order.created', function(data) {
        if (data.order && data.order.notes && data.order.notes.includes('JUICE ITEMS:')) {
          location.reload();
        }
      });
    @else
      // Fallback: Poll for updates every 10 seconds if WebSocket not configured
      setInterval(function() {
        // Silent check - only reload if there are changes
        $.get('{{ route('bar.orders.juice') }}', function(data) {
          // Simple check - if order count changed, reload
          // This is a basic fallback, full WebSocket is recommended
        });
      }, 10000);
    @endif

    // Update order status
    $('.update-status').on('click', function() {
      const orderId = $(this).data('order-id');
      const status = $(this).data('status');
      const button = $(this);

      $.ajax({
        url: '/bar/orders/' + orderId + '/update-status',
        method: 'POST',
        data: {
          _token: '{{ csrf_token() }}',
          status: status
        },
        beforeSend: function() {
          button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
        },
        success: function(response) {
          location.reload();
        },
        error: function() {
          alert('Failed to update order status');
          button.prop('disabled', false);
        }
      });
    });
  });
</script>
@endsection

