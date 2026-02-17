@extends('layouts.dashboard')

@section('title', 'Orders')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-shopping-cart"></i> Orders</h1>
    <p>Manage customer orders</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Orders</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Orders</h3>
        <a href="{{ route('bar.orders.create') }}" class="btn btn-primary">
          <i class="fa fa-plus"></i> New Order
        </a>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        @if($orders->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="ordersTable">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Table</th>
                  <th>Customer</th>
                  <th>Items</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th>Created By</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($orders as $order)
                  <tr>
                    <td><strong>{{ $order->order_number }}</strong></td>
                    <td>
                      @if($order->table)
                        {{ $order->table->table_number }}
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if($order->customer_name)
                        {{ $order->customer_name }}<br>
                        @if($order->customer_phone)
                          <small class="text-muted">{{ $order->customer_phone }}</small>
                        @endif
                      @else
                        <span class="text-muted">Walk-in</span>
                      @endif
                    </td>
                    <td>{{ $order->items->count() }} item(s)</td>
                    <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
                    <td>
                      @if($order->status === 'pending')
                        <span class="badge badge-warning">Pending</span>
                      @elseif($order->status === 'preparing')
                        <span class="badge badge-info">Preparing</span>
                      @elseif($order->status === 'served')
                        <span class="badge badge-success">Served</span>
                      @elseif($order->status === 'cancelled')
                        <span class="badge badge-danger">Cancelled</span>
                      @endif
                    </td>
                    <td>
                      @if($order->payment_status === 'pending')
                        <span class="badge badge-warning">Pending</span>
                      @elseif($order->payment_status === 'paid')
                        <span class="badge badge-success">Paid</span>
                      @elseif($order->payment_status === 'partial')
                        <span class="badge badge-info">Partial</span>
                      @elseif($order->payment_status === 'refunded')
                        <span class="badge badge-danger">Refunded</span>
                      @endif
                    </td>
                    <td>{{ $order->createdBy->name ?? 'N/A' }}</td>
                    <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      <button type="button" class="btn btn-info btn-sm view-order-details" data-order-id="{{ $order->id }}">
                        <i class="fa fa-eye"></i> View
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $orders->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No orders found. 
            <a href="{{ route('bar.orders.create') }}">Create your first order</a> to get started.
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
<script type="text/javascript" src="{{ asset('js/admin/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/admin/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    if (typeof $.fn.DataTable !== 'undefined') {
      try {
        $('#ordersTable').DataTable({
          "paging": false,
          "info": false,
          "searching": true,
        });
      } catch(e) {
        console.warn('DataTable initialization failed:', e);
      }
    }

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
            
            // Juice Items
            if (order.juice_items && order.juice_items.length > 0) {
              html += '<hr><h5><i class="fa fa-glass text-info"></i> Juice Items</h5>';
              html += '<div class="table-responsive">';
              html += '<table class="table table-bordered table-sm">';
              html += '<thead><tr><th>#</th><th>Item</th><th>Variant</th><th>Quantity</th><th>Price</th><th>Total</th></tr></thead>';
              html += '<tbody>';
              order.juice_items.forEach(function(item, index) {
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



