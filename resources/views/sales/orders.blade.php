@extends('layouts.dashboard')

@section('title', 'Sales Orders & Tracking')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-list"></i> Sales Orders Tracking</h1>
    <p>View, filter, and track all sales orders</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Sales Orders</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">Filter Orders</h3>
      </div>
      <div class="tile-body">
        <form action="{{ route('sales.orders') }}" method="GET" class="row">
          <div class="col-md-3 mb-2">
            <label>Order Status</label>
            <select name="status" class="form-control">
              <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All</option>
              <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="preparing" {{ request('status') == 'preparing' ? 'selected' : '' }}>Preparing</option>
              <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Ready</option>
              <option value="served" {{ request('status') == 'served' ? 'selected' : '' }}>Served</option>
              <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
          </div>
          <div class="col-md-3 mb-2">
            <label>Payment Status</label>
            <select name="payment_status" class="form-control">
              <option value="all" {{ request('payment_status') == 'all' ? 'selected' : '' }}>All</option>
              <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
              <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Partial</option>
            </select>
          </div>
          <div class="col-md-2 mb-2">
            <label>From Date</label>
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
          </div>
          <div class="col-md-2 mb-2">
            <label>To Date</label>
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
          </div>
          <div class="col-md-2 mb-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Filter</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="ordersTable">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Date & Time</th>
                <th>Table/Location</th>
                <th>Items Count</th>
                <th>Total Value</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Tracking</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $order)
                <tr>
                  <td><strong>{{ $order->order_number }}</strong></td>
                  <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                  <td>
                    @if($order->table)
                      {{ $order->table->name }} <small class="text-muted">({{ ucfirst($order->table->location) }})</small>
                    @else
                      Walk-in
                    @endif
                  </td>
                  <td>
                    @php
                      $barCount = $order->items ? $order->items->count() : 0;
                      $foodCount = $order->kitchenOrderItems ? $order->kitchenOrderItems->count() : 0;
                    @endphp
                    {{ $barCount + $foodCount }} item(s)
                  </td>
                  <td class="font-weight-bold">TSh {{ number_format($order->total_amount) }}</td>
                  <td>
                    @switch($order->status)
                      @case('pending') <span class="badge badge-warning">Pending</span> @break
                      @case('preparing') <span class="badge badge-info">Preparing</span> @break
                      @case('ready') <span class="badge badge-primary">Ready</span> @break
                      @case('served') <span class="badge badge-success">Served</span> @break
                      @case('cancelled') <span class="badge badge-danger">Cancelled</span> @break
                      @default <span class="badge badge-secondary">{{ ucfirst($order->status) }}</span>
                    @endswitch
                  </td>
                  <td>
                    @switch($order->payment_status)
                      @case('paid') <span class="badge badge-success">Paid</span> @break
                      @case('pending') <span class="badge badge-warning">Pending</span> @break
                      @case('partial') <span class="badge badge-info">Partial</span> @break
                      @default <span class="badge badge-secondary">{{ ucfirst($order->payment_status) }}</span>
                    @endswitch
                    @if($order->payment_method)
                      <br><small class="text-muted"><i class="fa fa-money"></i> {{ str_replace('_', ' ', ucfirst($order->payment_method)) }}</small>
                    @endif
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline-info view-details" data-id="{{ $order->id }}">
                      <i class="fa fa-eye"></i> View Details
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="fa fa-list-alt fa-3x mb-2 d-block"></i>
                    No sales orders found matching your criteria.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $orders->appends(request()->query())->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details <span id="modalOrderNumber" class="text-primary font-weight-bold ml-2"></span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="text-center p-4" id="modalLoader">
          <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
          <p class="mt-2">Loading order details...</p>
        </div>
        <div id="modalContent" style="display: none;">
          <div class="row mb-4">
            <div class="col-md-6">
              <h6 class="text-muted text-uppercase mb-2">Order Info</h6>
              <p class="mb-1"><strong>Date:</strong> <span id="modalOrderDate"></span></p>
              <p class="mb-1"><strong>Table:</strong> <span id="modalOrderTable"></span></p>
              <p class="mb-1"><strong>Status:</strong> <span id="modalOrderStatus"></span></p>
            </div>
            <div class="col-md-6 text-right">
              <h6 class="text-muted text-uppercase mb-2">Payment Info</h6>
              <p class="mb-1"><strong>Status:</strong> <span id="modalPaymentStatus"></span></p>
              <p class="mb-1"><strong>Method:</strong> <span id="modalPaymentMethod"></span></p>
              <p class="mb-1 text-danger font-weight-bold" style="font-size: 1.2rem;"><strong>Total:</strong> <span id="modalOrderTotal"></span></p>
            </div>
          </div>
          
          <h6 class="text-muted text-uppercase mb-2 border-bottom pb-2">Order Items</h6>
          <div class="table-responsive">
            <table class="table table-sm table-striped">
              <thead>
                <tr>
                  <th>Item Name</th>
                  <th class="text-center">Qty</th>
                  <th class="text-right">Unit Price</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody id="modalItemsBody">
                <!-- Items populated via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
  $('.view-details').click(function() {
    var orderId = $(this).data('id');
    $('#orderDetailsModal').modal('show');
    $('#modalLoader').show();
    $('#modalContent').hide();
    
    // Uses BarOrderController's details route
    var requestUrl = "{{ route('bar.orders.details', ':id') }}".replace(':id', orderId);

    $.ajax({
      url: requestUrl,
      type: 'GET',
      success: function(response) {
        if(response.success && response.order) {
          var order = response.order;
          $('#modalOrderNumber').text(order.order_number);
          $('#modalOrderDate').text(order.date_formatted);
          $('#modalOrderTable').text(order.table_name);
          $('#modalOrderStatus').html('<span class="badge badge-info">' + order.status.toUpperCase() + '</span>');
          $('#modalPaymentStatus').html('<span class="badge badge-success">' + order.payment_status.toUpperCase() + '</span>');
          $('#modalPaymentMethod').text(order.payment_method ? order.payment_method.replace('_', ' ').toUpperCase() : 'N/A');
          $('#modalOrderTotal').text('TSh ' + order.total_amount.toLocaleString());

          var itemsHtml = '';
          
          if (order.items && order.items.length > 0) {
            order.items.forEach(function(item) {
              itemsHtml += '<tr>';
              itemsHtml += '<td>' + item.name + '</td>';
              itemsHtml += '<td class="text-center">' + item.quantity + '</td>';
              itemsHtml += '<td class="text-right">TSh ' + item.price.toLocaleString() + '</td>';
              itemsHtml += '<td class="text-right font-weight-bold">TSh ' + item.total.toLocaleString() + '</td>';
              itemsHtml += '</tr>';
            });
          }
          
          if (order.kitchen_items && order.kitchen_items.length > 0) {
            order.kitchen_items.forEach(function(item) {
              itemsHtml += '<tr>';
              itemsHtml += '<td><span class="badge badge-warning">Food</span> ' + item.name + '</td>';
              itemsHtml += '<td class="text-center">' + item.quantity + '</td>';
              itemsHtml += '<td class="text-right">TSh ' + item.price.toLocaleString() + '</td>';
              itemsHtml += '<td class="text-right font-weight-bold">TSh ' + item.total.toLocaleString() + '</td>';
              itemsHtml += '</tr>';
            });
          }
          
          if (itemsHtml === '') {
            itemsHtml = '<tr><td colspan="4" class="text-center text-muted">No items found</td></tr>';
          }
          
          $('#modalItemsBody').html(itemsHtml);
          
          $('#modalLoader').hide();
          $('#modalContent').fadeIn();
        } else {
          $('#modalLoader').html('<div class="alert alert-danger p-2 text-center">Failed to load order details.</div>');
        }
      },
      error: function() {
        $('#modalLoader').html('<div class="alert alert-danger p-2 text-center">Error communicating with server. Please try again.</div>');
      }
    });
  });
});
</script>
@endpush
