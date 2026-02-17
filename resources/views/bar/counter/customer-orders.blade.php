@extends('layouts.dashboard')

@section('title', 'Customer Orders')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-users"></i> Customer Orders</h1>
    <p>Manage direct orders from customers</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Counter Dashboard</a></li>
    <li class="breadcrumb-item">Customer Orders</li>
  </ul>
</div>

<!-- Status Summary -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-clock-o fa-3x"></i>
      <div class="info">
        <h4>Pending</h4>
        <p><b>{{ $pendingCount }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-check fa-3x"></i>
      <div class="info">
        <h4>Prepared</h4>
        <p><b>{{ $preparedCount }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-truck fa-3x"></i>
      <div class="info">
        <h4>Served</h4>
        <p><b>{{ $servedCount }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-list fa-3x"></i>
      <div class="info">
        <h4>Total Orders</h4>
        <p><b>{{ $orders->total() }}</b></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Customer Orders</h3>
        <div>
          <a href="{{ route('bar.counter.dashboard') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Dashboard
          </a>
        </div>
      </div>

      <div class="tile-body">
        @if($orders->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Order #</th>
                  <th>Customer</th>
                  <th>Items</th>
                  <th>Total</th>
                  <th>Status</th>
                  <th>Payment</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($orders as $order)
                <tr>
                  <td><strong>{{ $order->order_number }}</strong></td>
                  <td>
                    @if($order->customer_name)
                      {{ $order->customer_name }}
                      @if($order->customer_phone)
                        <br><small class="text-muted">{{ $order->customer_phone }}</small>
                      @endif
                    @else
                      <span class="text-muted">Walk-in</span>
                    @endif
                  </td>
                  <td>{{ $order->items->count() }} item(s)</td>
                  <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
                  <td>
                    <span class="badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'prepared' ? 'info' : ($order->status === 'served' ? 'success' : 'secondary')) }}">
                      {{ ucfirst($order->status) }}
                    </span>
                  </td>
                  <td>
                    @if($order->payment_status === 'paid')
                      <span class="badge badge-success">Paid</span>
                    @else
                      <span class="badge badge-warning">Pending</span>
                    @endif
                  </td>
                  <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                  <td>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-info view-order-btn" data-order-id="{{ $order->id }}">
                        <i class="fa fa-eye"></i> View
                      </button>
                      @if($order->status === 'pending')
                        <button class="btn btn-sm btn-success update-status-btn" 
                                data-order-id="{{ $order->id }}" 
                                data-status="prepared">
                          <i class="fa fa-check"></i> Mark Prepared
                        </button>
                      @elseif($order->status === 'prepared')
                        <button class="btn btn-sm btn-primary update-status-btn" 
                                data-order-id="{{ $order->id }}" 
                                data-status="served">
                          <i class="fa fa-truck"></i> Mark Served
                        </button>
                      @endif
                      @if($order->status === 'served' && $order->payment_status !== 'paid')
                        <button class="btn btn-sm btn-success mark-paid-btn" data-order-id="{{ $order->id }}">
                          <i class="fa fa-money"></i> Mark Paid
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-center">
            {{ $orders->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No customer orders found.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="order-details-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="order-details-content">
        <!-- Order details will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal fade" id="mark-paid-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Mark Order as Paid</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="mark-paid-form">
        <div class="modal-body">
          <input type="hidden" id="paid-order-id" name="order_id">
          <div class="form-group">
            <label>Paid Amount</label>
            <input type="number" class="form-control" id="paid-amount" name="paid_amount" step="0.01" required>
            <small class="form-text text-muted">Total Amount: <span id="order-total-amount"></span></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Mark as Paid</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Update order status
  $(document).on('click', '.update-status-btn', function() {
    const orderId = $(this).data('order-id');
    const status = $(this).data('status');
    
    Swal.fire({
      title: 'Update Order Status?',
      text: 'Change status to ' + status + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Update',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#940000'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '/bar/counter/orders/' + orderId + '/update-status',
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          data: {
            status: status
          },
          success: function(response) {
            if (response.success) {
              Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Order status updated successfully',
                confirmButtonColor: '#940000'
              }).then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to update order status';
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error,
              confirmButtonColor: '#940000'
            });
          }
        });
      }
    });
  });

  // Mark as paid
  $(document).on('click', '.mark-paid-btn', function() {
    const orderId = $(this).data('order-id');
    const row = $(this).closest('tr');
    const totalAmount = row.find('td:nth-child(4)').text().trim().replace('TSh ', '').replace(/,/g, '');
    
    $('#paid-order-id').val(orderId);
    $('#paid-amount').val(totalAmount);
    $('#order-total-amount').text('TSh ' + parseFloat(totalAmount).toLocaleString('en-US', {minimumFractionDigits: 2}));
    $('#mark-paid-modal').modal('show');
  });

  // Submit mark paid form
  $('#mark-paid-form').on('submit', function(e) {
    e.preventDefault();
    const orderId = $('#paid-order-id').val();
    const paidAmount = parseFloat($('#paid-amount').val());
    
    $.ajax({
      url: '/bar/counter/orders/' + orderId + '/mark-paid',
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      data: {
        paid_amount: paidAmount,
        waiter_id: null // Customer orders don't have waiter
      },
      success: function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Payment recorded successfully',
            confirmButtonColor: '#940000'
          }).then(() => {
            location.reload();
          });
        }
      },
      error: function(xhr) {
        const error = xhr.responseJSON?.error || 'Failed to record payment';
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error,
          confirmButtonColor: '#940000'
        });
      }
    });
  });

  // View order details
  $(document).on('click', '.view-order-btn', function() {
    const orderId = $(this).data('order-id');
    const row = $(this).closest('tr');
    
    const orderNumber = row.find('td:first').text().trim();
    const customer = row.find('td:nth-child(2)').html();
    const items = row.find('td:nth-child(3)').text().trim();
    const total = row.find('td:nth-child(4)').text().trim();
    const status = row.find('td:nth-child(5)').html();
    const payment = row.find('td:nth-child(6)').html();
    const date = row.find('td:nth-child(7)').text().trim();
    
    const content = `
      <div class="row">
        <div class="col-md-6">
          <h6>Order Information</h6>
          <p><strong>Order #:</strong> ${orderNumber}</p>
          <p><strong>Customer:</strong> ${customer}</p>
          <p><strong>Date:</strong> ${date}</p>
        </div>
        <div class="col-md-6">
          <h6>Status & Payment</h6>
          <p><strong>Status:</strong> ${status}</p>
          <p><strong>Payment:</strong> ${payment}</p>
          <p><strong>Total:</strong> ${total}</p>
        </div>
      </div>
      <hr>
      <h6>Order Items</h6>
      ${items}
    `;
    
    $('#order-details-content').html(content);
    $('#order-details-modal').modal('show');
  });
</script>
@endpush








