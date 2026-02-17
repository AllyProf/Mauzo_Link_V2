@extends('layouts.dashboard')

@section('title', 'Counter Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-tachometer"></i> Counter Dashboard</h1>
    <p>Welcome back, {{ session('staff_name') }}!</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Counter Dashboard</li>
  </ul>
</div>

<!-- Statistics Cards -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-archive fa-3x"></i>
      <div class="info">
        <h4>Warehouse Stock</h4>
        <p><b>{{ $warehouseStockItems ?? 0 }} items</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-cubes fa-3x"></i>
      <div class="info">
        <h4>Counter Stock Items</h4>
        <p><b>{{ $counterStockItems }}</b></p>
        @if($lowStockItems > 0)
          <small class="text-warning">{{ $lowStockItems }} low stock</small>
        @endif
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-exchange fa-3x"></i>
      <div class="info">
        <h4>Pending Transfers</h4>
        <p><b>{{ $pendingTransfers }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon">
      <i class="icon fa fa-exclamation-triangle fa-3x"></i>
      <div class="info">
        <h4>Low Stock Items</h4>
        <p><b>{{ $lowStockItems }}</b></p>
        <small>Need attention</small>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        <div class="row">
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.counter.waiter-orders') }}" class="btn btn-primary btn-block btn-lg">
              <i class="fa fa-list-alt"></i><br>
              Waiter Orders
              @if($pendingOrders > 0)
                <span class="badge badge-danger">{{ $pendingOrders }}</span>
              @endif
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.counter.customer-orders') }}" class="btn btn-info btn-block btn-lg">
              <i class="fa fa-users"></i><br>
              Customer Orders
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.counter.counter-stock') }}" class="btn btn-success btn-block btn-lg">
              <i class="fa fa-cubes"></i><br>
              Counter Stock
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.stock-transfers.available') }}" class="btn btn-warning btn-block btn-lg">
              <i class="fa fa-archive"></i><br>
              Warehouse Stock
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.counter.analytics') }}" class="btn btn-secondary btn-block btn-lg">
              <i class="fa fa-line-chart"></i><br>
              Analytics & Trends
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.counter.stock-transfer-requests') }}" class="btn btn-primary btn-block btn-lg">
              <i class="fa fa-exchange"></i><br>
              Stock Requests
              @if($pendingTransfers > 0)
                <span class="badge badge-danger">{{ $pendingTransfers }}</span>
              @endif
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.stock-transfers.available') }}" class="btn btn-info btn-block btn-lg">
              <i class="fa fa-plus-circle"></i><br>
              Request Stock
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.counter.record-voice') }}" class="btn btn-warning btn-block btn-lg">
              <i class="fa fa-microphone"></i><br>
              Record Voice
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.counter-settings.index') }}" class="btn btn-secondary btn-block btn-lg">
              <i class="fa fa-cog"></i><br>
              Counter Settings
            </a>
          </div>
          <div class="col-md-3 mb-3">
            <a href="{{ route('bar.stock-transfers.history') }}" class="btn btn-info btn-block btn-lg">
              <i class="fa fa-line-chart"></i><br>
              Track Sales
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Stock Transfer Requests -->
@if(isset($recentTransferRequests) && $recentTransferRequests->count() > 0)
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Recent Stock Transfer Requests</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Transfer #</th>
                <th>Product</th>
                <th>Variant</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Requested By</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentTransferRequests as $transfer)
              <tr>
                <td><strong>{{ $transfer->transfer_number }}</strong></td>
                <td>{{ $transfer->productVariant->product->name ?? 'N/A' }}</td>
                <td>{{ $transfer->productVariant->measurement ?? 'N/A' }}</td>
                <td>{{ number_format($transfer->total_units) }} bottle(s)</td>
                <td>
                  @if($transfer->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @elseif($transfer->status === 'approved')
                    <span class="badge badge-info">Approved</span>
                  @elseif($transfer->status === 'rejected')
                    <span class="badge badge-danger">Rejected</span>
                  @elseif($transfer->status === 'completed')
                    <span class="badge badge-success">Completed</span>
                  @endif
                </td>
                <td>
                  @if($transfer->requestedBy)
                    {{ $transfer->requestedBy->name }}
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td>{{ $transfer->created_at->format('M d, H:i') }}</td>
                <td>
                  <a href="{{ route('bar.stock-transfers.show', $transfer) }}" class="btn btn-sm btn-info">
                    <i class="fa fa-eye"></i> View
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <a href="{{ route('bar.counter.stock-transfer-requests') }}" class="btn btn-primary">
            <i class="fa fa-list"></i> View All Requests
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<!-- Low Stock Items -->
@if(isset($lowStockItemsList) && $lowStockItemsList->count() > 0)
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title"><i class="fa fa-exclamation-triangle text-warning"></i> Low Stock Items</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Warehouse Stock</th>
                <th>Counter Stock</th>
                <th>Total Stock</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lowStockItemsList as $item)
              <tr>
                <td><strong>{{ $item['product_name'] }}</strong></td>
                <td>{{ $item['variant'] }}</td>
                <td>{{ number_format($item['warehouse_qty']) }}</td>
                <td>{{ number_format($item['counter_qty']) }}</td>
                <td><strong class="text-danger">{{ number_format($item['total_qty']) }}</strong></td>
                <td>
                  <span class="badge badge-{{ isset($item['is_critical']) && $item['is_critical'] ? 'danger' : 'warning' }}">
                    {{ isset($item['is_critical']) && $item['is_critical'] ? 'Critical' : 'Low' }}
                  </span>
                </td>
                <td>
                  <a href="{{ route('bar.beverage-inventory.index') }}" class="btn btn-sm btn-warning">
                    <i class="fa fa-plus"></i> Restock
                  </a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <a href="{{ route('bar.beverage-inventory.low-stock-alerts') }}" class="btn btn-warning">
            <i class="fa fa-exclamation-triangle"></i> View All Low Stock Alerts
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

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

@push('scripts')
<script>
$(document).ready(function() {
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
          
          // Payment Method & Details
          if (order.payment_status === 'paid' || order.payment_status === 'partial') {
            if (order.payment_method === 'mobile_money') {
              const providerName = order.mobile_money_number || 'MOBILE MONEY';
              let displayProvider = providerName.toUpperCase();
              // Handle special cases
              if (providerName.toLowerCase().includes('mixx')) {
                displayProvider = 'MIXX BY YAS';
              } else if (providerName.toLowerCase().includes('halopesa')) {
                displayProvider = 'HALOPESA';
              } else if (providerName.toLowerCase().includes('tigo')) {
                displayProvider = 'TIGO PESA';
              } else if (providerName.toLowerCase().includes('airtel')) {
                displayProvider = 'AIRTEL MONEY';
              }
              
              html += '<p><strong>Payment Method:</strong> <span class="badge badge-success">' + displayProvider + '</span></p>';
              if (order.transaction_reference) {
                html += '<p><strong>Transaction Ref:</strong> <code>' + order.transaction_reference + '</code></p>';
              }
            } else if (order.payment_method === 'cash') {
              html += '<p><strong>Payment Method:</strong> <span class="badge badge-warning">CASH</span></p>';
            } else if (order.payment_method) {
              html += '<p><strong>Payment Method:</strong> <span class="badge badge-info">' + order.payment_method.replace('_', ' ').toUpperCase() + '</span></p>';
            }
            
            if (order.paid_by_waiter) {
              html += '<p><strong>Paid By:</strong> ' + order.paid_by_waiter + '</p>';
            }
          }
          
          // Show notes only if there are additional notes beyond food/juice items
          if (order.notes) {
            let cleanNotes = order.notes;
            cleanNotes = cleanNotes.replace(/FOOD ITEMS:.*?(?:\||$)/gi, '').trim();
            cleanNotes = cleanNotes.replace(/JUICE ITEMS:.*?(?:\||$)/gi, '').trim();
            cleanNotes = cleanNotes.replace(/^\|\s*|\s*\|$/g, '').trim();
            
            if (cleanNotes && cleanNotes.length > 0) {
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
          
          // Order Items
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
@endpush

