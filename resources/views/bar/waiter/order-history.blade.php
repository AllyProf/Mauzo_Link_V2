@extends('layouts.dashboard')

@section('title', 'Order History')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-history"></i> Order History</h1>
    <p>View your order history</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.waiter.dashboard') }}">Waiter Dashboard</a></li>
    <li class="breadcrumb-item">Order History</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">
          <i class="fa fa-list"></i> My Orders
        </h3>
        <div>
          <a href="{{ route('bar.waiter.dashboard') }}" class="btn btn-primary">
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
                  <th>Source</th>
                  <th>Items</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                  <th>Payment Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($orders as $order)
                <tr>
                  <td><strong>{{ $order->order_number }}</strong></td>
                  <td>
                    @if($order->order_source === 'kiosk')
                      <span class="badge badge-info"><i class="fa fa-desktop"></i> Kiosk</span>
                    @else
                      <span class="badge badge-secondary"><i class="fa fa-globe"></i> Web</span>
                    @endif
                  </td>
                  <td>
                    <ul class="list-unstyled mb-0">
                      @foreach($order->items as $item)
                      <li>
                        <small>{{ $item->quantity }}x {{ $item->productVariant->product->name }} - {{ $item->productVariant->measurement }}</small>
                      </li>
                      @endforeach
                      @foreach($order->kitchenOrderItems as $item)
                      <li>
                        <small>{{ $item->quantity }}x {{ $item->food_item_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif</small>
                      </li>
                      @endforeach
                    </ul>
                  </td>
                  <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
                  <td>
                    <span class="badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'prepared' ? 'info' : ($order->status === 'served' ? 'success' : 'secondary')) }}">
                      {{ ucfirst($order->status) }}
                    </span>
                  </td>
                  <td>
                    @if($order->payment_status === 'paid')
                      <span class="badge badge-success">
                        <i class="fa fa-check"></i> Paid
                      </span>
                      @if($order->payment_method)
                        <br><small class="text-muted">
                          <i class="fa fa-{{ $order->payment_method === 'cash' ? 'money' : 'mobile' }}"></i> 
                          {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                        </small>
                        @if($order->payment_method === 'mobile_money' && $order->transaction_reference)
                          <br><small class="text-muted">Ref: {{ $order->transaction_reference }}</small>
                        @endif
                      @endif
                      @if($order->paidByWaiter)
                        <br><small class="text-muted">Collected by: {{ $order->paidByWaiter->full_name }}</small>
                      @endif
                    @elseif($order->payment_status === 'partial')
                      <span class="badge badge-warning">
                        Partial: TSh {{ number_format($order->paid_amount, 2) }}
                      </span>
                    @else
                      <span class="badge badge-danger">Pending</span>
                    @endif
                  </td>
                  <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                  <td>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-info view-order-details" data-order-id="{{ $order->id }}">
                        <i class="fa fa-eye"></i> View
                      </button>
                      @if($order->canRecordPayment())
                        <button class="btn btn-sm btn-success record-payment-btn" 
                                data-order-id="{{ $order->id }}" 
                                data-order-number="{{ $order->order_number }}"
                                data-order-amount="{{ $order->total_amount }}">
                          <i class="fa fa-money"></i> Record Payment
                        </button>
                      @elseif($order->payment_status !== 'paid' && $order->status !== 'cancelled')
                        <button class="btn btn-sm btn-secondary" 
                                data-toggle="tooltip" 
                                data-placement="top"
                                title="{{ $order->getPaymentReadinessMessage() }}"
                                disabled>
                          <i class="fa fa-money"></i> Record Payment
                        </button>
                      @endif
                    </div>
                    @if($order->payment_status !== 'paid' && $order->status !== 'cancelled' && !$order->canRecordPayment())
                      <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                        <i class="fa fa-info-circle"></i> {{ $order->getPaymentReadinessMessage() }}
                      </small>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-center mt-3">
            {{ $orders->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> You haven't placed any orders yet.
            <a href="{{ route('bar.waiter.dashboard') }}" class="alert-link">Place your first order</a>
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
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="record-payment-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Record Payment</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="record-payment-form">
        <div class="modal-body">
          <input type="hidden" id="payment-order-id" name="order_id">
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> 
            <strong>Order #<span id="payment-order-number"></span></strong><br>
            <small>Total Amount: <strong>TSh <span id="payment-order-amount"></span></strong></small>
          </div>
          
          <div class="form-group">
            <label>Payment Method <span class="text-danger">*</span></label>
            <select class="form-control" id="payment-method-select" name="payment_method" required>
              <option value="">Select Payment Method</option>
              <option value="cash">Cash</option>
              <option value="mobile_money">Mobile Money (M-Pesa)</option>
            </select>
          </div>
          
          <!-- Mobile Money Fields (shown when mobile_money is selected) -->
          <div id="mobile-money-payment-fields" style="display: none;">
            <div class="form-group">
              <label>Customer Phone Number (M-Pesa) <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="mobile-money-number-input" name="mobile_money_number" placeholder="+255XXXXXXXXX" value="+255">
              <small class="form-text text-muted">Customer's phone number for M-Pesa payment</small>
            </div>
            <div class="form-group">
              <label>Transaction Reference <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="transaction-reference-input" name="transaction_reference" placeholder="e.g., QGH7X8Y9Z" maxlength="50">
              <small class="form-text text-muted">M-Pesa transaction code from customer's confirmation SMS</small>
            </div>
            <div class="alert alert-warning">
              <i class="fa fa-exclamation-triangle"></i> 
              <strong>Important:</strong> Verify the transaction reference with the customer before marking as received.
            </div>
          </div>
          
          <div class="alert alert-success" id="cash-payment-info" style="display: none;">
            <i class="fa fa-check-circle"></i> 
            <strong>Cash Payment:</strong> Mark as received after collecting cash from customer.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">
            <i class="fa fa-check"></i> Mark as Received
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Initialize tooltips
  $(function () {
    $('[data-toggle="tooltip"]').tooltip();
  });

  // View order details
  $(document).on('click', '.view-order-details', function() {
    const orderId = $(this).data('order-id');
    const row = $(this).closest('tr');
    
    // Get order data from row
    const orderNumber = row.find('td:first').text().trim();
    const source = row.find('td:nth-child(2)').html();
    const items = row.find('td:nth-child(3)').html();
    const total = row.find('td:nth-child(4)').text().trim();
    const status = row.find('td:nth-child(5)').html();
    const payment = row.find('td:nth-child(6)').html();
    const date = row.find('td:nth-child(7)').text().trim();
    
    const content = `
      <div class="row">
        <div class="col-md-6">
          <h6><i class="fa fa-info-circle"></i> Order Information</h6>
          <p><strong>Order #:</strong> ${orderNumber}</p>
          <p><strong>Source:</strong> ${source}</p>
          <p><strong>Date:</strong> ${date}</p>
        </div>
        <div class="col-md-6">
          <h6><i class="fa fa-tag"></i> Status & Payment</h6>
          <p><strong>Status:</strong> ${status}</p>
          <p><strong>Payment:</strong> ${payment}</p>
          <p><strong>Total:</strong> ${total}</p>
        </div>
      </div>
      <hr>
      <h6><i class="fa fa-shopping-cart"></i> Order Items</h6>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Unit Price</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            ${items.replace(/<li>/g, '<tr><td>').replace(/<\/li>/g, '</td><td>-</td><td>-</td><td>-</td></tr>')}
          </tbody>
        </table>
      </div>
    `;
    
    $('#order-details-content').html(content);
    $('#order-details-modal').modal('show');
  });

  // Show/hide mobile money fields based on payment method selection
  $('#payment-method-select').on('change', function() {
    const paymentMethod = $(this).val();
    if (paymentMethod === 'mobile_money') {
      $('#mobile-money-payment-fields').slideDown();
      $('#cash-payment-info').hide();
      $('#mobile-money-number-input, #transaction-reference-input').prop('required', true);
    } else if (paymentMethod === 'cash') {
      $('#mobile-money-payment-fields').slideUp();
      $('#cash-payment-info').slideDown();
      $('#mobile-money-number-input, #transaction-reference-input').prop('required', false).val('');
    } else {
      $('#mobile-money-payment-fields').slideUp();
      $('#cash-payment-info').hide();
      $('#mobile-money-number-input, #transaction-reference-input').prop('required', false).val('');
    }
  });

  // Open record payment modal
  $(document).on('click', '.record-payment-btn', function() {
    const orderId = $(this).data('order-id');
    const orderNumber = $(this).data('order-number');
    const orderAmount = parseFloat($(this).data('order-amount'));
    
    $('#payment-order-id').val(orderId);
    $('#payment-order-number').text(orderNumber);
    $('#payment-order-amount').text(orderAmount.toLocaleString('en-US', {minimumFractionDigits: 2}));
    
    // Reset form
    $('#record-payment-form')[0].reset();
    $('#payment-method-select').val('');
    $('#mobile-money-payment-fields').hide();
    $('#cash-payment-info').hide();
    
    $('#record-payment-modal').modal('show');
  });

  // Submit payment recording form
  $('#record-payment-form').on('submit', function(e) {
    e.preventDefault();
    
    const orderId = $('#payment-order-id').val();
    const paymentMethod = $('#payment-method-select').val();
    
    if (!paymentMethod) {
      Swal.fire({
        icon: 'warning',
        title: 'Payment Method Required',
        text: 'Please select a payment method'
      });
      return;
    }
    
    // Validate mobile money fields if selected
    if (paymentMethod === 'mobile_money') {
      const mobileNumber = $('#mobile-money-number-input').val().trim();
      const transactionRef = $('#transaction-reference-input').val().trim();
      
      if (!mobileNumber || mobileNumber === '+255') {
        Swal.fire({
          icon: 'warning',
          title: 'Phone Number Required',
          text: 'Please enter customer\'s phone number'
        });
        return;
      }
      
      if (!transactionRef) {
        Swal.fire({
          icon: 'warning',
          title: 'Transaction Reference Required',
          text: 'Please enter the M-Pesa transaction reference'
        });
        return;
      }
    }
    
    const formData = {
      payment_method: paymentMethod,
      mobile_money_number: paymentMethod === 'mobile_money' ? $('#mobile-money-number-input').val().trim() : null,
      transaction_reference: paymentMethod === 'mobile_money' ? $('#transaction-reference-input').val().trim() : null
    };
    
    // Show loading
    Swal.fire({
      title: 'Recording Payment...',
      text: 'Please wait',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
    
    $.ajax({
      url: '{{ route("bar.waiter.record-payment", ":id") }}'.replace(':id', orderId),
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      data: formData,
      success: function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Payment Recorded!',
            text: 'Payment has been recorded successfully',
            confirmButtonText: 'OK'
          }).then(() => {
            location.reload();
          });
        }
      },
      error: function(xhr) {
        let errorMessage = 'Failed to record payment';
        
        if (xhr.responseJSON) {
          if (xhr.responseJSON.error) {
            errorMessage = xhr.responseJSON.error;
          } else if (xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            errorMessage = Object.values(errors).flat().join('<br>');
          } else if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          html: errorMessage,
          confirmButtonText: 'OK'
        });
      }
    });
  });
</script>
@endpush





