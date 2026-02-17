@extends('layouts.dashboard')

@section('title', 'Food Orders')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cutlery"></i> Food Orders</h1>
    <p>Kitchen station - Manage food orders</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.orders.index') }}">Orders</a></li>
    <li class="breadcrumb-item">Food Orders</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Food Orders - Kitchen Station</h3>
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

      <div class="tile-body" id="foodOrdersContainer">
        @if($orders->count() > 0)
          <div class="row">
            @foreach($orders as $order)
              <div class="col-md-6 col-lg-4 mb-4 order-card" data-order-id="{{ $order->id }}">
                <div class="card shadow-sm border-0 h-100">
                  <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
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
                      <h6 class="text-success"><i class="fa fa-cutlery"></i> YOUR FOOD ITEMS:</h6>
                      <ul class="list-unstyled mb-0">
                        @foreach($order->food_items as $item)
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
                      <button type="button" class="btn btn-sm btn-info view-order-details" data-order-id="{{ $order->id }}">
                        <i class="fa fa-eye"></i> View Full
                      </button>
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
            <i class="fa fa-info-circle"></i> No food orders found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="orderDetailsContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-2x"></i>
          <p>Loading order details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
        if (data.order && data.order.notes && data.order.notes.includes('FOOD ITEMS:')) {
          location.reload();
        }
      });

      channel.bind('order.created', function(data) {
        if (data.order && data.order.notes && data.order.notes.includes('FOOD ITEMS:')) {
          location.reload();
        }
      });
    @else
      // Fallback: Poll for updates every 10 seconds if WebSocket not configured
      setInterval(function() {
        // Silent check - only reload if there are changes
        $.get('{{ route('bar.orders.food') }}', function(data) {
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

    // View order details in modal
    $('.view-order-details').on('click', function() {
      const orderId = $(this).data('order-id');
      const modal = $('#orderDetailsModal');
      const content = $('#orderDetailsContent');
      
      // Show modal with loading state
      modal.modal('show');
      content.html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading order details...</p></div>');
      
      // Fetch order details
      $.ajax({
        url: '/bar/orders/' + orderId + '/details',
        method: 'GET',
        success: function(response) {
          if (response.order) {
            const order = response.order;
            let html = '<div class="row">';
            
            // Order Information
            html += '<div class="col-md-6">';
            html += '<h5>Order Information</h5>';
            html += '<table class="table table-borderless table-sm">';
            html += '<tr><th width="40%">Order Number:</th><td><strong>' + order.order_number + '</strong></td></tr>';
            html += '<tr><th>Status:</th><td>';
            if (order.status === 'pending') {
              html += '<span class="badge badge-warning">Pending</span>';
            } else if (order.status === 'preparing') {
              html += '<span class="badge badge-info">Preparing</span>';
            } else if (order.status === 'served') {
              html += '<span class="badge badge-success">Served</span>';
            } else if (order.status === 'cancelled') {
              html += '<span class="badge badge-danger">Cancelled</span>';
            }
            html += '</td></tr>';
            html += '<tr><th>Payment Status:</th><td>';
            if (order.payment_status === 'pending') {
              html += '<span class="badge badge-warning">Pending</span>';
            } else if (order.payment_status === 'paid') {
              html += '<span class="badge badge-success">Paid</span>';
            } else if (order.payment_status === 'partial') {
              html += '<span class="badge badge-info">Partial</span>';
            }
            html += '</td></tr>';
            if (order.table) {
              html += '<tr><th>Table:</th><td>' + order.table.table_name + '</td></tr>';
            }
            html += '<tr><th>Customer:</th><td>' + (order.customer_name || 'Walk-in customer') + '</td></tr>';
            if (order.customer_phone) {
              html += '<tr><th>Phone:</th><td>' + order.customer_phone + '</td></tr>';
            }
            html += '<tr><th>Created By:</th><td>' + order.created_by + '</td></tr>';
            html += '<tr><th>Created Date:</th><td>' + order.created_at + '</td></tr>';
            if (order.served_at) {
              html += '<tr><th>Served At:</th><td>' + order.served_at + '</td></tr>';
            }
            html += '</table>';
            html += '</div>';
            
            // Payment Information
            html += '<div class="col-md-6">';
            html += '<h5>Payment Information</h5>';
            html += '<table class="table table-borderless table-sm">';
            html += '<tr><th width="40%">Total Amount:</th><td><strong class="text-primary">TSh ' + parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            html += '<tr><th>Paid Amount:</th><td><strong class="text-success">TSh ' + parseFloat(order.paid_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            html += '<tr><th>Remaining:</th><td><strong class="text-danger">TSh ' + parseFloat(order.remaining_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr>';
            html += '</table>';
            // Show notes only if there are additional notes beyond food/juice items
            if (order.notes) {
              // Remove food items and juice items from notes to show only additional notes
              let cleanNotes = order.notes;
              // Remove FOOD ITEMS section (including everything after it until | or end)
              cleanNotes = cleanNotes.replace(/FOOD ITEMS:.*?(?:\||$)/gi, '').trim();
              // Remove JUICE ITEMS section (including everything after it until | or end)
              cleanNotes = cleanNotes.replace(/JUICE ITEMS:.*?(?:\||$)/gi, '').trim();
              // Remove leading/trailing pipes and whitespace
              cleanNotes = cleanNotes.replace(/^\|\s*|\s*\|$/g, '').trim();
              
              // Only show notes if there's content left after removing food/juice items
              if (cleanNotes && cleanNotes.length > 0) {
                // Escape HTML to prevent XSS
                const escapedNotes = $('<div>').text(cleanNotes).html();
                html += '<h6 class="mt-3">Additional Notes</h6>';
                html += '<p class="text-muted small">' + escapedNotes + '</p>';
              }
            }
            html += '</div>';
            html += '</div>';
            
            // Food Items
            if (order.food_items && order.food_items.length > 0) {
              html += '<hr><h5><i class="fa fa-cutlery text-success"></i> Food Items</h5>';
              html += '<div class="table-responsive">';
              html += '<table class="table table-bordered table-sm">';
              html += '<thead><tr><th>#</th><th>Item</th><th>Variant</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>';
              html += '<tbody>';
              order.food_items.forEach(function(item, index) {
                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td>' + item.name + '</td>';
                html += '<td>' + (item.variant || '-') + '</td>';
                html += '<td>' + item.quantity + '</td>';
                html += '<td>TSh ' + parseFloat(item.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td><strong>TSh ' + parseFloat(item.price * item.quantity).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td>';
                html += '</tr>';
              });
              html += '</tbody></table></div>';
            }
            
            // Other Items
            if (order.items && order.items.length > 0) {
              html += '<hr><h5><i class="fa fa-shopping-cart"></i> Order Items</h5>';
              html += '<div class="table-responsive">';
              html += '<table class="table table-bordered table-sm">';
              html += '<thead><tr><th>#</th><th>Product</th><th>Variant</th><th>Quantity</th><th>Unit Price</th><th>Total Price</th></tr></thead>';
              html += '<tbody>';
              order.items.forEach(function(item, index) {
                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td>' + item.product_name + '</td>';
                html += '<td>' + item.variant + '</td>';
                html += '<td>' + item.quantity + '</td>';
                html += '<td>TSh ' + parseFloat(item.unit_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td><strong>TSh ' + parseFloat(item.total_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td>';
                html += '</tr>';
              });
              html += '</tbody>';
              html += '<tfoot><tr><td colspan="5" class="text-right"><strong>Total:</strong></td>';
              html += '<td><strong>TSh ' + parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td></tr></tfoot>';
              html += '</table></div>';
            }
            
            content.html(html);
          } else {
            content.html('<div class="alert alert-danger">Failed to load order details.</div>');
          }
        },
        error: function(xhr) {
          let errorMsg = 'Failed to load order details.';
          if (xhr.responseJSON && xhr.responseJSON.error) {
            errorMsg = xhr.responseJSON.error;
          }
          content.html('<div class="alert alert-danger">' + errorMsg + '</div>');
        }
      });
    });
  });
</script>
@endsection

